<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2016 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage table
 *
 * Typically instantiated like this:
 * $this->table = t3lib_div::makeInstance('tx_table_db');
 * $this->table-> ... set your parameters here
 * $this->table->init();
 *
 */


class tx_table_db {
	public $tableFieldArray = array(); // array of fields for each table
	public $defaultFieldArray =
			array(
				'uid'=>'uid',
				'pid'=>'pid',
				'tstamp'=>'tstamp',
				'crdate'=>'crdate',
				'deleted' => 'deleted'
			); // TYPO3 default fields
	public $noTCAFieldArray =
			array(
				'cruser_id' => 'cruser_id',
				't3ver_oid' => 't3ver_oid',
				't3ver_id' => 't3ver_id',
				't3ver_label' => 't3ver_label',
				'sorting' => 'sorting',
			); // fields which do not have an entry in TCA
	public $newFieldArray = array(); 	// containts the field names which are no default fields (needed for insert)
	public $aliasArray; // alias names for tables
	public $langArray = array(); // array of language values
	public $markerArray = array(); // array of marker values
	public $name; // name of the table
	public $langname; // name of the language table
	public $enableFields;
	public $foreignUidArray = array();	// foreign keys to uid of table
	public $LLkey; 	// language key to use
	public $requiredFieldArray; // fields which must be read in even if no markers are found - needed in extensions
	public $columnPrefix; // prefix put before the column names
	public $config = array(); // configuration array
	public $bNeedsInit = TRUE;


	// use setTCAFieldArray instead of this
	public function init (
		$table,
		$tableAlias = '',
		$tableFieldArray = array()
	) {
		$this->aliasArray [$table] = ($tableAlias ? $tableAlias : $table);
		if (count($tableFieldArray)) {
			$this->tableFieldArray = $tableFieldArray;
		}

		$this->bNeedsInit = FALSE;
	}


	public function needsInit () {
		return $this->bNeedsInit;
	}


	public function getName () {
		return $this->name;
	}


	public function setName ($name) {
		$this->name = $name;
	}


	public function getLangName () {
		return $this->langname;
	}


	public function setLangName ($name) {
		$this->langname = $name;
	}


	public function getAlias () {
		$result = '';
		$name = $this->getName();
		if (isset($this->aliasArray[$name])) {
			$result = $this->aliasArray[$name];
		}
		return $result;
	}


	public function getLangAlias () {
		$name = $this->getLangName();
		return $this->aliasArray[$name];
	}


	public function setConfig (&$config) {
		$this->config = &$config;
	}


	public function &getConfig () {
		return $this->config;
	}


	/* deprecated */
	public function getAliasName () {
		return $this->getAlias();
	}


	public function getLanguage () {
		return $this->LLkey;
	}


	public function setLanguage ($LLkey) {
		$this->LLkey = $LLkey;
	}


	public function getField ($field) {
		$result = $field;
		$fieldArray = $this->tableFieldArray[$field];
		if (isset($fieldArray) && is_array($fieldArray)) {
			$result = current($fieldArray);
		}
		return $result;
	}


	public function getTableFromField ($field) {
		$result = FALSE;
		$fieldArray = $this->tableFieldArray[$field];
		if (isset($fieldArray) && is_array($fieldArray)) {
			$result = key($fieldArray);
		}
		return $result;
	}


	public function initFile (
		$filename,
		&$retLangArray,
		$keyWrapArray = array()
	) {
		if (
			@is_file($filename) &&
			t3lib_div::validPathStr($filename)
		) {
			$line = file_get_contents($filename);
			if ($line === FALSE) {
				return FALSE;
			}
			$tokenArray = preg_split('/[\n|\r|\f]+/', $line);

			foreach ($tokenArray as $k => $tokenRow) {
				$langArray = t3lib_div::trimExplode(';', $tokenRow);
				if ($langArray[0] != '') {
					$retLangArray[$keyWrapArray[0] . $langArray[0] . $keyWrapArray[1]] = $langArray[1];
				}
			}
		}
	}


	public function getMarkerArray () {
		return $this->markerArray;
	}


	public function substituteMarkerArray (
		&$row,
		$excludeFieldArray = array()
	) {
		if (is_array($row)) {

			foreach ($row as $field => $value) {
				if (
					!is_array($value) && strstr($value, '###') !== FALSE &&
					(
						$excludeFieldArray == '' ||
						is_array($excludeFieldArray) && !in_array($field, $excludeFieldArray)
					)
				) {
					$valueArray = explode('###', $value);
					$newValueArray = array();

					foreach ($valueArray as $k => $valPar) {
						$trimValPar = trim($valPar);
						if ($valPar == strtoupper($valPar) && $trimValPar != '') {
							if ($trimValPar != ';') {
								$markerKey = '###'.$valPar.'###';

								if (isset($this->markerArray[$markerKey])) {
									$newValueArray[$k] = $this->markerArray[$markerKey];
								} else {
									$newValueArray[$k] = $markerKey;
								}
							} else {
								$newValueArray[$k] = $trimValPar;
							}
						}
					}
					$row[$field] = implode('', $newValueArray);
				}
			}
		}
	}


	public function initLanguageFile ($filename) {
		$this->initFile($filename, $this->langArray);
	}


	public function initMarkerFile ($filename) {
		$this->initFile($filename, $this->markerArray, array('###', '###'));
	}


	public function setColumnPrefix ($prefix) {
		$this->columnPrefix = $prefix;
	}


	// use setTCAFieldArray instead of this
	public function setTableFieldArray ($table, $tableAlias = '', $fieldArray) {
		$this->aliasArray[$table] = ($tableAlias ? $tableAlias : $table);
		foreach ($fieldArray as $fieldbase => $field) {
			$this->tableFieldArray[$fieldbase] = array ($table => $field);
		}
	}


	public function getRequiredFieldArray () {
		return $this->requiredFieldArray;
	}

// neu Anfang
	public function setRequiredFieldArray ($fieldArray = array()) {
		$requiredFieldArray = array();
		$defaultFieldArray = $this->getDefaultFieldArray();
		$noTcaFieldArray = $this->getNoTcaFieldArray();
		foreach ($fieldArray as $field) {
			if (
				$this->bFieldExists($field) ||
				isset($defaultFieldArray[$field]) ||
				isset($noTcaFieldArray[$field])
			) {
				$requiredFieldArray[] = $field;
			}
		}
		$this->requiredFieldArray = $requiredFieldArray;
	}
// neu Ende


	public function addRequiredFieldArray ($fieldArray = array()) {
		$this->requiredFieldArray = array_merge($this->requiredFieldArray, $fieldArray);
	}


	public function getDefaultFieldArray () {
		return $this->defaultFieldArray;
	}


	public function getNoTcaFieldArray () {
		return $this->noTCAFieldArray;
	}



	public function setDefaultFieldArray ($defaultFieldArray = array()) {
		if (isset($this->defaultFieldArray) && is_array($this->defaultFieldArray)) {
			foreach ($this->defaultFieldArray as $field => $realField) {
				if (isset($this->tableFieldArray[$field])) {
					unset($this->tableFieldArray[$field]);
				}
			}
		}
		$this->defaultFieldArray = $defaultFieldArray;
	}


	public function addDefaultFieldArray ($defaultFieldArray = array()) {
		$this->defaultFieldArray = array_merge($this->defaultFieldArray, $defaultFieldArray);
	}


	public function setNewFieldArray () {
		$this->newFieldArray = array();

		if (isset($this->tableFieldArray) && is_array($this->tableFieldArray)) {
			foreach ($this->tableFieldArray as $fieldname => $value) {
				if (!($this->defaultFieldArray[$fieldname])) {
					$this->newFieldArray[] = $fieldname;
				}
			}
		}
	}


	public function getTCA (
		$part,
		$field = ''
	) {
		$result = FALSE;

		$table = $this->getName();
		if (version_compare(TYPO3_version, '6.1.0', '<')) {
			t3lib_div::loadTCA($table);
		}

		if (
			is_array($GLOBALS['TCA'][$table]) &&
			is_array($GLOBALS['TCA'][$table][$part])
		) {
			if ($field) {
				$result = $GLOBALS['TCA'][$table][$part][$field];
			} else {
				$result = $GLOBALS['TCA'][$table][$part];
			}
		}
		return $result;
	}


	public function getLangTCA (
		$part,
		$field = ''
	) {
		$table = $this->langname;
		if (version_compare(TYPO3_version, '6.1.0', '<')) {
			t3lib_div::loadTCA($table);
		}

		if (
			is_array($GLOBALS['TCA'][$table]) &&
			is_array($GLOBALS['TCA'][$table][$part])
		) {
			if ($field) {
				$result = $GLOBALS['TCA'][$table][$part][$field];
			} else {
				$result = $GLOBALS['TCA'][$table][$part];
			}
		}
		return $result;
	}


	public function bFieldExists ($field) {
		$field = $this->getField($field);
		$fieldTca = $this->getTCA('columns', $field);
		return (isset($fieldTca));
	}


	/* must be called after setTCAFieldArray */
	public function setNoTCAFieldArray (
		$table,
		$fieldArray
	) {
		foreach ($fieldArray as $key => $field) {
			if (
				!isset($this->tableFieldArray[$field])
			) {
				$this->tableFieldArray[$field] = array($table => $field);
			}
		}
	}


	public function getForeignUidArray ($table = '') {
		$result = FALSE;

		if ($table) {
			$result = $this->foreignUidArray[$table];
		} else {
			$result = $this->foreignUidArray;
		}
		return $result;
	}


	public function setForeignUidArray (
		$table,
		$field
	) {
		$this->foreignUidArray[$table] = $field;
	}


	/**
	 * Generates a search where clause based on the input search words (AND operation - all search words must be found in record.)
	 * Example: The $sw is "content management, system" (from an input form) and the $searchFieldList is "bodytext,header" then the output will be ' AND (bodytext LIKE "%content%" OR header LIKE "%content%") AND (bodytext LIKE "%management%" OR header LIKE "%management%") AND (bodytext LIKE "%system%" OR header LIKE "%system%")'
	 *
	 * @param	string		The search words. These will be separated by space and comma.
	 * @param	string		The fields to search in
	 * @param	boolean		If the language table shall be used for the fields which need a translation
	 * @param	string		character intermediate regular expression. This will be inserted between all characters of the search words. "{s1}" is a placeholder for the search word.
	 * @param	array		key => value pairs for characters which should be alternatives
	 * @return	string		The WHERE clause.
	 */
	public function searchWhere (
		$sw,
		$searchFieldList,
		$bUseLanguage = TRUE,
		$charRegExp = '',
		$replaceConf = array()
	) {
		$where = '';
		$replaceArray = array();

		if (!empty($replaceConf)) {
			foreach ($replaceConf as $search => $replace) {
				$replaceArray[$search][] = $replace;
				$replaceArray[$replace][] = $search;
			}
		}

		if ($sw) {
			$tablename = $this->getName();
			$languageName = $this->getLangName();
			$aliasArray = array();
			$aliasArray[$tablename] = $this->getAlias();
			$aliasArray[$languageName] = $this->getLangAlias();
			$searchFields = explode(',', $searchFieldList);
			$kw = preg_split('/[ ,]/', $sw);

			foreach ($kw as $val) {
				$val = trim($val);
				$where_p = array();
				if (strlen($val) >= 2) {
					$valueArray = array();
					$valueArray[$tablename] =
						$GLOBALS['TYPO3_DB']->escapeStrForLike(
							$GLOBALS['TYPO3_DB']->quoteStr(
								$val,
								$tablename
							),
						$tablename
					);

					if ($bUseLanguage) {
						$valueArray[$languageName] =
							$GLOBALS['TYPO3_DB']->escapeStrForLike(
								$GLOBALS['TYPO3_DB']->quoteStr(
									$val,
									$languageName
								),
							$languageName
						);
					}

					foreach ($searchFields as $field) {
						$theTablename = $tablename;
						if ($bUseLanguage) {
							$theTablename = $this->getTableFromField($field);
						}

						if ($theTablename != '') {
							$part2 = '';
							if ($charRegExp != '') {
								$comparatorArray = array();
								$value2 = $valueArray[$theTablename];

								if (!empty($replaceArray)) {
									foreach ($replaceArray as $search => $searchArray) {
										if (empty($searchArray)) {
											continue;
										}
										$variantArray = array();
										$variantArray[] = $search;
										$variantArray = array_merge($variantArray, $searchArray);
										$value2 =
											str_replace($search, '(' . implode('|', $variantArray) . ')', $value2);
									}
								}

								if (strpos($charRegExp, '"{s1}"') !== FALSE) {
									$tmpCharRegExp = str_replace('"{s1}"', $value2, $charRegExp);
								} else {
									$tmpCharRegExp = $value2 . $charRegExp;
								}

								$part2 = 'REGEXP \'' . $tmpCharRegExp . '\'';
							} else {
								$part2 = 'LIKE \'%' . $valueArray[$theTablename] . '%\'';
							}
							$where_p[] = $aliasArray[$theTablename] . '.' . $field . ' ' . $part2;
						}
					}
				}

				if (count($where_p)) {
					$where .= ' AND (' . implode(' OR ', $where_p) . ')';
				}
			}
		}
		return $where;
	}


	public function setTCAFieldArray (
		$table,
		$tableAlias = '',
		$bSetTablename = TRUE
	) {
		if ($table != '') {
			if (
				$bSetTablename &&
				$table != $this->getName() &&
				$table != $this->getLangName()
			) {
				$this->setName($table);
			}

			$tmp = ($tableAlias ? $tableAlias : $table);
			$this->aliasArray[$table] = $tmp;

			if (version_compare(TYPO3_version, '6.1.0', '<')) {
				t3lib_div::loadTCA($table);
			}

			reset($this->aliasArray);
			$tmp = key($this->aliasArray);

			if (is_array($this->defaultFieldArray)) {

				foreach ($this->defaultFieldArray as $field => $realField) {
					if (
						$field != 'uid' &&
						(
							!is_array($this->foreignUidArray) ||
							!in_array($field, $this->foreignUidArray)
						) ||
						$table == key($this->aliasArray)
					) {
						$this->tableFieldArray[$field] = array($table => $realField);
					}
					if ($field == 'uid') {
						// nothing yet
					}
				}
			}

			if ($GLOBALS['TCA'][$table]['columns']) {
				foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $fieldArray) {

					if (
						$fieldArray['config']['type'] != 'passthrough' &&
						$fieldArray['config']['db'] != 'passthrough'
							||
						$field == 'sorting'
					) {
						$this->tableFieldArray[$field] = array($table => $field);

							// is there a foreign key to the first table?
						if (
							(
								$fieldArray['config']['type'] == 'select' ||
								$fieldArray['config']['type'] == 'group'
							) &&
							($foreignTable = $fieldArray['config']['foreign_table']) != ''
						) {
							$this->setForeignUidArray($table, $field);
						}
					}
				}
			}

			if (
				is_array($this->requiredFieldArray) &&
				count($this->requiredFieldArray)
			) {
				foreach ($this->requiredFieldArray as $k => $field) {
					if (
						$field &&
						!isset($this->tableFieldArray[$field]) &&
						$field != 'uid' &&
						isset($GLOBALS['TCA'][$table]['columns'][$field])
					) {
						$this->tableFieldArray[$field] = array ($table => $field);
					}
					if ($field == 'uid') {
						// nothing yet
					}
				}
			}

			$this->bNeedsInit = FALSE;
		} else {
			$tmp = tx_div2007_core::debugTrail();
			tx_div2007_core::debug($tmp);
			die ('The function setTCAFieldArray() is called with an empty table name as argument.');
		}
	}


	/**
	 * Returns the array of fields which will filter out records with start/end times or hidden/fe_groups fields set to values that should de-select them according to the current time, preview settings or user login. Definitely a frontend function.
	 *
	 * @param	integer		If $show_hidden is set (0/1), any hidden-fields in records are ignored. NOTICE: If you call this function, consider what to do with the show_hidden parameter. Maybe it should be set? See tslib_cObj->enableFields where it's implemented correctly.
	 * @param	array		Array you can pass where keys can be "disabled", "starttime", "endtime", "fe_group" (keys from "enablefields" in TCA) and if set they will make sure that part of the clause is not added. Thus disables the specific part of the clause. For previewing etc.
	 * @param	string 		table name (optional)
	 * @return	string		The clause starting like " AND ...=... AND ...=..." is as well set internally.
	 * @see enableFields()
	 */
	public function getEnableFieldArray (
		$show_hidden = -1,
		$ignore_array = array(),
		$table = ''
	) {
		if ($this->needsInit()) {
			return FALSE;
		}
		if (!$table) {
			$table = $this->getName();
		}
		$aliasTable = (isset($this->aliasArray[$table]) ? $this->aliasArray[$table] : $table);

		if ($show_hidden==-1 && is_object($GLOBALS['TSFE'])) {	// If show_hidden was not set from outside and if TSFE is an object, set it based on showHiddenPage and showHiddenRecords from TSFE
			$show_hidden = $table == 'pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords;
		}
		if ($show_hidden == -1) {
			$show_hidden = 0;	// If show_hidden was not changed during the previous evaluation, do it here.
		}

		$ctrl = $GLOBALS['TCA'][$table]['ctrl'];
		$fieldArray = array();
		if (is_array($ctrl)) {
			if ($ctrl['delete']) {
				$query .= ' AND ' . $aliasTable . '.' . $ctrl['delete'] . '=0';
				$fieldArray[] = 'delete';
			}

			if (is_array($ctrl['enablecolumns'])) {
				if ($ctrl['enablecolumns']['disabled'] && !$show_hidden && !$ignore_array['disabled']) {
					$fieldArray[] = $ctrl['enablecolumns']['disabled'];
				}
				if ($ctrl['enablecolumns']['starttime'] && !$ignore_array['starttime']) {
					$fieldArray[] = $ctrl['enablecolumns']['starttime'];
				}
				if ($ctrl['enablecolumns']['endtime'] && !$ignore_array['endtime']) {
					$fieldArray[] = $ctrl['enablecolumns']['endtime'];
				}
				if ($ctrl['enablecolumns']['fe_group'] && !$ignore_array['fe_group']) {
					$fieldArray[] = $ctrl['enablecolumns']['fe_group'];
				}

					// Call hook functions for additional enableColumns
					// It is used by the extension ingmar_accessctrl which enables assigning more than one usergroup to content and page records
				if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['ext/table/lib/class.tx_table_db.php']['addEnableFieldArray'])) {
					$_params = array(
						'table' => $table,
						'show_hidden' => $show_hidden,
						'ignore_array' => $ignore_array,
						'ctrl' => $ctrl
					);
					foreach($TYPO3_CONF_VARS['SC_OPTIONS']['ext/table/lib/class.tx_table_db.php']['addEnableFieldArray'] as $_funcRef) {
						$addFieldArray = t3lib_div::callUserFunction($_funcRef, $_params,$this);
						if (isset($addFieldArray) && is_array($addFieldArray)) {
							$fieldArray = array_merge($fieldArray, $addFieldArray);
						}
					}
				}
			}
		} else {
			$tmp = tx_div2007_core::debugTrail();
			tx_div2007_core::debug($tmp);
			die ('NO entry in the $GLOBALS[\'TCA\']-array for the table "' . $table . '". This means that the function enableFields() is called with an invalid table name as argument.');
		}
		$fieldArray = array_unique($fieldArray);
		return $fieldArray;
	}


	/**
	 * Returns a part of a WHERE clause which will filter out records with start/end times or hidden/fe_groups fields set to values that should de-select them according to the current time, preview settings or user login. Definitely a frontend function.
	 * Is using the $GLOBALS['TCA'] arrays "ctrl" part where the key "enablefields" determines for each table which of these features applies to that table.
	 * The alias table name gets used
	 *
	 * @param	integer		If $show_hidden is set (0/1), any hidden-fields in records are ignored. NOTICE: If you call this function, consider what to do with the show_hidden parameter. Maybe it should be set? See tslib_cObj->enableFields where it's implemented correctly.
	 * @param	array		Array you can pass where keys can be "disabled", "starttime", "endtime", "fe_group" (keys from "enablefields" in TCA) and if set they will make sure that part of the clause is not added. Thus disables the specific part of the clause. For previewing etc.
	 * @param	string 		table name (optional)
	 * @return	string		The clause starting like " AND ...=... AND ...=..." is as well set internally.
	 * @see tslib_cObj::enableFields(), deleteClause()
	 */
	public function enableFields (
		$aliasPostfix = '',
		$show_hidden = -1,
		$ignore_array = array(),
		$table = ''
	) {
		if ($this->needsInit()) {
			return FALSE;
		}
		if (!$table) {
			$table = $this->getName();
		}
		$aliasTable = (isset($this->aliasArray[$table]) ? $this->aliasArray[$table] . $aliasPostfix : $table);

		if ($show_hidden==-1 && is_object($GLOBALS['TSFE'])) {	// If show_hidden was not set from outside and if TSFE is an object, set it based on showHiddenPage and showHiddenRecords from TSFE
			$show_hidden = $table == 'pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords;
		}
		if ($show_hidden == -1) {
			$show_hidden = 0;	// If show_hidden was not changed during the previous evaluation, do it here.
		}

		$ctrl = $GLOBALS['TCA'][$table]['ctrl'];
		$query='';
		if (is_array($ctrl)) {
			if ($ctrl['delete']) {
				$query .=' AND ' . $aliasTable . '.' . $ctrl['delete'] . '=0';
			}

			if (is_array($ctrl['enablecolumns'])) {
				if ($ctrl['enablecolumns']['disabled'] && !$show_hidden && !$ignore_array['disabled']) {
					$field = $aliasTable . '.' . $ctrl['enablecolumns']['disabled'];
					$query .= ' AND ' . $field . '=0';
				}
				if ($ctrl['enablecolumns']['starttime'] && !$ignore_array['starttime']) {
					$field = $aliasTable . '.' . $ctrl['enablecolumns']['starttime'];
					$query.=' AND (' . $field . '<=' . $GLOBALS['SIM_EXEC_TIME'].')';
				}
				if ($ctrl['enablecolumns']['endtime'] && !$ignore_array['endtime']) {
					$field = $aliasTable . '.' . $ctrl['enablecolumns']['endtime'];
					$query .= ' AND (' . $field . '=0 OR ' . $field . '>' . $GLOBALS['SIM_EXEC_TIME'] . ')';
				}
				if (
					is_object($GLOBALS['TSFE']) &&
					$ctrl['enablecolumns']['fe_group'] &&
					!$ignore_array['fe_group']
				) {
					$field = $aliasTable . '.' . $ctrl['enablecolumns']['fe_group'];
					$gr_list = $GLOBALS['TSFE']->gr_list;
					if (!strcmp($gr_list, '')) {
						$gr_list = 0;
					}
					$query .= ' AND ' . $field . ' IN (\' \',' . $gr_list . ')';
				}

					// Call hook functions for additional enableColumns
					// It is used by the extension ingmar_accessctrl which enables assigning more than one usergroup to content and page records
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['addEnableColumns'])) {
					$_params = array(
						'table' => $table,
						'show_hidden' => $show_hidden,
						'ignore_array' => $ignore_array,
						'ctrl' => $ctrl
					);
					foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['addEnableColumns'] as $_funcRef) {
						$query .= t3lib_div::callUserFunction($_funcRef, $_params, $this);
					}
				}
			}
		} else {
			$tmp = tx_div2007_core::debugTrail();
			tx_div2007_core::debug($tmp);
			die ('NO entry in the $GLOBALS[\'TCA\']-array for the table "' . $table . '". This means that the function enableFields() is called with an invalid table name as argument.');
		}
		$this->enableFields = $query;

		return $query;
	}


	/**
	 * Returns the SQL where clause with the correct table alias names
	 *
	 * @param	string		List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	public function transformWhere (
		$clause,
		$aliasPostfix = '',
		&$joinFallback = '',
		$joinTableArray = array()
	) {
		if ($this->needsInit()) {
			return FALSE;
		}

		$result = '';
		$bracketOpen = preg_split('/\(/', $clause);
		$bracketOpenArray = array();
		$bracketOpenOffset = '';

		foreach ($bracketOpen as $key => $part) {
			$part = $bracketOpenOffset . $part;
			$bracketOpenOffset = '';
			if (isset($part)) {
				if (($quotePos = strpos($part, '\'')) !== FALSE) {
					$quoteCount = 1;
					$len = strlen($part);
					while ($quotePos !== FALSE && $quotePos < $len - 1) {
						$quotePos = strpos($part, '\'', $quotePos + 1);
						if ($quotePos !== FALSE) {
							$quoteCount++;
						}
					}
					$halfQuoteCount = $quoteCount >> 1;
					$fullQuoteCount = $halfQuoteCount << 1;

					if ($quoteCount != $fullQuoteCount) {	// not even. This means that a open bracket ( has been inside of a quoted string
						$bracketOpenOffset = $part . '(';	// add this part to the next one
						continue;
					}
				}
				$bracketClose = preg_split('/\)/', $part);
				$bracketCloseArray = array();

				foreach ($bracketClose as $key2 => $part2) {

					if (isset($part2)) {
						$blank = preg_split('/ /', $part2, -1, PREG_SPLIT_NO_EMPTY);
						$blankArray = array();

						foreach ($blank as $key3 => $part3) {
							$chars = preg_split('//', $part3, -1, PREG_SPLIT_NO_EMPTY);
							$part3pre = '';
							$i = 0;
							while (
								$i < count($chars) &&
								$chars[$i] != '<' &&
								$chars[$i] != '>' &&
								$chars[$i] != '='
							) {
								$part3pre .= $chars[$i];
								$i++;
							}

							if ($part3pre != '') {
								$part3prePos = strpos($part3pre , '.');
								if ($part3prePos !== FALSE) {
									$part3preArray = explode('.', $part3pre);
									if (
										$part3preArray['0'] == $this->getName() ||
										$part3preArray['0'] == $this->getAlias()
									) {
										$part3pre = $part3preArray['1'];
									}
								}
							}
							$part3comp = '';

							while (
								$i < count($chars) &&
								(
									$chars[$i] == '<' ||
									$chars[$i] == '>' ||
									$chars[$i] == '='
								)
							) {
								$part3comp .= $chars[$i];
								$i++;
							}
							$part3post = '';

							while ($i < count($chars)) {
								$part3post .= $chars[$i];
								$i++;
							}

							if ($part3post != '') {
								$part3postPos = strpos($part3post, '.');
								if ($part3postPos !== FALSE) {
									$part3postArray = explode('.', $part3post);
									if (
										$part3postArray['0'] == $this->getName() ||
										$part3postArray['0'] == $this->getAlias()
									) {
										$part3post = $part3postArray['1'];
									}
								}
							}

							if ($part3pre) {
								$tableField = $this->tableFieldArray[$part3pre];
								if (is_array($tableField)) {
									$part3pre = $this->aliasArray[key($tableField)] . $aliasPostfix . '.' . current($tableField);
								}
							}

							if ($part3post) {
								$tableField = $this->tableFieldArray[$part3post];
								if (is_array($tableField)) {
									$part3post = $this->aliasArray[key($tableField)] .  $aliasPostfix . '.' . current($tableField);
								}
							}

							$newBlank = $part3pre . $part3comp . $part3post;
							$blankArray[] = $newBlank;
						}
						$line = implode (' ', $blankArray);
						$bracketCloseArray[] = $line;
					} else {
						$bracketCloseArray[] = '';
					}
				} // foreach ($bracketClose ...
				$bracketOpenArray[] = implode (')', $bracketCloseArray);
			} else {
				$bracketOpenArray[] = '';
			}
		} // foreach ($bracketOpen ...

		if ($joinFallback != '') {
			$alias = $this->getAlias();
			$langAlias = $this->getLangAlias();
			$mainBracketOpenArray = array();
			$joinBracketOpenArray = array();
			$countMain = 0;
			$countJoin = 0;
			$indexMain = 0;
			$indexJoin = 0;
			$mainOpenCount = 0;

			foreach ($bracketOpenArray as $line) {
				if (strpos($line, $langAlias) !== FALSE) {
					if (strpos($line, ')') !== FALSE) {
						$bracketCloseArray = preg_split('/\)/', $line);
						$languageAdded = FALSE;
						foreach ($bracketCloseArray as $k => $line) {
							$addClosingBracket = ($k < count($bracketCloseArray) - 1);
							if ($addClosingBracket) {
								$line .= ')';
							}

							if (strpos($line, $langAlias) === FALSE) {
								if (
									strpos($line, $alias) !== FALSE ||
									!$languageAdded
								) {
									if ($addClosingBracket && $line != ')') {
										$indexMain = $countMain;
									} else {
										$indexMain = $countMain - 1;
									}
									if ($indexMain < 0) {
										$indexMain = 0;
									}
									$mainBracketOpenArray[$indexMain] .= $line;
									if ($indexMain == $countMain) {
										$countMain++;
									}

									$languageAdded = FALSE;
								} else {
									$indexJoin = $countJoin - 1;
									if ($indexJoin < 0) {
										$indexJoin = 0;
									}
									$joinBracketOpenArray[$indexJoin] .= $line;
								}
							} else {
								$languageAdded = TRUE;
								$indexJoin = $countJoin;
								$joinBracketOpenArray[$indexJoin] .= $line;
								$countJoin++;
							}
						}
					} else {
						$languageAdded = TRUE;
						$joinBracketOpenArray[] = $line;
						$countJoin++;
					}
				} else {
					$mainBracketOpenArray[] = $line;
					$countMain++;
					$languageAdded = FALSE;
				}
			}
			$joinFallback .= ' ' . implode ('(', $joinBracketOpenArray);
			$result = implode ('(', $mainBracketOpenArray);
		} else {
			$result = implode ('(', $bracketOpenArray);
		}

		$dummy = '';
		if ($joinFallback != '') {
			$this->transformLanguage($dummy, $joinFallback);
		} else {
			$this->transformLanguage($dummy, $result);
		}

		return $result;
	}


	/**
	 * Adds the language table and where clause if a translation is needed.
	 *
	 * @param	string		from
	 * @param	string		where clause
	 * @param	boolean		TRUE, if the language table shall use the outer join
	 * @return	string		Select clause
	 */
	public function transformLanguage (
		&$table,
		&$where,
		$bUseJoin = FALSE
	) {
			// set the language
		if (
			$this->getLanguage() &&
			is_array($this->tableFieldArray) &&
			is_array($this->tableFieldArray['sys_language_uid'])
		) {
			$tableField = $this->tableFieldArray['sys_language_uid'];
			$newWhere = ' AND ' . $this->aliasArray[key($tableField)] . '.' . current($this->tableFieldArray['sys_language_uid']) . '=' . $GLOBALS['TSFE']->config['config']['sys_language_uid'];
			$languageTable = $this->getLangName();

			if ($languageTable != '') {
				if (strpos($table, $languageTable) === FALSE) {
					if ($bUseJoin && $table != '') {
						$foreignUidArray = $this->getForeignUidArray();
						$tableNew = ' LEFT OUTER JOIN ' . $languageTable . ' ' . $this->aliasArray[$languageTable] . ' ON ' . $this->getAliasName() . '.uid=' . $this->aliasArray[$languageTable] . '.' . $foreignUidArray[$languageTable];
						$table .= $tableNew . $newWhere;
					} else {
						$tableNew = $languageTable . ' ' . $this->aliasArray[$languageTable];
						$table .= (strlen($table) ? ',' : '') . $tableNew;
						$where .= $newWhere;
					}
				}
			} else {
				$where .= $newWhere;
			}
		}
	}


	/**
	 * Returns a simple SQL select clause for this table with the correct table alias names
	 *
	 * @param	string		List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @param	string		postfix for the alias
	 * @param	array		The collation configuration properties: field name as key and collation as value e.g. array('title' => 'utf8_bin');
	 * @return	string		Select clause
	 */
	public function transformSelect (
		$clause,
		$aliasPostfix = '',
		$collateConf = array()
	) {
		if ($this->needsInit()) {
			return FALSE;
		}

		$result = FALSE;
		$resultArray = array();

		if (
			is_array($this->aliasArray) &&
			count($this->aliasArray) &&
			is_array($this->tableFieldArray)
		) {
			if ($clause == '*') {

				foreach ($this->tableFieldArray as $productsfield => $fieldArray) {
					foreach ($fieldArray as $table => $field) {
						$resultArray[] = $this->aliasArray[$table] . $aliasPostfix . '.' . $field . ' ' . $this->columnPrefix . $productsfield;
					}
				}

				if (
					is_array($this->requiredFieldArray) &&
					count($this->requiredFieldArray)
				) {
					$table = $this->getName();

					foreach ($this->requiredFieldArray as $k => $field) {

						if ($field && !isset($this->tableFieldArray[$field]) && $field != 'uid') {
							$resultArray[] = $this->aliasArray[$table] . $aliasPostfix . '.' . $field . ' ' . $this->columnPrefix . $field;
						}
					}
				}
				$result = implode (',', $resultArray);
			} else if (strpos($clause,'count(') !== FALSE) {
				$result = $clause;
			} else if ($clause == '') {
				// nothing
			} else {
				$fieldArray = t3lib_div::trimExplode(',', $clause);

				foreach ($fieldArray as $k => $field) {
					$bAddAlias = TRUE;
					if (is_array($this->tableFieldArray[$field])) {
						$table = key($this->tableFieldArray[$field]);
						$realField = $this->tableFieldArray[$field][$table];
					} else {
						$table = $this->getName();
						$realField = $field;
						if (strpos($realField, ' ') !== FALSE) {
							$bAddAlias = FALSE;
						}
					}
					$collatePart = '';
					if (
						isset($collateConf) &&
						is_array($collateConf) &&
						is_array($collateConf[$table]) &&
						isset($collateConf[$table][$realField])
					) {
						$collatePart = ' COLLATE ' . $collateConf[$table][$realField];
					}

					if ($bAddAlias) {
						$line = $this->aliasArray[$table] . $aliasPostfix . '.' . $field . $collatePart . ' ' . $this->columnPrefix . $realField;
						$resultArray[] = $line;
					} else {
						$resultArray[] = $realField . $collatePart;
					}
				}
				$result = implode (',', $resultArray);
			}
		} else {
			$result = 'error: wrong initialisation before call of transformSelect with ' . $clause;
		}

		return $result;
	}


	/**
	 * Returns the SQL orderby clause with the correct table alias names
	 *
	 * @param	string		List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @return	string		Select clause
	 */
	public function transformOrderby (
		$clause,
		$aliasPostfix = ''
	) {
		if ($this->needsInit()) {
			return FALSE;
		}

		$result = '';
		if ($clause == '') {
			// nothing
		} else {
			$parts = t3lib_div::trimExplode(',', $clause);

			foreach ($parts as $k => $fieldExpression) {
				$spaceStartPos = strpos($fieldExpression, ' ');
				$bracketStartPos = strpos($fieldExpression, '(');
				$bracketEndPos = strpos($fieldExpression, ')');
				$function = '';

				if ($spaceStartPos === FALSE) {
					$field = $fieldExpression;
					unset($order);
				} else {
					$field = substr($fieldExpression, 0, $spaceStartPos);
					$order = substr($fieldExpression, $spaceStartPos);
				}

				if ($bracketStartPos !== FALSE) {
					$expression = $field;
					$field = substr($expression, $bracketStartPos + 1);
					$function = substr($expression, 0, $bracketStartPos);
				}

				if ($bracketEndPos !== FALSE) {
					$expression = $field;
					$fieldBracketEndPos = strpos($expression, ')');
					$field = substr($expression, 0, $fieldBracketEndPos);
				}

				$fieldArray = t3lib_div::trimExplode ('.', $field);

				// no table has been specified?
				if (
					(count($fieldArray) == 1) &&
					isset($this->tableFieldArray[$field]) &&
					is_array($this->tableFieldArray[$field])
				) { // TODO: check this
					$tableName = key($this->tableFieldArray[$field]);
				} else if (strlen($this->noTCAFieldArray[$field])) {
					$tableName = $this->getName();
				} else {
					$tableName = '';
				}

				$fieldTmp = '';
				if (strlen($tableName)) {
					$fieldTmp = $this->aliasArray[$tableName] . $aliasPostfix . '.' . $field;
				} else {
					$fieldTmp = $field;
				}

				$resultArray[] = ($function ? $function . '('  : '' ) . $fieldTmp . ($bracketEndPos ? ')' : '') . ($order ? ' ' . $order : '');
			}
			$result = implode (',', $resultArray);
		}

		return $result;
	}


	/**
	 * Returns the table names which are used in addition to the main table
	 *
	 * @param 	string		exclude table
	 * @return	string		table names with aliases separated by comma
	 */
	public function getAdditionalTables ($excludeArray = array()) {

		if ($this->needsInit()) {
			return FALSE;
		}

		$resultArray = array();
		$result = '';

		if (count($this->langArray)) {
			// nothing
		} else {
			foreach ($this->aliasArray as $table => $alias) {

				if ($table != $this->getName() && !in_array($table, $excludeArray))
					$resultArray[] = $table . ' ' . $alias;
			}

			if (count($resultArray) > 1) {
				$result = implode (',', $resultArray);
			} else if (count($resultArray) == 1) {
				$result = $resultArray[0];
			}
		}
		return $result;
	}


	/**
	 * Returns the tables for the SQL select clause with the correct table alias names and all used tables
	 *
	 * @param 	string		string to form the JOIN command
	 * @return	string		table names with aliases separated by comma
	 */
	public function transformTable (
		$tables,
		$bJoinFound,
		&$join,
		$aliasPostfix = '',
		$fallback = FALSE
	) {
		if ($this->needsInit()) {
			return FALSE;
		}

		$theName = $this->getName();
		$bTableFound = FALSE;
		if (!$bJoinFound && strstr($tables, $theName)) {
			$bTableFound = TRUE;
		}

		$resultArray = array();
		$result = '';
		$joinArray = array();

		foreach ($this->aliasArray as $table => $alias) {
			if ($table != $theName || !$bTableFound) {
				$resultArray[] = $table . ' ' . $alias . $aliasPostfix;

				if ($this->foreignUidArray[$table] && $table != $theName) {
					$joinArray[] =
						$this->aliasArray[$theName] .
						'.uid = ' . $this->aliasArray[$table] . $aliasPostfix . '.' . $this->foreignUidArray[$table];
				}
			}
		}

		if (count($resultArray) > 1) {
			$result = implode(',', $resultArray);
		} else if (!$bJoinFound && !$bTableFound) {
			$result = $resultArray['0'];
		}

		if (count($joinArray)) {
			$join = implode(' AND ', $joinArray) . ' AND ';
		}
		return $result;
	}


	/**
	 * Returns the tables for the SQL select clause with the correct table alias names and all used tables
	 *
	 * @param 	string		string to form the JOIN command
	 * @return	string		table names with aliases separated by comma
	 */
	public function transformRow (
		&$row,
		$extKey
	) {
		$tablename = $this->getName();

			// Call all changeBasket hooks
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey][$tablename]['transformRow'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey][$tablename]['transformRow'] as $classRef) {
				$hookObj= t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'transformRow')) {
					$hookObj->transformRow($this, $row);
				}
			}
		}
	}


	/**
	 * Creates and executes an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
	 * Using this function specifically allows us to handle BLOB and CLOB fields depending on DB
	 * Usage count/core: 47
	 *
	 * @param	string		Table name
	 * @param	array		Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$insertFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param	string/array	See fullQuoteArray()
	 * @param	boolean		check if the count of fields is equal to $this->newFieldArray
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	public function exec_INSERTquery (
		$pid,
		$fields_values,
		$no_quote_fields = FALSE,
		$bCheckCount = TRUE
	) {
		$result = TRUE;

		if ($this->needsInit()) {
			return FALSE;
		}
		$fieldsArray = array();
		$fieldsArray['pid'] = $pid;
		$fieldsArray['tstamp'] = time();
		$fieldsArray['crdate'] = time();
		$fieldsArray['deleted'] = 0;
		$tablename = $this->getName();
		if ($bCheckCount && (count ($fields_values) == count($this->newFieldArray))) {
			$count = 0;
			foreach ($this->newFieldArray as $k => $field) {
				$fieldsArray[$field] = $fields_values[$count++];
			}
			$GLOBALS['TYPO3_DB']->exec_INSERTquery($tablename, $fieldsArray, $no_quote_fields);
		} else if (!$bCheckCount) {
			$fieldsArray = array_merge($fieldsArray, $fields_values);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery($tablename, $fieldsArray, $no_quote_fields);
		} else {
			$result = FALSE;
		}
		return $result;
	}


	/**
	 * Creates and executes a DELETE SQL-statement for $table where $where-clause
	 * Usage count/core: 40
	 *
	 * @param	string		WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	public function exec_DELETEquery ($where) {

		$tablename = $this->getName();
		$GLOBALS['TYPO3_DB']->exec_DELETEquery($tablename, $where);
	}


	/**
	 * Creates and executes a SELECT SQL-statement
	 * Using this function specifically allow us to handle the LIMIT feature independently of DB.
	 *
	 * @param	string		List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @param	string		Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @param	string		Optional FROM parts to be able to put a JOIN inside
	 * @param	string		postfix for the alias
	 * @param	boolean		FALLBACK
	 * @param	array		The collation configuration properties: field name as key and collation as value e.g. array('title' => 'utf8_bin');
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	public function exec_SELECTquery (
		$select_fields,
		$where_clause,
		$groupBy = '',
		$orderBy = '',
		$limit = '',
		$from = '',
		$aliasPostfix = '',
		$fallback = FALSE,
		$collateConf = array()
	) {
		$tables = '';

		if ($this->needsInit()) {
			return FALSE;
		}

		$bJoinFound = FALSE;
		if (strpos($from, $this->getName()) !== FALSE) {
			$tables = $from;
		}

		if (strpos($from, 'JOIN') !== FALSE) {
			$bJoinFound = TRUE;
		}

		$join = '';
		$joinTables =
			$this->transformTable(
				$tables,
				$bJoinFound,
				$join,
				$aliasPostfix,
				$fallback
			);
		$joinTableArray = array();
		if (strpos($joinTables, ',') !== FALSE) {
			$joinTableArray = t3lib_div::trimExplode(',', $joinTables);
		}
		$bAllTablesIncluded = TRUE;
		$excludedArray = array();

		if ($tables != '') {
			foreach ($joinTableArray as $joinTable) {
				if ($joinTable != '' && strpos($tables, $joinTable) === FALSE) {
					$bAllTablesIncluded = FALSE;
					$excludedArray[] = $joinTable;
				}
			}
		}

		if (
			!$from ||
			!$bAllTablesIncluded
		) { // the from fields do not already contain all aliases
			$tableArray = array();
			if ($tables != '') {
				$tableArray = t3lib_div::trimExplode(',', $tables);
				if ($excludedArray) {
					$tableArray = array_merge($tableArray, $excludedArray);
				}
				$tables = implode(',', $tableArray);
			}
		}

		$joinTableArray = array();
		$joinFallback = '';

		if ($tables == '') {
			if ($fallback && strpos($joinTables, ' LEFT JOIN ') === FALSE) {
				$joinTableArray = t3lib_div::trimExplode(',', $joinTables);
				if (count($joinTableArray) == 2) {
					$tables = $joinTableArray['0'] . ' LEFT JOIN ' . $joinTableArray['1'] . ' ON ';
					$joinFallback = $join;
					if (($pos = strpos($joinFallback, ' AND')) !== FALSE) {
						$joinFallback = substr($joinFallback, 0, $pos);
					}
					$join = '';
				}
			}/* else {
				$tables = $joinTables;
			}*/
			if ($tables == '') {
				$tables = $joinTables;
			}
		}

		if ($fallback) {
			$select_fields = $this->transformSelect($select_fields, $aliasPostfix, $collateConf);
		}
		$where_clause =
			$join .
			$this->transformWhere(
				$where_clause,
				$aliasPostfix,
				$joinFallback,
				$joinTableArray
			);
		$groupBy = $this->transformOrderby($groupBy, $aliasPostfix);
		$orderBy = $this->transformOrderby($orderBy, $aliasPostfix);

		if ($joinFallback != '') {
			$tables .= ' ' . $joinFallback;
		}
		$res =
			$GLOBALS['TYPO3_DB']->exec_SELECTquery(
				$select_fields,
				$tables,
				$where_clause,
				$groupBy,
				$orderBy,
				$limit
			);
		return $res;
	}


	/**
	 * Creates and returns a SELECT query for records from $table and with conditions based on the configuration in the $conf array
	 * The function will return the query not as a string but array with the various parts.
	 *
	 * @param	array		The TypoScript configuration properties
	 * @param	string		List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @param	string		Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @param	string		postfix for the alias
	 * @param	boolean		FALLBACK
	 * @param	array		The collation configuration properties: field name as key and collation as value e.g. array('title' => 'utf8_bin');
	 * @return	mixed		The SELECT query in an array as parts.
	 * @access public
	 */
	public function getQuery (
		$select_fields,
		$where_clause,
		$groupBy = '',
		$orderBy = '',
		$limit = '',
		$aliasPostfix = '',
		$fallback = FALSE,
		$collateConf = array()
	) {
		if ($this->needsInit()) {
			return FALSE;
		}

		$join = '';
		$tables =
			$this->transformTable(
				$tables,
				FALSE,
				$join,
				$aliasPostfix,
				$fallback
			);
		$select_fields =
			$this->transformSelect(
				$select_fields,
				$aliasPostfix,
				$collateConf
			);
		$where_clause = $join . $this->transformWhere($where_clause);
		$groupBy = $this->transformOrderby($groupBy);
		$orderBy = $this->transformOrderby($orderBy);

		$queryParts = array();
		$queryParts['FROM'] = $tables;
		$queryParts['SELECT'] = $select_fields;
		$queryParts['WHERE'] = $where_clause;
		$queryParts['GROUPBY'] = $groupBy;
		$queryParts['ORDERBY'] = $orderBy;
		$queryParts['LIMIT'] = $limit;

		return $queryParts;
	}


	/**
	 * Returns a select query array on input query parts array
	 *
	 * @param	array		Query parts array
	 * @param	string		postfix for the alias
	 * @param	array		The collation configuration properties: field name as key and collation as value e.g. array('title' => 'utf8_bin');
	 * @return	pointer		MySQL select result pointer / DBAL object
	 * @see getQuery()
	 */
	public function getQueryArray (
		$queryParts,
		$aliasPostfix = '',
		$collateConf = array()
	) {
		if ($this->needsInit()) {
			return FALSE;
		}

		$queryParts =
			$this->getQuery(
				$queryParts['SELECT'],
				$queryParts['WHERE'],
				$queryParts['GROUPBY'],
				$queryParts['ORDERBY'],
				$queryParts['LIMIT'],
				$aliasPostfix,
				$collateConf
			);
		return $queryParts;
	}


	/**
	 * Executes a select based on input query parts array
	 *
	 * Usage: 9
	 *
	 * @param	array		Query parts array
	 * @param	string		postfix for the alias
	 * @param	boolean		FALLBACK
	 * @param	array		The collation configuration properties: field name as key and collation as value e.g. array('title' => 'utf8_bin');
	 * @return	pointer		MySQL select result pointer / DBAL object
	 * @see exec_SELECTquery()
	 */
	public function exec_SELECT_queryArray (
		$queryParts,
		$aliasPostfix = '',
		$fallback = FALSE,
		$collateConf = array()
	) {
		if ($queryParts['FROM'] == '') {
			$queryParts['FROM'] = $this->getName();
		}
		$res = $this->exec_SELECTquery(
				$queryParts['SELECT'],
				$queryParts['WHERE'],
				$queryParts['GROUPBY'],
				$queryParts['ORDERBY'],
				$queryParts['LIMIT'],
				$queryParts['FROM'],
				$aliasPostfix,
				$fallback,
				$collateConf
		);
		return $res;
	}


	/**
	 * Creates and returns a SELECT query for records from $table and with conditions based on the configuration in the $conf array
	 * Implements the "select" function in TypoScript
	 *
	 * @param	object		cObject
	 * @param	string		The table names
	 * @param	array		The TypoScript configuration properties
	 * @param	boolean		If set, the function will return the query not as a string but array with the various parts. RECOMMENDED!
	 * @return	mixed		A SELECT query if $returnQueryArray is FALSE, otherwise the SELECT query in an array as parts.
	 * @access private
	 * @see CONTENT(), numRows()
	 */
	public function getQueryConf (
		$cObj,
		$table,
		$conf,
		$returnQueryArray = FALSE
	) {
		if ($this->needsInit()) {
			return FALSE;
		}
		$result = '';

			// Resolve stdWrap in these properties first
		$properties = array(
			'pidInList', 'uidInList', 'languageField', 'selectFields', 'max', 'begin', 'groupBy', 'orderBy', 'join', 'leftjoin', 'rightjoin'
		);
		foreach ($properties as $property) {
			$conf[$property] = isset($conf[$property . '.'])
					? trim($this->stdWrap($conf[$property], $conf[$property . '.']))
					: trim($conf[$property]);
			if ($conf[$property] === '') {
				unset($conf[$property]);
			}
			if (isset($conf[$property . '.'])) {
					// stdWrapping already done, so remove the sub-array
				unset($conf[$property . '.']);
			}
		}
		$conf['pidInList'] = trim($cObj->stdWrap($conf['pidInList'], $conf['pidInList.']));

			// Handle PDO-style named parameter markers first
		$queryMarkers = $cObj->getQueryMarkers($table, $conf);

			// replace the markers in the non-stdWrap properties
		foreach ($queryMarkers as $marker => $markerValue) {
			$properties = array(
				'uidInList', 'selectFields', 'where', 'max', 'begin', 'groupBy', 'orderBy', 'join', 'leftjoin', 'rightjoin'
			);
			foreach ($properties as $property) {
				if ($conf[$property]) {
					$conf[$property] = str_replace('###' . $marker . '###', $markerValue, $conf[$property]);
				}
			}
		}

			// Construct WHERE clause:

			// Handle recursive function for the pidInList
		if (
			isset($conf['recursive']) &&
			strcmp($conf['pidInList'], '-1') != 0
		) {
			$conf['recursive'] = intval($conf['recursive']);
			if ($conf['recursive'] > 0) {
				$pidList = '';
				foreach (explode(',', $conf['pidInList']) as $value) {
					if ($value === 'this') {
						$value = $GLOBALS['TSFE']->id;
					}
					$pidList .= $value . ',' . $cObj->getTreeList($value, $conf['recursive']);
				}
				$conf['pidInList'] = trim($pidList, ',');
			}
		}

		if (!strcmp($conf['pidInList'], '')) {
			$conf['pidInList'] = 'this';
		}
// 		if (!strcmp($conf['pidInList'], '-1')) {
// 			unset($conf['pidInList']);
// 		}

		$queryParts = $this->getWhere($cObj, $table, $conf, TRUE);
		if ($queryParts === FALSE) {
			return FALSE;
		}

 		$queryParts['SELECT'] = $conf['selectFields'] ? $conf['selectFields'] : '*';

			// Setting LIMIT:
		if ($conf['max'] || $conf['begin']) {
			$error = 0;

			// Finding the total number of records, if used:
			if (strstr(strtolower($conf['begin'] . $conf['max']), 'total')) {
				$res =
					$GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'count(*)',
						$table,
						$queryParts['WHERE'],
						$queryParts['GROUPBY']
					);
				if ($error = $GLOBALS['TYPO3_DB']->sql_error()) {
					$GLOBALS['TT']->setTSlogMessage($error);
				} else {
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
					$conf['max'] = str_ireplace('total', $row[0], $conf['max']);
					$conf['begin'] = str_ireplace('total', $row[0], $conf['begin']);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}

			if (!$error) {
				$begin = ceil($cObj->calc($conf['begin']));
				$conf['begin'] = tx_div2007_core::intInRange($begin, 0);
				$max = ceil($cObj->calc($conf['max']));
				$conf['max'] = tx_div2007_core::intInRange($max, 0);

				if ($conf['begin'] && !$conf['max']) {
					$conf['max'] = 100000;
				}

				if ($conf['begin'] && $conf['max']) {
					$queryParts['LIMIT'] = $conf['begin'] . ',' . $conf['max'];
				} elseif (!$conf['begin'] && $conf['max']) {
					$queryParts['LIMIT'] = $conf['max'];
				}
			}
		}

		if (!$error) {

				// Setting up tablejoins:
			$joinPart = '';
			if ($conf['join']) {
				$joinPart = 'JOIN ' . $conf['join'];
			} elseif ($conf['leftjoin']) {
				$joinPart = 'LEFT OUTER JOIN ' . $conf['leftjoin'];
			} elseif ($conf['rightjoin']) {
				$joinPart = 'RIGHT OUTER JOIN ' . $conf['rightjoin'];
			}

				// Compile and return query:
			$fromTable = $table.' ' . $this->aliasArray[$table];
			$queryParts['FROM'] = trim($fromTable . ' ' . $joinPart) . ($conf['from'] ? ',' . $conf['from']  : '');

				// replace the markers in the queryParts to handle stdWrap
				// enabled properties
			foreach ($queryMarkers as $marker => $markerValue) {
				foreach ($queryParts as $queryPartKey => &$queryPartValue) {
					$queryPartValue = str_replace('###' . $marker . '###', $markerValue, $queryPartValue);
				}
				unset($queryPartValue);
			}

			$query = $GLOBALS['TYPO3_DB']->SELECTquery(
				$queryParts['SELECT'],
				$queryParts['FROM'],
				$queryParts['WHERE'],
				$queryParts['GROUPBY'],
				$queryParts['ORDERBY'],
				$queryParts['LIMIT']
			);

			$result = $returnQueryArray ? $queryParts : $query;
		}
		return $result;
	}


	/**
	 * Helper function for getQuery(), creating the WHERE clause of the SELECT query
	 *
	 * @param	string		The table name
	 * @param	array		The TypoScript configuration properties
	 * @param	boolean		If set, the function will return the query not as a string but array with the various parts. RECOMMENDED!
	 * @return	mixed		A WHERE clause based on the relevant parts of the TypoScript properties for a "select" function in TypoScript, see link. If $returnQueryArray is FALSE the where clause is returned as a string with WHERE, GROUP BY and ORDER BY parts, otherwise as an array with these parts.
	 * @access private
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=318&cHash=a98cb4e7e6
	 * @see getQuery()
	 */
	public function getWhere (
		$cObj,
		$table,
		$conf,
		$returnQueryArray = FALSE
	) {
		if ($this->needsInit()) {
			return FALSE;
		}

		if (!$table) {
			return FALSE;
		}

			// Init:
		$query = '';
		$pid_uid_flag = 0;
		$queryParts = array(
			'SELECT' => '',
			'FROM' => '',
			'WHERE' => '',
			'GROUPBY' => '',
			'ORDERBY' => '',
			'LIMIT' => ''
		);

		if (trim($conf['uidInList'])) {
			if (TYPO3_MODE == 'FE') {
				$listArr = t3lib_div::intExplode(',', str_replace('this', $GLOBALS['TSFE']->contentPid, $conf['uidInList']));
			}
			if (count($listArr) == 1) {
				$query.=' AND '.$this->aliasArray[$table] . '.uid=' . intval($listArr[0]);
			} else {
				$query.=' AND '.$this->aliasArray[$table] . '.uid IN (' . implode(',', $GLOBALS['TYPO3_DB']->cleanIntArray($listArr)) . ')';
			}
			$pid_uid_flag++;
		}

		if (
			!strcmp($conf['pidInList'], '-1')
		) {
			$pid_uid_flag = -1; // allow to show the records from all pages
		} else if (
			trim($conf['pidInList'])
		) {
			if (TYPO3_MODE == 'FE') {
				$listArr = t3lib_div::intExplode(',', str_replace('this', $GLOBALS['TSFE']->contentPid, $conf['pidInList']));
				$listArr = $cObj->checkPidArray($listArr);
			}

			if (count($listArr)) {
				$query.=' AND ' . $this->aliasArray[$table] . '.pid IN (' . implode(',', $GLOBALS['TYPO3_DB']->cleanIntArray($listArr)) . ')';
				$pid_uid_flag++;
			} else {
				$pid_uid_flag = 0;		// If not uid and not pid then uid is set to 0 - which results in nothing!!
			}
		}

		if (!$pid_uid_flag) {		// If not uid and not pid then uid is set to 0 - which results in nothing!!
			$query.=' AND ' . $this->aliasArray[$table] . '.uid=0';
		}

		if ($where = trim($conf['where'])) {
			$query.=' AND ' . $where;
		}

		if (
			(TYPO3_MODE == 'FE') &&
			$conf['languageField']
		) {
			if (
				$GLOBALS['TSFE']->sys_language_contentOL &&
				$GLOBALS['TCA'][$table] &&
				$GLOBALS['TCA'][$table]['ctrl']['languageField'] &&
				$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
			) {
					// Sys language content is set to zero/-1 - and it is expected that whatever routine processes the output will OVERLAY the records with localized versions!
				$sys_language_content = '0,-1';
			} else {
				$sys_language_content = intval($GLOBALS['TSFE']->sys_language_content);
			}
			$query.=' AND ' . $conf['languageField'] . ' IN (' . $sys_language_content . ')';
		}

		$andWhere = trim($cObj->stdWrap($conf['andWhere'], $conf['andWhere.']));
		if ($andWhere) {
			$query .= ' AND ' . $andWhere;
		}

			// enablefields
		if (
			TYPO3_MODE == 'FE' &&
			$table == 'pages'
		) {
			$query .= ' ' . $GLOBALS['TSFE']->sys_page->where_hid_del .
				$GLOBALS['TSFE']->sys_page->where_groupAccess;
		} else {
			$query .= $this->enableFields();
		}

			// MAKE WHERE:
		if ($query) {
			$queryParts['WHERE'] = trim(substr($query, 4));	// Stripping of " AND"...
			$query = 'WHERE ' . $queryParts['WHERE'];
		}

			// GROUP BY
		if (trim($conf['groupBy'])) {
			$queryParts['GROUPBY'] = trim($conf['groupBy']);
			$query.=' GROUP BY ' . $queryParts['GROUPBY'];
		}

			// ORDER BY
		if (trim($conf['orderBy'])) {
			$queryParts['ORDERBY'] = trim($conf['orderBy']);
			$query.=' ORDER BY ' . $queryParts['ORDERBY'];
		}

			// Return result:
		return $returnQueryArray ? $queryParts : $query;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/table/lib/class.tx_table_db.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/table/lib/class.tx_table_db.php']);
}

