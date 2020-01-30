<?php

$key = 'table';

$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($key, $script);

return array(
    'tx_table_db' => $extensionPath . 'lib/class.tx_table_db.php',
    'tx_table_db_access' => $extensionPath . 'lib/class.tx_table_db_access.php'
);

