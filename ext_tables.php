<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tm_s3assets', 'Configuration/TypoScript', 's3 typo3temp assets');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_tms3assets_domain_model_temptable', 'EXT:tm_s3assets/Resources/Private/Language/locallang_csh_tx_tms3assets_domain_model_temptable.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_tms3assets_domain_model_temptable');

    }
);
