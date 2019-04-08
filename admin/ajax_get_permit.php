<?php
/*-----------引入檔案區--------------*/
include_once "header.php";
include_once "../function.php";

/*-----------function區--------------*/
$tad_uploader    = array(1, 2, 3);
$tad_uploader_up = array(1);

//上層權限
$of_cat_sn = (int)$_GET['of_cat_sn'];
if ($of_cat_sn) {
    $tad_uploader    = getItem_Permissions($of_cat_sn, 'catalog');
    $tad_uploader_up = getItem_Permissions($of_cat_sn, 'catalog_up');
}

$data['tad_uploader']    = join(',', $tad_uploader);
$data['tad_uploader_up'] = join(',', $tad_uploader_up);
/*
$data['tad_uploader'] =  $tad_uploader  ;
$data['tad_uploader_up']  = $tad_uploader_up  ;
 */
echo json_encode($data, JSON_FORCE_OBJECT);
