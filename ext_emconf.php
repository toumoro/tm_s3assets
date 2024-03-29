<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "tm_s3assets"
 *
 * Auto generated by Extension Builder 2018-05-16
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'AWS S3 typo3temp assets',
    'description' => 'This extension rewrites typo3temp assets to an AWS S3 bucket url.',
    'category' => 'plugin',
    'author' => 'Simon Ouellet',
    'author_email' => 'simon.ouellet@toumoro.com',
    'state' => 'alpha',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '2.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-10.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
