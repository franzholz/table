<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "table".
 ***************************************************************/


$EM_CONF[$_EXTKEY] = array(
    'title' => 'Table Library',
    'description' => 'This contains a base class which you can use to make your extensions independant from any specific table. And it can be used to make multiple language support with a separate language overlay table.',
    'category' => 'misc',
    'version' => '0.7.4',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearcacheonload' => 0,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'constraints' => array(
        'depends' => array(
            'php' => '5.5.0-7.99.99',
            'typo3' => '6.2.0-9.5.99',
            'div2007' => '1.10.26-0.0.0',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
            'typo3db_legacy' => '1.0.0-1.1.99',
        ),
    ),
);

