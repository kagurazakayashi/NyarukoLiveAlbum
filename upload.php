<?php
require_once __DIR__ . '/vendor/autoload.php'; //不用OSS删除此项
use OSS\OssClient; //不用OSS删除此项
use OSS\Core\OssException; //不用OSS删除此项
require_once 'config.php';
//POST album 相册

/**
 * [NyarukoLogin] uploadfile.class.php
 * @description: 将图片限制到指定尺寸，压缩成更小的 gif 或 jpg+webp 格式，然后存入日期文件夹。
 * @param String imagefile 图片临时文件完整路径
 * @param String savefile 最终存储文件路径（无.）（/imgs/img）
 * @param String saveext 最终存储文件路径扩展名前补位（在.后添加）
 * @param String type 扩展名
 * @param Array sizequality 最大尺寸和清晰度 [宽,高,在 jpg+webp 模式时的压缩比]
 * @param Bool img_strip 是否去除图片信息
 * @return Array [[文件完整路径],是否找到重复文件]
 */
function resizeto($imagefile,$savefile,$saveext,$type,$sizequality,$img_strip) {
    $imagick = new Imagick($imagefile);
    if ($img_strip) $imagick->stripImage(); //去除图片信息
    $isresize = ($sizequality[0] <= 0 || $sizequality[1] <= 0) ? false : true;
    $isrequality = ($sizequality[2] <= 0) ? false : true;
    if ($isresize) {
        $imageWidth = $imagick->getImageWidth();
        $imageHeight = $imagick->getImageHeight();
        if ($imageWidth <= 0 || $imageHeight <= 0) {
            $imageWidth = imagesx($srcImg);
            $imageHeight = imagesy($srcImg);
        }
        $newsize = getresize($imageWidth,$imageHeight,$sizequality[0],$sizequality[1]);
    }
    $isexists = false;
    if ($type == "gif") {
        $transparent = new ImagickPixel("transparent");
        $newimagick = new Imagick();
        foreach($imagick as $img){
            $page = $img->getImagePage();
            $tmp = new Imagick();
            $tmp->newImage($page['width'], $page['height'], $transparent, 'gif');
            $tmp->compositeImage($img, Imagick::COMPOSITE_OVER, $page['x'], $page['y']);
            if ($isresize) $tmp->adaptiveResizeImage($newsize[0], $newsize[1]);
            if ($isrequality) $tmp->setImageCompressionQuality(1);
            $tmp->setFormat("gif");
            $newimagick->addImage($tmp);
            $newimagick->setImagePage($tmp->getImageWidth(), $tmp->getImageHeight(), 0, 0);
            $newimagick->setImageDelay($img->getImageDelay());
            $newimagick->setImageDispose($img->getImageDispose());
            $tmp->destroy();
        }
        $newimagick->setFormat("gif");
        $savefilename = $savefile.".".$saveext.".gif";
        if (file_exists($savefilename)) $isexists = true;
        $newimagick->writeImages($savefilename,true);
        $newimagick->destroy();
        return [[$savefilename],$isexists];
    } else {
        if ($isresize) $imagick->adaptiveResizeImage($newsize[0], $newsize[1]);
        if ($isrequality) $imagick->setImageCompressionQuality($sizequality[2]); //图片质量
        $webp = $imagick;
        $webp->setFormat("webp");
        $savefilename1 = $savefile.".".$saveext.".webp";
        $dechextime = strval(dechex(time()));
        if (file_exists($savefilename1)) $isexists = true;
        $webp->writeImage($savefilename1);
        $jpeg = $imagick;
        $jpeg->setFormat("jpeg");
        $savefilename2 = $savefile.".".$saveext.".jpg";
        if (file_exists($savefilename2)) $isexists = true;
        $jpeg->writeImage($savefilename2);
        $webp->destroy();
        $jpeg->destroy();
        $imagick->destroy();
        return [[$savefilename1,$savefilename2],$isexists];
    }
}

/**
 * [NyarukoLogin] uploadfile.class.php
 * @description: 缩小图片（如果已经小于设定值则输出原尺寸）
 * @param Float imageWidth 原始图片宽度
 * @param Float imageHeight 原始图片高度
 * @param Float maxWidth 目标尺寸宽度
 * @param Float maxHeight 目标尺寸高度
 * @return Array<Float> 新的宽高
 */
function getresize($imageWidth,$imageHeight,$maxWidth,$maxHeight) {
    $newWidth = $imageWidth;
    $newHeight = $imageHeight;
    $imageScale = $imageWidth / $imageHeight;
    $maxScale = $maxWidth / $maxHeight;
    if ($maxScale < $imageScale) {
        $newWidth = $maxWidth;
        $newHeight = $maxWidth / $imageScale;
    } else if ($maxScale > $imageScale) {
        $newHeight = $maxHeight;
        $newWidth = $maxHeight * $imageScale;
    }
    if ($newWidth > $imageWidth && $newHeight > $imageHeight) {
        return [$imageWidth,$imageHeight];
    } else {
        return [$newWidth,$newHeight];
    }
}

if (!isset($_FILES["file"]) || count($_FILES["file"]) <= 0 ||
    !isset($_POST["album"]) || strlen($_POST["album"]) <= 0 ||
    strpos($_POST["album"],'\\') || strpos($_POST["album"],'/')
    ) {
        header('HTTP/1.0 400 Bad Request'); die();
    }
$jsonarr = [];
$enableExts = ["jpg"];
$files = $_FILES["file"];
for ($upfileId = 0; $upfileId < count($files["name"]); $upfileId++) {
    $upfileName = $files["name"][$upfileId];
    $upfileType = $files["type"][$upfileId];
    $upfileTemp = str_replace("\\","/",$files["tmp_name"][$upfileId]);
    $upfileErr = $files["error"][$upfileId];
    $upfileSize = $files["size"][$upfileId];
    $tmpdir = explode("/", $upfileTemp);
    array_pop($tmpdir);
    $tmpdir = implode("/", $tmpdir);
    $filenamearr = explode(".", $upfileName);
    $extension = end($filenamearr);
    if (!in_array($extension, $enableExts)) {
        $jsonarr[$upfileName] = [-1,"不支持"];
    } else if ($upfileErr > 0) {
        $jsonarr[$upfileName] = [$upfileErr,"错误"];
    } else {
        $covered = false;
        foreach ($img_imageresize as $sizename => $sizemode) {
            $saveext = strval(time()).'.'.strval($upfileId).'.'.$sizename;
            $saveto = $dos_saveToOSS ? ($tmpdir.$dos_filename) : ($dos_albumdir.$dos_filename);
            $resizereq = resizeto($upfileTemp,$saveto,$saveext,$extension,$sizemode,$img_strip);
            if ($resizereq[1]) $covered = true;
        }
        if ($covered) {
            $jsonarr[$upfileName] = [1,"文件已存在"];
        } else {
            if ($dos_saveToOSS) {
                //TODO: 上传OSS
            }
            $jsonarr[$upfileName] = [0,"完成"];
        }
    }
}
header('Content-Type:application/json;charset=utf-8');
echo json_encode($jsonarr);
?>