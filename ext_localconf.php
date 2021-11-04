<?php
#$GLOBALS['TYPO3_CONF_VARS']['FE']['cssConcatenateHandler'] = "Toumoro\\TmS3assets\\Utility\\Concat->css";
#$GLOBALS['TYPO3_CONF_VARS']['FE']['jsConcatenateHandler'] = "Toumoro\\TmS3assets\\Utility\\Concat->js";

/*$GLOBALS['TYPO3_CONF_VARS']['BE']['cssConcatenateHandler'] = "Toumoro\\TmS3assets\\Utility\\Concat->css";
$GLOBALS['TYPO3_CONF_VARS']['BE']['jsConcatenateHandler'] = "Toumoro\\TmS3assets\\Utility\\Concat->js";*/

#$GLOBALS['TYPO3_CONF_VARS']['BE']['cssCompressHandler'] = "Toumoro\\TmS3assets\\Utility\\Concat->cssCompress";
#$GLOBALS['TYPO3_CONF_VARS']['BE']['jsCompressHandler'] = "Toumoro\\TmS3assets\\Utility\\Concat->jsCompress";


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess']['tm_s3assets'] = "Toumoro\\TmS3assets\\Utility\\Concat->postRender";

