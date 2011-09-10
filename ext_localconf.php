<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (!defined ('TABLE_EXTkey')) {
	define('TABLE_EXTkey',$_EXTKEY);
}

if (!defined ('PATH_BE_table')) {
	define('PATH_BE_table', t3lib_extMgm::extPath(TABLE_EXTkey));
}



?>