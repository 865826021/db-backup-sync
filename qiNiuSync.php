<?php

use Katzgrau\KLogger\Logger;
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

$logger = new Logger(__DIR__ . '/logs', Psr\Log\LogLevel::DEBUG, array(
    'extension' => 'log',
    'prefix' => 'qiniu_',
    ));

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


            $logMessages = [str_pad(' ' . date('Y-m-d H:i:s') . ' ', 80, "#", STR_PAD_BOTH)];
            $uploadMgr = new UploadManager();
            $countFiles = count($files);
            foreach ($files as $i => $file) {
                echo "$file\r\n";
                $key = basename($file);
                // 判断文件是否已经上传
                if (isset($uploadFiles[$key])) {
                    $logMessages[] = str_pad($i + 1, $countFiles, '', STR_PAD_LEFT) . '. 忽略 ' . $file;
                    continue;
                }
                list($ret, $err) = $uploadMgr->putFile($token, $key, $file);
                if ($err !== null) {
                    // write log
                    $txt = implode(' | ', $err);
                    $logMessages[] = $txt;
                } else {
                    $logMessages[] = str_pad($i + 1, $countFiles, '', STR_PAD_LEFT) . '. 上传完毕 ' . $file;
                }
            }
            if (count($logMessages) == 1) {
                $logMessages[] = '未处理任何文件';
            }
            $logMessages[] = str_pad(' ' . date('Y-m-d H:i:s') . ' ', 80, "#", STR_PAD_BOTH);
            $logger->info("\r\n" . implode("\r\n", $logMessages));
        }
    }
} else {
    $logger->error('请检查配置文件（config/main.php）是否正确。');
}

