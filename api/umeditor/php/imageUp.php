<?php

if ($this->uid) {
    include "Uploader.class.php";

    $base64 = isset($_POST["base64"]) ? true:false;

    //上传配置
    $config = array(
        "maxSize" => 1000 ,                   //允许的文件最大尺寸，单位KB
        "allowFiles" => array( ".gif" , ".png" , ".jpg" , ".jpeg" , ".bmp" )  //允许的文件格式
    );
    $up = new Uploader("upfile", $config, $base64);

    $info = $up->getFileInfo();
} else {
    $info = array(
        'state'=> '请登录在操作'
    );
}

/**
 * 返回数据
 */
$callback=$_GET['callback'];
if($callback) {
    echo '<script>'.$callback.'('.json_encode($info).')</script>';
} else {
    echo json_encode($info);
}
?>