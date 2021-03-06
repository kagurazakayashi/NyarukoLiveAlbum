# NyarukoLiveAlbum
- 简单的实时相册分享，可进行实时照片直播。
- 不依赖于任何数据库，可使用阿里云 OSS 存储照片。

## 依赖
- 若使用阿里云 OSS：
  - `composer require aliyuncs/oss-sdk-php`
- 若使用前端网页：
  - `npm install mdui --save`

# 接口

## 上传文件

### 调用
- `/upload.php`
  - (FILE) `file[]` 上传文件（建议一个个上传）
  - (POST) `album` 相册名称（将作为文件夹名称）

### 返回 JSON
```
{
    "IMG_20191109_153511.jpg": [
        0,
        "完成"
    ],
    "IMG_20191109_153507.jpg": [
        0,
        "完成"
    ]
}
```

## 获取某个相册中的所有照片

### 调用
- `/photolist.php`
  - (GET/POST) `album` 相册名称（将作为文件夹名称）

### 返回 JSON
```
[
    [
        2, //照片数量
        "f6f47b8578c475915696c6c270ef8a22", //出现变化此MD5会变动
        60, //自动刷新时间秒
        "upload/" //上传根目录
    ],
    {
        "11.09": { //月.日
            "08": [ //小时
                [
                    "nla.1573289902.1",
                    "jpg",
                    [
                        "2019","11","09",
                        "08","58","22"
                    ]
                ],
                [
                    "nla.1573289901.1",
                    "jpg",
                    [
                        "2019","11","09",
                        "08","58","21"
                    ]
                ]
            ]
        }
    }
]
```