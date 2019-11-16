<?php
require_once __DIR__ . '/vendor/autoload.php'; //不用OSS删除此项
use OSS\OssClient; //不用OSS删除此项
use OSS\Core\OssException; //不用OSS删除此项
require_once 'config.php';

$argv = (count($_POST) > 0) ? $_POST : $_GET;
if (!isset($argv["album"]) || strlen($argv["album"]) <= 0 ||
    strpos($argv["album"],'\\') || strpos($argv["album"],'/')
    ) {
        header('HTTP/1.0 400 Bad Request'); die();
    }

$filelist = []; //test1/nla.1573292918.1.S.webp
if ($dos_saveToOSS) {
    $ossClient = new OssClient($oss_accessKeyId, $oss_accessKeySecret, $oss_endpoint);
    while (true) {
        try {
            $listObjectInfo = $ossClient->listObjects($oss_bucket, [
                'prefix'    => "test1/",
                'max-keys'  => 1000
            ]);
        } catch (OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
        // 得到nextMarker，从上一次listObjects读到的最后一个文件的下一个文件开始继续获取文件列表。
        $nextMarker = $listObjectInfo->getNextMarker();
        $listObject = $listObjectInfo->getObjectList();
        $listPrefix = $listObjectInfo->getPrefixList();
        if (!empty($listObject)) {
            // print("objectList:\n");
            foreach ($listObject as $objectInfo) {
                // print($objectInfo->getKey() . "\n");
                array_push($filelist,$objectInfo->getKey());
            }
        }
        if (!empty($listPrefix)) {
            // print("prefixList: \n");
            // foreach ($listPrefix as $prefixInfo) {
            //     print($prefixInfo->getPrefix() . "\n");
            // }
        }
        if ($nextMarker === '') {
            break;
        }
    }
} else {
    $path = $dos_albumdir.$argv["album"];
    $dir = @opendir($path);
    $canopen = ($dir) ? true : false;
    if ($canopen) {
        while($content = readdir($dir)){
            if($content != '.' && $content != '..'){
                $fullpath = $path.'/'.$content;
                $fullpath = substr($fullpath,strlen($dos_albumdir));
                array_push($filelist,$fullpath);
            }
        }
    }
}
//文件列表分离和过滤
$newfilelist = [];
$newfilelistkeys = [];
for ($fli=0; $fli < count($filelist); $fli++) { 
    $nowfile = str_replace('\\','/',$filelist[$fli]);
    $nowfilearr = explode('/',$nowfile);
    $nowfilename = array_pop($nowfilearr);
    if (explode('.',$nowfilename)[0] != "nla") continue;
    $dir = implode('/',$nowfilearr);
    $filenamearr = explode('.',$nowfilename);
    $prefilename = $filenamearr[0].'.'.$filenamearr[1].'.'.$filenamearr[2];
    $fileextname = $filenamearr[4];
    $filetime = $filenamearr[1];
    $filetime = date("Y,m,d,H,i,s", intval($filetime));
    $filetime = explode(',',$filetime);
    $filekey = $filenamearr[1].'.'.$filenamearr[2];
    if ($fileextname != "webp") {
        $floatkey = floatval($filekey);
        if (!in_array($floatkey,$newfilelistkeys)) {
            $newfilelist[$filekey] = [$prefilename,$fileextname,$filetime];
            array_push($newfilelistkeys,$floatkey);
        }
    }
}
//文件信息数组重新排序
$newfilelistcount = count($newfilelistkeys);
for ($i = 0; $i < $newfilelistcount - 1; $i++) {
    for ($j = 0; $j < $newfilelistcount - $i - 1; $j++) {
        if ($newfilelistkeys[$j] > $newfilelistkeys[$j + 1]) {
            $newfilelistkeystemp = $newfilelistkeys[$j];
            $newfilelistkeys[$j] = $newfilelistkeys[$j + 1];
            $newfilelistkeys[$j + 1] = $newfilelistkeystemp;
        }
    }
}
$filelist = [];
$filelistkeysstr = "";
for ($i=0; $i < $newfilelistcount; $i++) { 
    $nowkey = $newfilelistkeys[$i];
    $filelistkeysstr .= $nowkey;
    $nowkeystr = null;
    if (ceil($nowkey) == $nowkey) {
        $nowkeystr = strval($nowkey).".0";
    } else {
        $nowkeystr = strval($nowkey);
    }
    $nowarr = $newfilelist[$nowkeystr];
    array_push($filelist,$nowarr);
}
//按日期倒序嵌套为分月日、分时二维数组
$newfilelist = [];
for ($i=$newfilelistcount-1; $i >= 0; $i--) {
    $file = $filelist[$i];
    $filedate = $file[2];
    $datekey = $filedate[1].'.'.$filedate[2];
    $hourkey = $filedate[3];
    if (isset($newfilelist[$datekey])) {
        $datearr = $newfilelist[$datekey];
        if (isset($datearr[$hourkey])) {
            $hourarr = $datearr[$hourkey];
            array_push($hourarr,$file);
            $datearr[$hourkey] = $hourarr;
        } else {
            $datearr[$hourkey] = [$file];
        }
        $newfilelist[$datekey] = $datearr;
    } else {
        $newfilelist[$datekey][$hourkey] = [$file];
    }
}
$reqarr = [
    [count($filelist),md5($filelistkeysstr),$url_autoreload,$url_albumdir],
    $newfilelist
];
header('Content-Type:application/json;charset=utf-8');
echo json_encode($reqarr);
?>