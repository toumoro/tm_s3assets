<?php

/***
 *
 * This file is part of the "tm_s3assets" Extension for TYPO3 CMS by Toumoro.com.
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
class Concat extends \TYPO3\CMS\Core\Page\PageRenderer {

    public function initS3() {

        /* Retrieve extension configuration */
        $this->s3ExtConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tm_s3assets']);
        $this->s3ExtConfiguration = $this->s3ExtConfiguration['s3.'];


        $this->S3bucket = $this->s3ExtConfiguration['bucket'];
        $this->S3protocol = $this->s3ExtConfiguration['protocol'];
        $this->S3domain = $this->s3ExtConfiguration['cdn'];
        $this->S3baseDir = $this->s3ExtConfiguration['baseDir'];
        
        
       
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

    public function css($params, &$obj) {
        $this->initS3();
        //debug($params['cssLibs']);
        $cssLibs = $this->getCompressor()->concatenateCssFiles($params['cssLibs'], array());
        $cssFiles = $this->getCompressor()->concatenateCssFiles($params['cssFiles'], array());

        $params['cssLibs'] = $this->processFileArray($cssLibs, '../');
        $params['cssFiles'] = $this->processFileArray($cssFiles, '../');


        //debug($currentApplicationContext);
    }

    public function js($params, &$obj) {

        $this->initS3();

        //debug($params['cssLibs']);
        $jsLibs = $this->getCompressor()->concatenateJsFiles($params['jsLibs'], array());
        $jsFiles = $this->getCompressor()->concatenateJsFiles($params['jsFiles'], array());
        $jsFooterFiles = $this->getCompressor()->concatenateJsFiles($params['jsFooterFiles'], array());


        $params['jsLibs'] = $this->processFileArray($jsLibs);
        $params['jsFiles'] = $this->processFileArray($jsFiles);
        $params['jsFooterFiles'] = $this->processFileArray($jsFooterFiles);


        //debug($currentApplicationContext);
    }

    public function cssCompress($params, &$obj) {
        $this->initS3();
        //debug($params['cssLibs']);
        $cssLibs = $this->getCompressor()->compressCssFiles($params['cssLibs'], array());
        $cssFiles = $this->getCompressor()->compressCssFiles($params['cssFiles'], array());

        $params['cssLibs'] = $this->processFileArray($cssLibs, '../../');
        $params['cssFiles'] = $this->processFileArray($cssFiles, '../../');


        //debug($currentApplicationContext);
    }

    public function jsCompress($params, &$obj) {

        $this->initS3();

        //debug($params['cssLibs']);
        $jsLibs = $this->getCompressor()->compressJsFiles($params['jsLibs'], array());
        $jsFiles = $this->getCompressor()->compressJsFiles($params['jsFiles'], array());
        $jsFooterFiles = $this->getCompressor()->compressJsFiles($params['jsFooterFiles'], array());

        $params['jsLibs'] = $this->processFileArray($jsLibs);
        $params['jsFiles'] = $this->processFileArray($jsFiles);
        $params['jsFooterFiles'] = $this->processFileArray($jsFooterFiles);
        // exit();
        //debug($currentApplicationContext);
    }

    public function postRender($params, &$obj) {
        if (TYPO3_MODE == "BE") {
            $libs = $params['jsLibs'];
            //print_r($libs);
            preg_match_all('/"(\/typo3temp\/[^"]+)/', $libs, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $k => $value) {
                    
                    $relPath = substr($value,1);
                    
                    $this->uploadToS3($relPath, PATH_site.$relPath);
                        
                        
                    
                }
                $params["jsLibs"] = str_replace('"/typo3temp/','"'.$this->S3protocol . '://' . $this->S3domain . '/' . $this->S3baseDir.'typo3temp/',$libs);
            }
            
        }
    }

    protected function processFileArray($files, $fixPath = false) {
        $tmpLibs = array();
        foreach ($files as $key => $value) {

            if ((strpos($value['file'], 'typo3temp/') !== false) && (strpos($value['file'], "http") !== 0)) {

                $file = $value['file'];
                $relPath = str_replace("../typo3temp/", "typo3temp/", $file);

                if (TYPO3_MODE == "BE") {
                    $file = PATH_site . $relPath;
                }


                if ($fixPath) {
                    $content = file_get_contents($file);
                    $content = $this->cssFixRelativeUrlPaths($content, $fixPath);
                    file_put_contents($file, $content);
                }

                $s3url = $this->uploadToS3($relPath, $file);

                $tmpLibs[$s3url] = $value;
                $tmpLibs[$s3url]['file'] = $s3url;
            } else {
                $tmpLibs[$key] = $value;
            }
        }
        return $tmpLibs;
    }

    public function uploadToS3($relPath, $file) {
        $s3path = $this->S3baseDir . $relPath;
        $s3url = $this->S3protocol . '://' . $this->S3domain . '/' . $s3path;
        
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_hash');
        $cacheKey = 's3'.md5($file);
                    
        if (!$cache->has($cacheKey)) {
            $this->S3client->putObject([
                'Bucket' => $this->S3bucket,
                'Key' => $s3path,
                'SourceFile' => $file,
                'CacheControl' => 'public,max-age=2678400',
            ]);
            $cache->set($cacheKey,1);
        }
        
        return $s3url;
    }

    /**
     * Fixes the relative paths inside of url() references in CSS files
     *
     * @param string $contents Data to process
     * @param string $oldDir Directory of the original file, relative to TYPO3_mainDir
     * @return string Processed data
     */
    protected function cssFixRelativeUrlPaths($contents, $oldDir) {
        $newDir = $oldDir;
        // Replace "url()" paths
        if (stripos($contents, 'url') !== false) {
            $regex = '/url(\\(\\s*["\']?(?!\\/)([^"\']+)["\']?\\s*\\))/iU';
            $contents = $this->findAndReplaceUrlPathsByRegex($contents, $regex, $newDir, '(\'|\')');
        }
        // Replace "@import" paths
        if (stripos($contents, '@import') !== false) {
            $regex = '/@import\\s*(["\']?(?!\\/)([^"\']+)["\']?)/i';
            $contents = $this->findAndReplaceUrlPathsByRegex($contents, $regex, $newDir, '"|"');
        }
        return $contents;
    }

    /**
     * Finds and replaces all URLs by using a given regex
     *
     * @param string $contents Data to process
     * @param string $regex Regex used to find URLs in content
     * @param string $newDir Path to prepend to the original file
     * @param string $wrap Wrap around replaced values
     * @return string Processed data
     */
    protected function findAndReplaceUrlPathsByRegex($contents, $regex, $newDir, $wrap = '|') {
        $matches = [];
        $replacements = [];
        $wrap = explode('|', $wrap);
        preg_match_all($regex, $contents, $matches);
        foreach ($matches[2] as $matchCount => $match) {
            // remove '," or white-spaces around
            $match = trim($match, '\'" ');
            // we must not rewrite paths containing ":" or "url(", e.g. data URIs (see RFC 2397)
            if (strpos($match, ':') === false && !preg_match('/url\\s*\\(/i', $match)) {
                $newPath = GeneralUtility::resolveBackPath($newDir . $match);
                $replacements[$matches[1][$matchCount]] = $wrap[0] . $newPath . $wrap[1];
            }
        }
        // replace URL paths in content
        if (!empty($replacements)) {
            $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);
        }
        return $contents;
    }

}
