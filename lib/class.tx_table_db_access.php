<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2011 Franz Holzinger <franz@ttproducts.de>
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
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage table
 *
 *
 */

 require_once(PATH_t3lib.'class.t3lib_div.php');


class tx_table_db_access {
	public $queryFieldArray;
	public $tableArray;
	public $where_clause;
	public $enableFields;


	/**
	 * Prepares the execution of a SQL-statement
	 *
	 * @param	string		Table(s) from which to select. This is what comes right after "FROM ...". Required value.
	 * @param	string		type of the fields: select, groupBy, orderBy
	 * @param	array		fields to set
	 * @return	void
	 */
	public function prepareFields ($table, $type, $fields) {
		$fieldArray = explode (',', $fields);
		if ($fields == '*') {
			$this->queryFieldArray[$type][$table->name] = $table->tableFieldArray;
		} else {
			foreach ($fieldArray as $key=>$field) {
				$this->queryFieldArray[$type][$table->name][$field] = array($table->name => $field);
			}
		}
		$this->tableArray[$table->name] = &$table;
	}


	/**
	 * Prepares the execution of the where clause of the SQL-statement
	 *
	 * @param	object		Table object from which to select. This is what comes right after "FROM ...". Required value.
	 * @param	string		type of the fields: select, where, groupBy, orderBy
	 * @param	string		coparator like '='
	 * @param	string		value for the field
	 * @return	void
	 */
	public function prepareWhereFields ($table, $field, $comparator, $value) {
		global $TYPO3_DB;

		$tmpArray = $table->tableFieldArray[$field];
		if ($this->where_clause) {
			$this->where_clause .= ' AND ';
		}
		$this->where_clause .= key($tmpArray).'.'.current($tmpArray).$comparator.$TYPO3_DB->fullQuoteStr($value, $table);
		$this->tableArray[$table->name] = &$table;
	}


	/**
	 * Prepares the execution of the enable fields for the where clause of the SQL-statement
	 *
	 * @param	object		Table object from which to select. This is what comes right after "FROM ...". Required value.
	 * @param	string		enable where clause
	 * @return	void
	 */
	public function prepareEnableFields ($table, $value='') {
		if ($value)	{
			$this->enableFields = $value;
		} else {
			$this->enableFields = $table->enableFields();
		}
	}


	/**
	 * Creates and executes a SELECT SQL-statement
	 * Using this function specifically allow us to handle the LIMIT feature independently of DB.
	 *
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
 	public function exec_SELECTquery($where='',$limit='') {
 		global $TYPO3_DB;

		$select_fields = '';
		$comma = '';
		if (!is_array($this->queryFieldArray['select']) || !is_array($this->tableArray)) return NULL;

		foreach ($this->queryFieldArray['select'] as $tablename => $fieldArray) {
			foreach ($fieldArray as $origField => $tableField) {
				$select_fields.=$comma.key($tableField).'.'.current($tableField);
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
		if (is_array($this->queryFieldArray['groupBy'])) {
			$comma = '';
			foreach ($this->queryFieldArray['groupBy'] as $tablename => $fieldArray) {
				foreach ($fieldArray as $origField => $tableField) {
					$groupBy .= $comma.key($tableField).'.'.current($tableField);
					$comma = ',';
				}
			}
		}

		$orderBy = '';
		if (is_array($this->queryFieldArray['orderBy'])) {
			$comma = '';
			foreach ($this->queryFieldArray['orderBy'] as $tablename => $fieldArray) {
				foreach ($fieldArray as $origField => $tableField) {
					$groupBy .= $comma.key($tableField).'.'.current($tableField);
					$comma = ',';
				}
			}
		}

		$where_clause = $where;
		if ($this->where_clause)	{
			if ($where_clause)	{
				$where_clause .=  ' AND '.$this->where_clause;
			} else {
				$where_clause = $this->where_clause;
			}
		}
		if ($this->enableFields)	{
			if ($where_clause)	{
				$where_clause .= $this->enableFields;
			} else {
				$where_clause = $this->enableFields;
			}
		}

	 	$res = $TYPO3_DB->exec_SELECTquery($select_fields, $from_table, $where_clause,$groupBy,$orderBy,$limit);
	 	return $res;
	 }
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/table/lib/class.tx_table_db_access.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/table/lib/class.tx_table_db_access.php']);
}


?>