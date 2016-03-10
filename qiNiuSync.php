<?php

use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
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
        // 获取空间中的文件列表
        $bucketMgr = new BucketManager($auth);
        list($iterms, $marker, $err) = $bucketMgr->listFiles($bucket, null, null, null);
        if ($err == null) {
            $uploadFiles = [];
            foreach ($iterms as $key => $item) {
                $uploadFiles[$item['key']] = $item['hash'];
            }

            $sign = ' ' . date('Y-m-d H:i:s') . ' ';
            $logMessages = [str_pad($sign, 80, "#", STR_PAD_BOTH)];
            $uploadMgr = new UploadManager();
            $countFiles = count($files);
            foreach ($files as $i => $file) {
                echo "$file\r\n";
                $key = basename($file);
                // 判断文件是否已经上传
                if (isset($uploadFiles[$key])) {
                    $logMessages[] = str_pad($i + 1, $countFiles, '', STR_PAD_LEFT) . ': Ignore ' . $file;
                    continue;
                }
                list($ret, $err) = $uploadMgr->putFile($token, $key, $file);
                if ($err !== null) {
                    // write log
                    $txt = implode(' | ', $err);
                    $logMessages[] = $txt;
                } else {
                    $logMessages[] = str_pad($i + 1, $countFiles, '', STR_PAD_LEFT) . ': Uploaded ' . $file;
                }
            }
            if (count($logMessages) == 1) {
                $logMessages[] = 'Nothing';
            }
            $logMessages[] = str_pad($sign, 80, "#", STR_PAD_BOTH);
            $logFile = fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . date('Ymd') . '.log', "a") or die("Unable to open file!");
            fwrite($logFile, implode("\r\n", $logMessages) . "\r\n");
            fclose($logFile);
        }
    }

    echo "Done.\r\n";
} else {
    die("Please setting config value.\r\n");
}

