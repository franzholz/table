<?php

$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';

if (
	class_exists($emClass) &&
	method_exists($emClass, 'extPath')
) {
	// nothing
} else {
	$emClass = 't3lib_extMgm';
}

$key = 'table';

$extensionPath = call_user_func($emClass . '::extPath', $key, $script);

return array(
	'tx_table_db' => $extensionPath . 'lib/class.tx_table_db.php',
	'tx_table_db_access' => $extensionPath . 'lib/class.tx_table_db_access.php',
	'tx_table_db_base' => $extensionPath . 'lib/class.tx_table_db_base.php'
);

