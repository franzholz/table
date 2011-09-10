<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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

require_once(PATH_t3lib.'class.t3lib_div.php');

class tx_table_db {
	public $tableFieldArray; // array of fields for each table
	public $defaultFieldArray = array('uid'=>'uid', 'pid'=>'pid', 'tstamp'=>'tstamp', 'crdate'=>'crdate',
			'deleted' => 'deleted'); // TYPO3 default fields
	public $noTCAFieldArray = array('cruser_id'=>'cruser_id',
				't3ver_oid' => 't3ver_oid', 't3ver_id' => 't3ver_id', 't3ver_label' => 't3ver_label', 'sorting' => 'sorting',
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
	public function init ($table, $tableAlias='', $tableFieldArray=array()) {
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


	public function getAlias () {
		$name = $this->getName();
		return $this->aliasArray[$name];
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
		$rc = $field;
		$fieldArray = $this->tableFieldArray[$field];
		if (isset($fieldArray) && is_array($fieldArray)) {
			$rc = current($fieldArray);
		}
		return $rc;
	}


	public function initFile ($filename, &$retLangArray, $keyWrapArray = array()) {
		if (@is_file($filename) && t3lib_div::validPathStr($filename)) {
			if (intval(phpversion()) == 5) {
				$line = file_get_contents($filename);
				if ($line === FALSE) {
					break;
				}
				$tokenArray = preg_split('/[\n|\r|\f]+/', $line);

				foreach ($tokenArray as $k => $tokenRow) {
					$langArray = t3lib_div::trimExplode(';', $tokenRow);
					if ($langArray[0] != '') {
						$retLangArray[$keyWrapArray[0].$langArray[0].$keyWrapArray[1]] = $langArray[1];
					}
				}
			} else {
				$langFile = fopen($filename, 'rb');
				while (!feof($langFile)) {
					$line = fgets($langFile, 4096);
					$langArray = t3lib_div::trimExplode(';', $line);
					if (count($langArray) == 2 && $langArray[0] != '') {
						$retLangArray[$keyWrapArray[0].$langArray[0].$keyWrapArray[1]] = $langArray[1];
					}
				}
			}
		}
	}


	public function getMarkerArray () {
		return $this->markerArray;
	}


	public function substituteMarkerArray (&$row, $excludeFieldArray=array()) {

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
		$this->initFile($filename, $this->markerArray, array('###','###'));
	}


	public function setColumnPrefix ($prefix) {
		$this->columnPrefix = $prefix;
	}


	// use setTCAFieldArray instead of this
	public function setTableFieldArray ($table, $tableAlias='', $fieldArray) {
		$this->aliasArray[$table] = ($tableAlias ? $tableAlias : $table);
		foreach ($fieldArray as $fieldbase => $field) {
			$this->tableFieldArray[$fieldbase] = array ($table => $field);
		}
	}


	public function getRequiredFieldArray () {
		return $this->requiredFieldArray;
	}


	public function setRequiredFieldArray ($fieldArray=array()) {
		$this->requiredFieldArray = $fieldArray;
	}


	public function addRequiredFieldArray ($fieldArray=array()) {
		$this->requiredFieldArray = array_merge($this->requiredFieldArray, $fieldArray);
	}


	public function getDefaultFieldArray () {
		return $this->defaultFieldArray;
	}


	public function setDefaultFieldArray ($defaultFieldArray=array()) {
		if (isset($this->defaultFieldArray) && is_array($this->defaultFieldArray)) {
			foreach ($this->defaultFieldArray as $field => $realField) {
				if (isset($this->tableFieldArray[$field])) {
					unset($this->tableFieldArray[$field]);
				}
			}
		}
		$this->defaultFieldArray = $defaultFieldArray;
	}


	public function addDefaultFieldArray ($defaultFieldArray=array()) {
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


	public function getTCA ($part, $field='') {
		global $TCA;

		$table = $this->getName();
		t3lib_div::loadTCA($table);
		if (is_array($TCA[$table]) && is_array($TCA[$table][$part])) {
			if ($field) {
				$rc = $TCA[$table][$part][$field];
			} else {
				$rc = $TCA[$table][$part];
			}
		}
		return $rc;
	}


	public function getLangTCA ($part, $field='') {
		global $TCA;

		$table = $this->langname;
		t3lib_div::loadTCA($table);
		if (is_array($TCA[$table]) && is_array($TCA[$table][$part])) {
			if ($field) {
				$rc = $TCA[$table][$part][$field];
			} else {
				$rc = $TCA[$table][$part];
			}
		}
		return $rc;
	}


	public function bFieldExists ($field) {
		$field = $this->getField($field);
		$fieldTca = &$this->getTCA('columns' ,$field);
		return (isset($fieldTca));
	}


	/* must be called after setTCAFieldArray */
	public function setNoTCAFieldArray ($table, $fieldArray) {
		foreach ($fieldArray as $key => $field) {
			$this->tableFieldArray[$field] = array ($table => $field);
		}
	}


	public function getForeignUidArray ($table='') {
		if ($table) {
			$rc = $this->foreignUidArray[$table];
		} else {
			$rc = $this->foreignUidArray;
		}
		return $rc;
	}


	public function setForeignUidArray ($table, $field) {
		$this->foreignUidArray[$table] = $field;
	}


	public function setTCAFieldArray ($table, $tableAlias='', $bSetTablename=TRUE) {
		global $TCA, $TSFE;

		if ($table != '') {
			$dummy1 = $this->aliasArray; // PHP 5.2.1 needs this
			$dummy2 = $this->defaultFieldArray; // PHP 5.2.1 needs this
			$dummy3 = $this->foreignUidArray; // PHP 5.2.1 needs this
			$dummy4 = $this->requiredFieldArray; // PHP 5.2.1 needs this
			$dummy5 = $this->tableFieldArray; // PHP 5.2.1 needs this

			if ($bSetTablename && $table != $this->getName() && $table != $this->getLangName()) {
				$this->setName($table);
			}

			$tmp = ($tableAlias ? $tableAlias : $table);
			$this->aliasArray[$table] = $tmp;
			t3lib_div::loadTCA($table);
			reset($this->aliasArray);
			$tmp = key($this->aliasArray);

			if (is_array($this->defaultFieldArray)) {

				foreach ($this->defaultFieldArray as $field => $realField) {
					if ($field != 'uid' && (!is_array($this->foreignUidArray) || !in_array($field,$this->foreignUidArray)) || $table == key($this->aliasArray) ) {
						$this->tableFieldArray[$field] = array ($table => $realField);
					}
					if ($field == 'uid') {
						// nothing yet
					}
				}
			}

			$theName = $this->getName();
			if ($TCA[$table]['columns']) {
				foreach ($TCA[$table]['columns'] as $field => $fieldArray) {
					$this->tableFieldArray[$field] = array ($table => $field);
						// is there a foreign key to the first table?
					if (($fieldArray['config']['type'] == 'select' || $fieldArray['config']['type'] == 'group') && ($foreignTable = $fieldArray['config']['foreign_table']) != '') {
						$this->setForeignUidArray($table,$field);
					}
				}
			}

			if (is_array($this->requiredFieldArray) && count($this->requiredFieldArray)) {

				foreach ($this->requiredFieldArray as $k => $field) {
					if ($field && !isset($this->tableFieldArray[$field]) && $field != 'uid') {

						$this->tableFieldArray[$field] = array ($table => $field);
					}
					if ($field == 'uid') {
						// nothing yet
					}
				}
			}

			$this->bNeedsInit = FALSE;
		} else {
			$tmp = t3lib_div::debug_trail();
			t3lib_div::debug($tmp);
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
	public function getEnableFieldArray ($show_hidden=-1,$ignore_array=array(),$table='') {
		global $TYPO3_CONF_VARS;

		if ($this->needsInit()) {
			return FALSE;
		}
		if (!$table) {
			$table = $this->getName();
		}
		$aliasTable = (isset($this->aliasArray[$table]) ? $this->aliasArray[$table] : $table);

		if ($show_hidden==-1 && is_object($GLOBALS['TSFE'])) {	// If show_hidden was not set from outside and if TSFE is an object, set it based on showHiddenPage and showHiddenRecords from TSFE
			$show_hidden = $table=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords;
		}
		if ($show_hidden==-1) {
			$show_hidden=0;	// If show_hidden was not changed during the previous evaluation, do it here.
		}

		$ctrl = $GLOBALS['TCA'][$table]['ctrl'];
		$fieldArray=array();
		if (is_array($ctrl)) {
			if ($ctrl['delete']) {
				$query.=' AND '.$aliasTable.'.'.$ctrl['delete'].'=0';
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
						$addFieldArray = t3lib_div::callUserFunction($_funcRef,$_params,$this);
						if (isset($addFieldArray) && is_array($addFieldArray)) {
							$fieldArray = array_merge($fieldArray, $addFieldArray);
						}
					}
				}
			}
		} else {
			$tmp = t3lib_div::debug_trail();
			t3lib_div::debug($tmp);
			die ('NO entry in the $TCA-array for the table "'.$table.'". This means that the function enableFields() is called with an invalid table name as argument.');
		}
		$fieldArray = array_unique($fieldArray);
		return $fieldArray;
	}


	/**
	 * Returns a part of a WHERE clause which will filter out records with start/end times or hidden/fe_groups fields set to values that should de-select them according to the current time, preview settings or user login. Definitely a frontend function.
	 * Is using the $TCA arrays "ctrl" part where the key "enablefields" determines for each table which of these features applies to that table.
	 * The alias table name gets used
	 *
	 * @param	integer		If $show_hidden is set (0/1), any hidden-fields in records are ignored. NOTICE: If you call this function, consider what to do with the show_hidden parameter. Maybe it should be set? See tslib_cObj->enableFields where it's implemented correctly.
	 * @param	array		Array you can pass where keys can be "disabled", "starttime", "endtime", "fe_group" (keys from "enablefields" in TCA) and if set they will make sure that part of the clause is not added. Thus disables the specific part of the clause. For previewing etc.
	 * @param	string 		table name (optional)
	 * @return	string		The clause starting like " AND ...=... AND ...=..." is as well set internally.
	 * @see tslib_cObj::enableFields(), deleteClause()
	 */
	public function enableFields ($aliasPostfix='',$show_hidden=-1,$ignore_array=array(),$table='') {
		global $TYPO3_CONF_VARS;

		if ($this->needsInit()) {
			return FALSE;
		}
		if (!$table) {
			$table = $this->getName();
		}
		$aliasTable = (isset($this->aliasArray[$table]) ? $this->aliasArray[$table].$aliasPostfix : $table);

		if ($show_hidden==-1 && is_object($GLOBALS['TSFE'])) {	// If show_hidden was not set from outside and if TSFE is an object, set it based on showHiddenPage and showHiddenRecords from TSFE
			$show_hidden = $table=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords;
		}
		if ($show_hidden==-1) {
			$show_hidden=0;	// If show_hidden was not changed during the previous evaluation, do it here.
		}

		$ctrl = $GLOBALS['TCA'][$table]['ctrl'];
		$query='';
		if (is_array($ctrl)) {
			if ($ctrl['delete']) {
				$query.=' AND '.$aliasTable.'.'.$ctrl['delete'].'=0';
			}

			if (is_array($ctrl['enablecolumns'])) {
				if ($ctrl['enablecolumns']['disabled'] && !$show_hidden && !$ignore_array['disabled']) {
					$field = $aliasTable.'.'.$ctrl['enablecolumns']['disabled'];
					$query.=' AND '.$field.'=0';
				}
				if ($ctrl['enablecolumns']['starttime'] && !$ignore_array['starttime']) {
					$field = $aliasTable.'.'.$ctrl['enablecolumns']['starttime'];
					$query.=' AND ('.$field.'<='.$GLOBALS['SIM_EXEC_TIME'].')';
				}
				if ($ctrl['enablecolumns']['endtime'] && !$ignore_array['endtime']) {
					$field = $aliasTable.'.'.$ctrl['enablecolumns']['endtime'];
					$query.=' AND ('.$field.'=0 OR '.$field.'>'.$GLOBALS['SIM_EXEC_TIME'].')';
				}
				if ($ctrl['enablecolumns']['fe_group'] && !$ignore_array['fe_group']) {
					$field = $aliasTable.'.'.$ctrl['enablecolumns']['fe_group'];
					$gr_list = $GLOBALS['TSFE']->gr_list;
					if (!strcmp($gr_list,''))	$gr_list=0;
					$query.=' AND '.$field.' IN (\' \','.$gr_list.')';
				}

					// Call hook functions for additional enableColumns
					// It is used by the extension ingmar_accessctrl which enables assigning more than one usergroup to content and page records
				if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_page.php']['addEnableColumns'])) {
					$_params = array(
						'table' => $table,
						'show_hidden' => $show_hidden,
						'ignore_array' => $ignore_array,
						'ctrl' => $ctrl
					);
					foreach($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_page.php']['addEnableColumns'] as $_funcRef) {
						$query .= t3lib_div::callUserFunction($_funcRef,$_params,$this);
					}
				}
			}
		} else {
			$tmp = t3lib_div::debug_trail();
			t3lib_div::debug($tmp);
			die ('NO entry in the $TCA-array for the table "'.$table.'". This means that the function enableFields() is called with an invalid table name as argument.');
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
	public function transformWhere ($clause, $aliasPostfix='') {

		if ($this->needsInit()) {
			return FALSE;
		}

		$dummy1 = $this->aliasArray; // PHP 5.2.1 needs this
		$dummy2 = $this->tableFieldArray; // PHP 5.2.1 needs this
		$rc = '';
		$rcArray = array();
		$bracketOpen = preg_split('/\(/',$clause);
		$bracketOpenArray = array();
		$bracketOpenOffset = '';

		foreach ($bracketOpen as $key => $part) {
			$part = $bracketOpenOffset . $part;
			$bracketOpenOffset = '';
			if (isset($part)) {
				if (($quotePos = strpos($part,'\'')) !== FALSE) {
					$quoteCount = 1;
					$len = strlen($part);
					while ($quotePos !== FALSE && $quotePos < $len-1) {
						$quotePos = strpos($part,'\'',$quotePos+1);
						if ($quotePos !== FALSE) {
							$quoteCount++;
						}
					}
					$halfQuoteCount = $quoteCount >> 1;
					$fullQuoteCount = $halfQuoteCount << 1;

					if ($quoteCount != $fullQuoteCount) {	// not even. This means that a open bracket ( has been inside of a quoted string
						$bracketOpenOffset = $part.'(';	// add this part to the next one
						continue;
					}
				}
				$bracketClose = preg_split('/\)/',$part);
				$bracketCloseArray = array();

				foreach ($bracketClose as $key2 => $part2) {

					if (isset($part2)) {
						$blank = preg_split('/ /',$part2, -1, PREG_SPLIT_NO_EMPTY);
						$blankArray = array();
						foreach ($blank as $key3 => $part3) {

							$chars = preg_split('//', $part3, -1, PREG_SPLIT_NO_EMPTY);
							$part3pre = '';
							$i = 0;
							while ($i < count($chars) && $chars[$i] != '<' && $chars[$i] != '>' && $chars[$i] != '=') {
								$part3pre .= $chars[$i];
								$i++;
							}
							if ($part3pre != '') {
								$part3prePos = strpos($part3pre,'.');
								if ($part3prePos !== FALSE)	{
									$part3preArray = explode('.',$part3pre);
									if ($part3preArray['0'] == $this->getName() || $part3preArray['0'] == $this->getAlias()) {
										$part3pre = $part3preArray['1'];
									}
								}
							}
							$part3comp = '';
							while ($i < count($chars) && ($chars[$i] == '<' || $chars[$i] == '>' || $chars[$i] == '=')) {
								$part3comp .= $chars[$i];
								$i++;
							}
							$part3post = '';
							while ($i < count($chars)) {
								$part3post .= $chars[$i];
								$i++;
							}
							if ($part3post != '') {
								$part3postPos = strpos($part3post,'.');
								if ($part3postPos !== FALSE) {
									$part3postArray = explode('.',$part3post);
									if ($part3postArray['0'] == $this->getName() || $part3postArray['0'] == $this->getAlias()) {
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

							$newBlank = $part3pre.$part3comp.$part3post;
							$blankArray[] = $newBlank;
						}
						$bracketCloseArray[] = implode (' ',$blankArray);
					} else {
						$bracketCloseArray[] = '';
					}
				} // foreach ($bracketClose ...
				$bracketOpenArray[] = implode (')', $bracketCloseArray);
			} else {
				$bracketOpenArray[] = '';
			}
		} // foreach ($bracketOpen ...
		$rc = implode ('(', $bracketOpenArray);

		$dummy = '';
		$this->transformLanguage($dummy, $rc);

		return $rc;
	}


	/**
	 * Adds the language table and where clause if a translation is needed.
	 *
	 * @param	string		from
	 * @param	string		where clause
	 * @param	boolean		TRUE, if the language table shall use the outer join
	 * @return	string		Select clause
	 */
	public function transformLanguage (&$table, &$where, $bUseJoin=FALSE) {
		global $TSFE;

			// set the language
		if ($this->getLanguage() && is_array($this->tableFieldArray) && is_array($this->tableFieldArray['sys_language_uid'])) {
			$tableField = $this->tableFieldArray['sys_language_uid'];
			$newWhere = ' AND ' . $this->aliasArray[key($tableField)] . '.' . current($this->tableFieldArray['sys_language_uid']) . '=' . $TSFE->config['config']['sys_language_uid'];
			$languageTable = $this->getLangName();

			if ($languageTable != '') {
				if (strpos($table,$languageTable)===FALSE)	{
					if ($bUseJoin && $table != '')	{
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
	 * @return	string		Select clause
	 */
	public function transformSelect ($clause,$aliasPostfix='') {
		if ($this->needsInit())	{
			return FALSE;
		}

		$rc = '';
		$rcArray = array();
		$dummy1 = $this->aliasArray; // PHP 5.2.1 needs this
		$dummy2 = $this->tableFieldArray; // PHP 5.2.1 needs this

		if (is_array($this->aliasArray) && count($this->aliasArray) && is_array($this->tableFieldArray))	{
			if ($clause == '*') {
				foreach ($this->tableFieldArray as $productsfield => $fieldArray) {
					foreach ($fieldArray as $table => $field) {
						$rcArray[] = $this->aliasArray[$table] . $aliasPostfix . '.' . $field . ' ' . $this->columnPrefix . $productsfield;
					}
				}

				if (is_array($this->requiredFieldArray) && count($this->requiredFieldArray))	{

					$table = $this->getName();
					foreach ($this->requiredFieldArray as $k => $field) {

						if ($field && !isset($this->tableFieldArray[$field]) && $field != 'uid')	{
							$rcArray[] = $this->aliasArray[$table] . $aliasPostfix . '.' . $field . ' ' . $this->columnPrefix . $field;
						}
					}
				}
				$rc = implode (',', $rcArray);
			} else if (strpos($clause,'count(')!==FALSE) {
				$rc = $clause;
			} else if ($clause == '') {
				// nothing
			} else {
				$fieldArray = t3lib_div::trimExplode(',', $clause);

				foreach ($fieldArray as $k => $field) {
					$bAddAlias = TRUE;
					if (is_array($this->tableFieldArray[$field])) {
						$table = key($this->tableFieldArray[$field]);
						$realField = $this->tableFieldArray[$field][$table];
					} else	{
						$table = $this->getName();
						$realField = $field;
						if (strpos($realField, ' ') !== FALSE) {
							$bAddAlias = FALSE;
						}
					}
					if ($bAddAlias) {
						$rcArray[] = $this->aliasArray[$table] . $aliasPostfix . '.' . $field . ' ' . $this->columnPrefix . $realField;
					} else {
						$rcArray[] = $realField;
					}
				}
				$rc = implode (',', $rcArray);
			}
		} else {
			$rc = 'error: wrong initialisation before call of transformSelect with '.$clause;
		}

		return $rc;
	}


	/**
	 * Returns the SQL orderby clause with the correct table alias names
	 *
	 * @param	string		List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @return	string		Select clause
	 */
	public function transformOrderby ($clause, $aliasPostfix='') {

		if ($this->needsInit()) {
			return FALSE;
		}

		$rc = '';
		if ($clause == '') {
			// nothing
		} else {
			$dummy1 = $this->tableFieldArray; // PHP 5.2.1 needs this
			$fieldArray = t3lib_div::trimExplode(',', $clause);

			foreach ($fieldArray as $k => $fieldExpression) {
				$spacePos = strpos ($fieldExpression,' ');
				if ($spacePos === FALSE) {
					$field = $fieldExpression;
					unset($order);
				} else {
					$field = substr($fieldExpression,0,$spacePos);
					$order = substr($fieldExpression, $spacePos);
				}
				$fieldArray = t3lib_div::trimExplode ('.', $field);

				// no table has been specified?
				if ((count($fieldArray) == 1) && isset($this->tableFieldArray[$field]) && is_array($this->tableFieldArray[$field])) { // TODO: check this
					$tableName = key($this->tableFieldArray[$field]);
				} else if (strlen($this->noTCAFieldArray[$field])) {
					$tableName = $this->getName();
				} else {
					$tableName = '';
				}
				if (strlen($tableName)) {
					$fieldTmp = $this->aliasArray[$tableName] . $aliasPostfix . '.' . $field;
				} else {
					$fieldTmp = $field;
				}

				$rcArray[] = $fieldTmp.($order ? ' '.$order : '');
			}
			$rc = implode (',', $rcArray);
		}
		return $rc;
	}


	/**
	 * Returns the table names which are used in addition to the main table
	 *
	 * @param 	string		exclude table
	 * @return	string		table names with aliases separated by comma
	 */
	public function getAdditionalTables ($excludeArray=array()) {

		if ($this->needsInit()) {
			return FALSE;
		}

		$rcArray = array();
		$rc = '';
		$dummy1 = $this->aliasArray; // PHP 5.2.1 needs this
		$dummy2 = $this->langArray; // PHP 5.2.1 needs this

		if (count($this->langArray)) {
			$rc = '';
		} else {
			foreach ($this->aliasArray as $table => $alias) {

				if ($table != $this->getName() && !in_array($table,$excludeArray))
					$rcArray[] = $table.' '.$alias;
			}

			if (count($rcArray) > 1) {
				$rc = implode (',', $rcArray);
			} else if (count($rcArray) == 1) {
				$rc = $rcArray[0];
			}
		}
		return $rc;
	}


	/**
	 * Returns the tables for the SQL select clause with the correct table alias names and all used tables
	 *
	 * @param 	string		string to form the JOIN command
	 * @return	string		table names with aliases separated by comma
	 */
	public function transformTable ($tables, $bJoinFound, &$join, $aliasPostfix='') {

		if ($this->needsInit()) {
			return FALSE;
		}

		$dummy1 = $this->aliasArray; // PHP 5.2.1 needs this
		$dummy2 = $this->foreignUidArray; // PHP 5.2.1 needs this
		$theName = $this->getName();

		$bTableFound = FALSE;
		if (!$bJoinFound && strstr($tables,$theName)) {
			$bTableFound = TRUE;
		}

		$rcArray = array();
		$rc = '';
		$joinArray = array();

		foreach ($this->aliasArray as $table => $alias) {
			if ($table != $theName || !$bTableFound) {
				$rcArray[] = $table.' '.$alias.$aliasPostfix;
				if ($this->foreignUidArray[$table] && $table != $theName) {
					$joinArray[] = $this->aliasArray[$theName] . '.uid = ' . $this->aliasArray[$table] . $aliasPostfix . '.'.$this->foreignUidArray[$table];
				}
			}
		}
		if (count($rcArray) > 1) {
			$rc = implode(',', $rcArray);
		} else if (!$bJoinFound && !$bTableFound) {
			$rc = $rcArray[0];
		}
		if (count ($joinArray)) {
			$join = implode(' AND ', $joinArray).' AND ';
		}
		return $rc;
	}


	/**
	 * Returns the tables for the SQL select clause with the correct table alias names and all used tables
	 *
	 * @param 	string		string to form the JOIN command
	 * @return	string		table names with aliases separated by comma
	 */
	public function transformRow (&$row, $extKey) {
		$tablename = $this->getName();

			// Call all changeBasket hooks
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey][$tablename]['transformRow'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey][$tablename]['transformRow'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
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
	public function exec_INSERTquery ($pid, $fields_values, $no_quote_fields = FALSE, $bCheckCount = TRUE) {
		global $TYPO3_DB;
		$rc = TRUE;

		if ($this->needsInit()) {
			return FALSE;
		}
		$dummy1 = $this->newFieldArray; // PHP 5.2.1 needs this
		$fieldsArray = array();
		$fieldsArray['pid']=$pid;
		$fieldsArray['tstamp']=time();
		$fieldsArray['crdate']=time();
		$fieldsArray['deleted']=0;
		$tablename = $this->getName();
		if ($bCheckCount && (count ($fields_values) == count($this->newFieldArray))) {
			$count = 0;
			foreach ($this->newFieldArray as $k => $field) {
				$fieldsArray[$field] = $fields_values[$count++];
			}
			$TYPO3_DB->exec_INSERTquery($tablename,$fieldsArray,$no_quote_fields);
		} else if (!$bCheckCount) {
			$fieldsArray = array_merge($fieldsArray, $fields_values);
			$TYPO3_DB->exec_INSERTquery($tablename,$fieldsArray,$no_quote_fields);
		} else {
			$rc = FALSE;
		}
		return $rc;
	}


	/**
	 * Creates and executes a DELETE SQL-statement for $table where $where-clause
	 * Usage count/core: 40
	 *
	 * @param	string		WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	public function exec_DELETEquery ($where) {
		global $TYPO3_DB;

		$tablename = $this->getName();
		$TYPO3_DB->exec_DELETEquery($tablename,$where);
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
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	public function exec_SELECTquery ($select_fields, $where_clause, $groupBy = '', $orderBy = '', $limit = '', $from = '', $aliasPostfix = '') {
		global $TYPO3_DB;

		if ($this->needsInit()) {
			return FALSE;
		}

		$bJoinFound = FALSE;
		if (strstr($from,$this->getName())) {
			$tables = $from;
		}

		if (strstr($from,'JOIN')) {
			$bJoinFound = TRUE;
		}

		$join = '';
		$joinTables = $this->transformTable($tables, $bJoinFound, $join, $aliasPostfix);
		if (!$from || strstr($from, $tables) === FALSE)	{ // the from fields already contain all aliases
			$tables = ($tables ? $tables :'').($tables!='' && $joinTables!='' ? ',' : '').($joinTables!='' ? $joinTables : '');
		}
		$tables = ($tables ? $tables : $joinTables);
		$select_fields = $this->transformSelect($select_fields,$aliasPostfix);
		$where_clause = $join . $this->transformWhere($where_clause,$aliasPostfix);
		$groupBy = $this->transformOrderby($groupBy,$aliasPostfix);
		$orderBy = $this->transformOrderby($orderBy,$aliasPostfix);

		$res = $TYPO3_DB->exec_SELECTquery($select_fields, $tables, $where_clause, $groupBy, $orderBy, $limit);
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
	 * @return	mixed		The SELECT query in an array as parts.
	 * @access public
	 */
	public function getQuery ($select_fields,$where_clause,$groupBy='',$orderBy='',$limit='') {
		global $TYPO3_DB;

		if ($this->needsInit()) {
			return FALSE;
		}

		$join = '';
		$tables = $this->transformTable($tables, FALSE, $join);
		$select_fields = $this->transformSelect($select_fields);
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
	 * Usage: 9
	 *
	 * @param	array		Query parts array
	 * @return	pointer		MySQL select result pointer / DBAL object
	 * @see getQuery()
	 */
	public function getQueryArray ($queryParts) {
		if ($this->needsInit()) {
			return FALSE;
		}

		$queryParts = $this->getQuery($queryParts['SELECT'], $queryParts['WHERE'], $queryParts['GROUPBY'], $queryParts['ORDERBY'], $queryParts['LIMIT']);
		return $queryParts;
	}


	/**
	 * Executes a select based on input query parts array
	 *
	 * Usage: 9
	 *
	 * @param	array		Query parts array
	 * @return	pointer		MySQL select result pointer / DBAL object
	 * @see exec_SELECTquery()
	 */
	public function exec_SELECT_queryArray ($queryParts) {
		if ($queryParts['FROM'] == '') {
			$queryParts['FROM'] = $this->getName();
		}
		$res = $this->exec_SELECTquery(
				$queryParts['SELECT'],
				$queryParts['WHERE'],
				$queryParts['GROUPBY'],
				$queryParts['ORDERBY'],
				$queryParts['LIMIT'],
				$queryParts['FROM']
		);
		return $res;
	}


	/**
	 * Creates and returns a SELECT query for records from $table and with conditions based on the configuration in the $conf array
	 * Implements the "select" function in TypoScript
	 *
	 * @param	string		The table names
	 * @param	array		The TypoScript configuration properties
	 * @param	boolean		If set, the function will return the query not as a string but array with the various parts. RECOMMENDED!
	 * @return	mixed		A SELECT query if $returnQueryArray is FALSE, otherwise the SELECT query in an array as parts.
	 * @access private
	 * @see CONTENT(), numRows()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=318&cHash=a98cb4e7e6
	 */
	public function getQueryConf (&$cObj, $table, $conf, $returnQueryArray=FALSE) {
		global $TYPO3_DB;

		$rc = '';
		if ($this->needsInit()) {
			return FALSE;
		}

		$dummy1 = $this->aliasArray; // PHP 5.2.1 needs this

		// $addTables = $this->getAdditionalTables();
		// $table = ($table && $addTables ? $table.','.$addTables : $table);
			// Construct WHERE clause:
		$conf['pidInList'] = trim($cObj->stdWrap($conf['pidInList'], $conf['pidInList.']));

		$queryParts = $this->getWhere($cObj, $table, $conf, TRUE);
		if ($queryParts === FALSE) {
			return FALSE;
		}

			// Fields:
		$queryParts['SELECT'] = $conf['selectFields'] ? $conf['selectFields'] : '*';

			// Setting LIMIT:
		if ($conf['max'] || $conf['begin']) {
			$error = 0;

				// Finding the total number of records, if used:
			if (strstr(strtolower($conf['begin'].$conf['max']),'total')) {
				$res = $TYPO3_DB->exec_SELECTquery('uid', $table, $queryParts['WHERE'], $queryParts['GROUPBY']);
				if ($error = $TYPO3_DB->sql_error()) {
					$GLOBALS['TT']->setTSlogMessage($error);
				} else {
					$total = $TYPO3_DB->sql_num_rows($res);
					$conf['max'] = eregi_replace('total', (string)$total, $conf['max']);
					$conf['begin'] = eregi_replace('total', (string)$total, $conf['begin']);
				}
			}
			if (!$error) {
				$conf['begin'] = t3lib_div::intInRange(ceil($cObj->calc($conf['begin'])),0);
				$conf['max'] = t3lib_div::intInRange(ceil($cObj->calc($conf['max'])),0);
				if ($conf['begin'] && !$conf['max']) {
					$conf['max'] = 100000;
				}

				if ($conf['begin'] && $conf['max']) {
					$queryParts['LIMIT'] = $conf['begin'].','.$conf['max'];
				} elseif (!$conf['begin'] && $conf['max']) {
					$queryParts['LIMIT'] = $conf['max'];
				}
			}
		}

		if (!$error) {

				// Setting up tablejoins:
			$joinPart='';
			if ($conf['join']) {
				$joinPart = 'JOIN ' .trim($conf['join']);
			} elseif ($conf['leftjoin']) {
				$joinPart = 'LEFT OUTER JOIN ' .trim($conf['leftjoin']);
			} elseif ($conf['rightjoin']) {
				$joinPart = 'RIGHT OUTER JOIN ' .trim($conf['rightjoin']);
			}

				// Compile and return query:
			$fromTable = $table.' '.$this->aliasArray[$table];
			$queryParts['FROM'] = trim($fromTable.' '.$joinPart).($conf['from'] ? ','.$conf['from'] :'' );

			$query = $TYPO3_DB->SELECTquery(
				$queryParts['SELECT'],
				$queryParts['FROM'],
				$queryParts['WHERE'],
				$queryParts['GROUPBY'],
				$queryParts['ORDERBY'],
				$queryParts['LIMIT']
			);

			$rc = $returnQueryArray ? $queryParts : $query;
		}
		return $rc;
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
	public function getWhere (&$cObj, $table, $conf, $returnQueryArray = FALSE) {
		global $TCA, $TSFE, $TYPO3_DB;

		if ($this->needsInit()) {
			return FALSE;
		}

		$dummy1 = $this->aliasArray; // PHP 5.2.1 needs this

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
			$listArr = t3lib_div::intExplode(',', str_replace('this', $TSFE->contentPid, $conf['uidInList']));
			if (count($listArr) == 1) {
				$query.=' AND '.$this->aliasArray[$table].'.uid='.intval($listArr[0]);
			} else {
				$query.=' AND '.$this->aliasArray[$table].'.uid IN ('.implode(',', $TYPO3_DB->cleanIntArray($listArr)).')';
			}
			$pid_uid_flag++;
		}

		if (trim($conf['pidInList'])) {
			$listArr = t3lib_div::intExplode(',',str_replace('this', $TSFE->contentPid, $conf['pidInList']));	// str_replace instead of ereg_replace 020800

				// removes all pages which are not visible for the user!
			$listArr = $cObj->checkPidArray($listArr);

			if (count($listArr)) {
				$query.=' AND ' . $this->aliasArray[$table] . '.pid IN (' . implode(',', $TYPO3_DB->cleanIntArray($listArr)) . ')';
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

		if ($conf['languageField']) {
			if ($TSFE->sys_language_contentOL && $TCA[$table] && $TCA[$table]['ctrl']['languageField'] && $TCA[$table]['ctrl']['transOrigPointerField']) {
					// Sys language content is set to zero/-1 - and it is expected that whatever routine processes the output will OVERLAY the records with localized versions!
				$sys_language_content = '0,-1';
			} else {
				$sys_language_content = intval($TSFE->sys_language_content);
			}
			$query.=' AND ' . $conf['languageField'] . ' IN (' . $sys_language_content . ')';
		}

		$andWhere = trim($cObj->stdWrap($conf['andWhere'], $conf['andWhere.']));
		if ($andWhere) {
			$query .= ' AND ' . $andWhere;
		}

			// enablefields
		if ($table == 'pages') {
			$query .= ' ' . $TSFE->sys_page->where_hid_del .
						$TSFE->sys_page->where_groupAccess;
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


?>