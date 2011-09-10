<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Franz Holzinger <franz@ttproducts.de>
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
 * Use this as a base class for your database tables
 */



class tx_table_db_base {
	public $name = ''; // overwrite this with the name of your table
	public $fieldArray = array(); // overwrite this with your fields

	public function init()	{

	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/table/lib/class.tx_table_db_base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/table/lib/class.tx_table_db_base.php']);
}


?>