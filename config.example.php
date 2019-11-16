<?php
$dos_saveToOSS = false; //是否存储到阿里云 OSS

// 本地相册文件夹（若使用阿里云 OSS 则不用设置，默认设置为当前文件夹的 upload 文件夹）
$dos_albumdir = dirname(__FILE__)."/upload/";
$dos_filename = "nla"; // 文件名称前缀
$dos_chmod = 770; //新建文件夹权限

$url_albumdir = "upload/"; //上传根目录网址,OSS用OSS网址
$url_autoreload = 60; //每隔多久自动重新获取，0 为不自动获取

// 阿里云 OSS 设置
// 阿里云主账号AccessKey拥有所有API的访问权限，风险很高。强烈建议您创建并使用RAM账号进行API访问或日常运维，请登录 https://ram.console.aliyun.com 创建RAM账号。
$oss_accessKeyId = "<yourAccessKeyId>"; // yourAccessKeyId
$oss_accessKeySecret = "<yourAccessKeySecret>"; // yourAccessKeySecret
$oss_endpoint = "http://oss-cn-beijing.aliyuncs.com"; // Endpoint
$oss_bucket = "<yourBucketName>"; // 存储空间名称

//图片压缩设置
$img_imageresize = [
    "R" => [0,0,0], //尺寸为 0 则使用原始尺寸，清晰度为 0 则使用原始清晰度
    "S" => [640,360,80],
    "M" => [1280,720,80],
    "L" => [1920,1080,80]
];
$img_strip = false; //是否清除 jpg 的 EXIF 信息
?>