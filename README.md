# NyarukoLiveAlbum
- 简单的实时相册分享，可进行实时照片直播。
- 不依赖于任何数据库，可使用阿里云 OSS 存储照片。

## 依赖
- `composer require aliyuncs/oss-sdk-php`

# 接口

## 上传文件
- `/upload.php`
    - (FILE) `file[]` 上传文件（建议一个个上传）
    - (POST) `album` 相册名称（将作为文件夹名称）