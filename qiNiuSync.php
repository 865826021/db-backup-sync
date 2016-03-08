<?php

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

/**
 * 同步数据到七牛存储
 * 
 * @author hiscaler <hiscaler@gmail.com>
 */
require 'vendor/autoload.php';
$config = require 'config/main.php';
$qiNiuConfig = isset($config['qiNiu']) ? $config['qiNiu'] : [];

if (isset($qiNiuConfig['accessKey']) && isset($qiNiuConfig['secretKey']) && isset($qiNiuConfig['bucketName'])) {
    $accessKey = $qiNiuConfig['accessKey'];
    $secretKey = $qiNiuConfig['secretKey'];
    $bucket = $qiNiuConfig['bucketName'];

    $auth = new Auth($accessKey, $secretKey);
    $token = $auth->uploadToken($bucket);

    $uploadMgr = new UploadManager();

    $dir = $config['fileDirectory'];
    $files = [];
    $handle = opendir($dir);
    if ($handle !== false) {
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $files[] = $dir . DIRECTORY_SEPARATOR . $file;
        }
    }
    closedir($handle);

    if ($files) {
        $logFile = fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . date('Ymd') . '.log', "a") or die("Unable to open file!");
        fwrite($logFile, "\r\n########## " . date('Y-m-d H:i:s') . " ##########\r\n");
        foreach ($files as $file) {
            echo "$file\r\n";
            $key = basename($file);
            list($ret, $err) = $uploadMgr->putFile($token, $key, $file);
            if ($err !== null) {
                // write log
                $txt = implode(' | ', $err) . "\r\n";
                fwrite($logFile, $txt);
            }
        }
        fwrite($logFile, "########## end ##########\r\n");
        fclose($logFile);
    }

    echo "Done.\r\n";
} else {
    die("Please setting config value.\r\n");
}

