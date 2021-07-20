<?php

/***
 *
 * This file is part of the "AWS S3 typo3temp assets" Extension for TYPO3 CMS by Toumoro.com.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Toumoro.com (Simon Ouellet)
 *
 ***/

namespace Toumoro\TmS3assets\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Cache\CacheManager;

/**
 * Description of Concat
 *
 * @author simouel
 */
class Concat extends \TYPO3\CMS\Core\Page\PageRenderer
{

    public function initS3()
    {

        /* Retrieve extension configuration */
        $this->s3ExtConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tm_s3assets'];
        if (!$this->s3ExtConfiguration) {
            $this->s3ExtConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tm_s3assets']);
            $this->s3ExtConfiguration = $this->s3ExtConfiguration['s3.'];
        }

        $this->S3bucket = $this->s3ExtConfiguration['bucket'];
        $this->S3protocol = $this->s3ExtConfiguration['protocol'];
        $this->S3domain = $this->s3ExtConfiguration['cdn'];
        $this->S3baseDir = $this->s3ExtConfiguration['baseDir'];
        $this->S3basereplace = $this->s3ExtConfiguration['baseDirReplace'];



        if (!class_exists("Aws\S3\S3Client")) {
            exit("Aws\S3\S3Client");
        }

        $options = [
            'region' => $this->s3ExtConfiguration['region'],
            'version' => $this->s3ExtConfiguration['version'],
            'credentials' => [
                'key' => $this->s3ExtConfiguration['apikey'], //'',
                'secret' => $this->s3ExtConfiguration['apisecret'], //'',
            ],
        ];
        if (!function_exists("Aws\manifest")) {
            require "typo3conf/ext/aws_sdk_php/Contrib/Aws/functions.php";
        }
        $this->S3client = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Aws\S3\S3Client', $options);
    }


    public function postRender($params, &$obj)
    {
        $this->initS3();
        $watch = ['headerData', 'jsLibs', 'jsFiles', 'jsFooterFiles', 'jsFooterLibs', 'cssFiles', 'cssLibs'];
        foreach ($watch as $key => $libs) {

            if (is_array($params[$libs])) {
                foreach ($params[$libs] as $k => $v) {
                    $this->extractFile($v);
                    $params[$libs][$k] = str_replace('typo3temp/assets',  $this->S3basereplace.'assets', $v);
                }
            } else {
                $this->extractFile($params[$libs]);
                $params[$libs] = str_replace('typo3temp/assets', $this->S3basereplace.'assets', $params[$libs]);
            }
        }
    }
    protected function extractFile($libs)
    {
        $found = false;
        preg_match_all('/(typo3temp\/assets[^"\?]+)/', $libs, $matches);
        if (!empty($matches[1])) {
            $found = true;
            foreach ($matches[1] as $k => $relPath) {
                $this->uploadToS3($relPath, \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/' . $relPath);
            }
        }
        return $found;
    }


    public function uploadToS3($relPath, $file)
    {
        $s3path = $this->S3baseDir . $relPath;
        $s3url = $this->S3protocol . '://' . $this->S3domain . '/' . $s3path;
        $s3path = str_replace('typo3temp/', $this->S3basereplace, $s3path);

        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_hash');
        $cacheKey = 's3' . md5($file);

        if (!$cache->has($cacheKey)) {
            $this->S3client->putObject([
                'Bucket' => $this->S3bucket,
                'Key' => $s3path,
                'SourceFile' => $file,
                'CacheControl' => 'public,max-age=2678400',
            ]);
            $cache->set($cacheKey, 1);
        }

        return $s3url;
    }

}
