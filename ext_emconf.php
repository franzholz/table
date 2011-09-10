<?php

########################################################################
# Extension Manager/Repository config file for ext "table".
#
# Auto generated 10-09-2011 18:57
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Table Library',
	'description' => 'This containts a base class which you can use to make your extensions independant from any specific table. And it can be used to make multiple language support with a separate language overlay table.',
	'category' => 'misc',
	'shy' => 0,
	'version' => '0.2.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Franz Holzinger',
	'author_email' => 'franz@ttproducts.de',
	'author_company' => 'jambage.com',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-0.0.0',
			'typo3' => '3.5.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:8:{s:9:"ChangeLog";s:4:"d6bb";s:10:"README.txt";s:4:"ee2d";s:12:"ext_icon.gif";s:4:"b71f";s:17:"ext_localconf.php";s:4:"8886";s:25:"lib/class.tx_table_db.php";s:4:"e866";s:32:"lib/class.tx_table_db_access.php";s:4:"e8bc";s:30:"lib/class.tx_table_db_base.php";s:4:"a724";s:26:"lib/class.tx_table_div.php";s:4:"d020";}',
	'suggests' => array(
	),
);

?>