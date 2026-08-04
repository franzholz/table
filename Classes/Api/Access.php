<?php

namespace JambageCom\Table\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2005-2026 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the table (Table Library) extension.
 *
 * database base class for your table classes
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage table
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use Doctrine\DBAL\Result;


class Access
{
    public $queryFieldArray = [];
    public $tableArray = null;
    public $where_clause = '';
    public $enableFields = false;


    /**
     * Prepares the execution of a SQL-statement
     *
     * @param	object		Table object of class tx_ttproducts_table_base from which to select. This is what comes right after "FROM ...". Required value.
     * @param	string		type of the fields: select, groupBy, orderBy
     * @param	array		fields to set
     * @return	void
     */
    public function prepareFields($table, $type, $fields): void
    {
        $fieldArray = explode(',', $fields);
        if ($fields == '*') {
            $this->queryFieldArray[$type][$table->name] = $table->tableFieldArray;
        } else {
            foreach ($fieldArray as $key => $field) {
                $this->queryFieldArray[$type][$table->name][$field] = [$table->name => $field];
            }
        }
        $this->tableArray[$table->name] = &$table;
    }


    /**
     * Prepares the execution of the where clause of the SQL statement.
     *
     * @param object $table Table object from which to select (e.g., tx_ttproducts_table_base)
     * @param string $field The field name to evaluate
     * @param string $comparator Comparator like '=', '!=', 'LIKE'
     * @param string $value Value for the field to be securely escaped
     * @return void
     */
    public function prepareWhereFields($table, $field, $comparator, $value): void
    {
        $tmpArray = $table->tableFieldArray[$field] ?? [];

        if ($this->where_clause) {
            $this->where_clause .= ' AND ';
        }

        // Get the driver-specific database connection for the given table name
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table->name);

        // Securely quote the input value to prevent SQL injections (Replaces fullQuoteStr)
        $quotedValue = $connection->quote((string)$value);

        // Assemble the legacy raw SQL WHERE clause snippet safely
        $this->where_clause .= key($tmpArray) . '.' . current($tmpArray) . $comparator . $quotedValue;

        $this->tableArray[$table->name] = $table;
    }


    /**
     * Prepares the execution of the enable fields for the where clause of the SQL-statement
     *
     * @param	object		Table object of class tx_ttproducts_table_base from which to select. This is what comes right after "FROM ...". Required value.
     * @param	string		enable where clause
     * @return	void
     */
    public function prepareEnableFields($table, $value = ''): void
    {
        if ($value) {
            $this->enableFields = $value;
        } else {
            $this->enableFields = $table->enableFields();
        }
    }


    /**
     * Creates and executes a SELECT SQL statement based on internal query arrays.
     * Using this function specifically allow us to handle the LIMIT feature independently of DB.
     *
     *
     * @param string $where Optional additional raw WHERE clause
     * @param string|int $limit Optional LIMIT value (e.g., '10' or '0,10')
     * @return Result|null Doctrine DBAL Result object, or null on configuration failure
     */
    public function exec_SELECTquery($where = '', $limit = ''): ?Result
    {
        if (
            !isset($this->queryFieldArray['select']) ||
            !is_array($this->queryFieldArray['select']) ||
            !isset($this->tableArray) ||
            !is_array($this->tableArray)
        ) {
            return null;
        }

        $select_fields = '';
        $comma = '';
        foreach ($this->queryFieldArray['select'] as $tablename => $fieldArray) {
            foreach ($fieldArray as $origField => $tableField) {
                $select_fields .= $comma . key($tableField) . '.' . current($tableField);
                $comma = ',';
            }
        }

        $from_table = '';
        $comma = '';
        foreach ($this->tableArray as $tablename => $value) {
            $from_table .= $comma . $tablename;
            $comma = ',';
        }

        $groupBy = '';
        if (
            isset($this->queryFieldArray['groupBy']) &&
            is_array($this->queryFieldArray['groupBy'])
        ) {
            $comma = '';
            foreach ($this->queryFieldArray['groupBy'] as $tablename => $fieldArray) {
                foreach ($fieldArray as $origField => $tableField) {
                    $groupBy .= $comma . key($tableField) . '.' . current($tableField);
                    $comma = ',';
                }
            }
        }

        $orderBy = '';
        if (
            isset($this->queryFieldArray['orderBy']) &&
            is_array($this->queryFieldArray['orderBy'])
        ) {
            $comma = '';
            foreach ($this->queryFieldArray['orderBy'] as $tablename => $fieldArray) {
                foreach ($fieldArray as $origField => $tableField) {
                    $orderBy .= $comma . key($tableField) . '.' . current($tableField);
                    $comma = ',';
                }
            }
        }

        $where_clause = $where;
        if ($this->where_clause) {
            if ($where_clause) {
                $where_clause .= ' AND ' . $this->where_clause;
            } else {
                $where_clause = $this->where_clause;
            }
        }

        if ($this->enableFields) {
            if ($where_clause) {
                $where_clause .= $this->enableFields;
            } else {
                $where_clause = $this->enableFields;
            }
        }

        // Extract the primary table name to fetch the correct database connection context
        $mainTable = key($this->tableArray);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($mainTable);

        // Explicitly configure frontend restrictions if required.
        // If your internal $this->enableFields already adds them as raw SQL strings,
        // you might want to call $queryBuilder->getRestrictions()->removeAll(); to prevent duplicates.
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        // Map SELECT fields into individual array arguments
        $fields = GeneralUtility::trimExplode(',', $select_fields, true);
        $queryBuilder->select(...$fields);

        // Map dynamic FROM/JOIN string blocks into the statement
        $queryBuilder->from($from_table);

        // Append the compiled where clause safely using the QueryHelper utility
        if (trim((string)$where_clause) !== '') {
            $cleanWhere = QueryHelper::stripLogicalOperatorPrefix($where_clause);
            $queryBuilder->andWhere($cleanWhere);
        }

        // Process GROUP BY
        if (trim((string)$groupBy) !== '') {
            $groups = GeneralUtility::trimExplode(',', $groupBy, true);
            foreach ($groups as $groupField) {
                $queryBuilder->addGroupBy($groupField);
            }
        }

        // Process ORDER BY using the core QueryHelper parser
        if (trim((string)$orderBy) !== '') {
            foreach (QueryHelper::parseOrderBy($orderBy) as $orderPair) {
                [$orderField, $direction] = $orderPair;
                $queryBuilder->addOrderBy($orderField, $direction);
            }
        }

        // Process LIMIT / OFFSET configuration
        if (trim((string)$limit) !== '') {
            $limitParts = GeneralUtility::intExplode(',', (string)$limit, true);
            if (count($limitParts) === 2) {
                $queryBuilder->setFirstResult($limitParts[0]); // OFFSET
                $queryBuilder->setMaxResults($limitParts[1]);  // LIMIT
            } else {
                $queryBuilder->setMaxResults($limitParts[0]);  // LIMIT only
            }
        }

        // Execute the query and return the Doctrine DBAL Result object
        return $queryBuilder->executeQuery();
    }
}
