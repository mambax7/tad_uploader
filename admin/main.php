<?php
/*-----------引入檔案區--------------*/
$xoopsOption['template_main'] = "tad_uploader_adm_main.tpl";
include_once "header.php";
include_once "../function.php";

/*-----------function區--------------*/

//列出所有tad_uploader_cate資料
function list_tad_uploader_cate_tree($def_cat_sn = "")
{
    global $xoopsDB, $xoopsTpl;

    $sql    = "SELECT cat_sn , count(*) FROM " . $xoopsDB->prefix("tad_uploader_file") . " GROUP BY cat_sn";
    $result = $xoopsDB->query($sql);
    while (list($cat_sn, $counter) = $xoopsDB->fetchRow($result)) {
        $cate_count[$cat_sn] = $counter;
    }
    $path     = get_tad_uploader_cate_path($def_cat_sn);
    $path_arr = array_keys($path);

    $data[] = "{ id:0, pId:0, name:'All', url:'main.php', target:'_self', open:true}";

    $sql = "SELECT cat_sn,of_cat_sn,cat_title FROM " . $xoopsDB->prefix("tad_uploader") . "  ORDER BY cat_sort";
    $result = $xoopsDB->query($sql) or web_error($sql);
    while (list($cat_sn, $of_cat_sn, $cat_title) = $xoopsDB->fetchRow($result)) {
        $font_style = $def_cat_sn == $cat_sn ? ", font:{'background-color':'yellow', 'color':'black'}" : '';
        //$open            = in_array($cat_sn, $path_arr) ? 'true' : 'false';
        $display_counter = empty($cate_count[$cat_sn]) ? "" : " ({$cate_count[$cat_sn]})";
        $data[]          = "{ id:{$cat_sn}, pId:{$of_cat_sn}, name:'{$cat_title}{$display_counter}', url:'main.php?cat_sn={$cat_sn}', open: true ,target:'_self' {$font_style}}";
    }

    $json = implode(",\n", $data);

    if (!file_exists(XOOPS_ROOT_PATH . "/modules/tadtools/ztree.php")) {
        redirect_header("index.php", 3, _MA_NEED_TADTOOLS);
    }
    include_once XOOPS_ROOT_PATH . "/modules/tadtools/ztree.php";
    $ztree      = new ztree("news_tree", $json, "save_drag.php", "save_sort.php", "of_cat_sn", "cat_sn");
    $ztree_code = $ztree->render();
    $xoopsTpl->assign('ztree_code', $ztree_code);

    return $data;
}

//列出所有tad_uploader資料
function list_tad_uploader($cat_sn = "")
{
    global $xoopsDB, $xoopsTpl;

    $and = !empty($cat_sn) ? "and a.cat_sn='{$cat_sn}'" : "";
    get_jquery(true);
    $sql = "select a.*,b.cat_title from " . $xoopsDB->prefix("tad_uploader_file") . " as a left join  " . $xoopsDB->prefix("tad_uploader") . " as b on a.cat_sn=b.cat_sn where 1 $and order by up_date desc";

    //getPageBar($原sql語法, 每頁顯示幾筆資料, 最多顯示幾個頁數選項);
    $PageBar = getPageBar($sql, $to_limit, 10);
    $bar     = $PageBar['bar'];
    $sql     = $PageBar['sql'];
    $total   = $PageBar['total'];

    $result = $xoopsDB->query($sql) or web_error($sql);
    $files = '';
    while ($all = $xoopsDB->fetchArray($result)) {
        $files[] = $all;
    }
    $cate = get_cate_data($cat_sn);
    $xoopsTpl->assign('files', $files);
    $xoopsTpl->assign('cate', $cate);
    $xoopsTpl->assign('cat_sn', $cat_sn);
    $xoopsTpl->assign('bar', $bar);
    $xoopsTpl->assign('total', $total);

    if (!file_exists(XOOPS_ROOT_PATH . "/modules/tadtools/sweet_alert.php")) {
        redirect_header("index.php", 3, _MA_NEED_TADTOOLS);
    }
    include_once XOOPS_ROOT_PATH . "/modules/tadtools/sweet_alert.php";
    $sweet_alert = new sweet_alert();
    $sweet_alert->render("delete_tad_uploader_func", "main.php?op=delete_tad_uploader&cat_sn=", 'cat_sn');
    $sweet_alert2 = new sweet_alert();
    $sweet_alert2->render("delete_file_func", "main.php?op=del_file&cat_sn={$cat_sn}&cfsn=", 'cfsn');

    // $xoopsTpl->assign('sweet_alert_code', $sweet_alert_code);
}

//取得所有資料夾列表
function get_cate_data($cat_sn = 0)
{
    global $xoopsDB, $xoopsTpl;

    $sql = "select * from " . $xoopsDB->prefix("tad_uploader") . " where cat_sn='$cat_sn'";
    $result = $xoopsDB->query($sql) or redirect_header($_SERVER['PHP_SELF'], 3, _MA_TADUP_DB_ERROR1);

    $data = "";

    list($cat_sn, $cat_title, $cat_desc, $cat_enable, $uid, $of_cat_sn, $cat_share, $cat_sort, $cat_count) = $xoopsDB->fetchRow($result);

    $cat_desc = nl2br($cat_desc);
    $uid_name = XoopsUser::getUnameFromId($uid, 1);
    $uid_name = (empty($uid_name)) ? XoopsUser::getUnameFromId($uid, 0) : $uid_name;

    $enable = ($cat_enable == '1') ? "<img src='../images/button_ok.png'>" : "<img src='../images/button_cancel.png'>";
    $share  = ($cat_share == '1') ? "<img src='../images/button_ok.png'>" : "<img src='../images/encrypted.gif'>";

    $data['cat_sort']  = $cat_sort;
    $data['cat_title'] = $cat_title;
    $data['cat_desc']  = $cat_desc;
    $data['uid_name']  = $uid_name;
    $data['enable']    = $enable;
    $data['share']     = $share;
    $data['cat_count'] = $cat_count;
    $data['cat_sn']    = $cat_sn;

    return $data;
}

//tad_uploader編輯表單
function tad_uploader_cate_form($cat_sn = "")
{
    global $xoopsDB, $xoopsModule, $xoopsTpl;
    include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";

    //抓取預設值
    if (!empty($cat_sn)) {
        $DBV = get_tad_uploader($cat_sn);
    } else {
        $DBV = array();
    }

    //預設值設定
    $cat_sn      = (!isset($DBV['cat_sn'])) ? $cat_sn : $DBV['cat_sn'];
    $cat_title   = (!isset($DBV['cat_title'])) ? "" : $DBV['cat_title'];
    $cat_desc    = (!isset($DBV['cat_desc'])) ? "" : $DBV['cat_desc'];
    $cat_enable  = (!isset($DBV['cat_enable'])) ? "1" : $DBV['cat_enable'];
    $uid         = (!isset($DBV['uid'])) ? "" : $DBV['uid'];
    $of_cat_sn   = (!isset($DBV['of_cat_sn'])) ? "" : $DBV['of_cat_sn'];
    $cata_select = get_cata_select(array($cat_sn), $of_cat_sn);
    $cat_share   = (!isset($DBV['cat_share'])) ? "1" : $DBV['cat_share'];
    $cat_count   = (!isset($DBV['cat_count'])) ? "" : $DBV['cat_count'];

    $cat_max_sort = get_cat_max_sort();
    $cat_sort     = (!isset($DBV['cat_sort'])) ? $cat_max_sort : $DBV['cat_sort'];

    $mod_id             = $xoopsModule->getVar('mid');
    $moduleperm_handler = xoops_gethandler('groupperm');
    $read_group         = $moduleperm_handler->getGroupIds("catalog", $cat_sn, $mod_id);
    $post_group         = $moduleperm_handler->getGroupIds("catalog_up", $cat_sn, $mod_id);

    if (empty($read_group)) {
        $read_group = array(1, 2, 3);
    }

    if (empty($post_group)) {
        $post_group = array(1);
    }

    //可見群組
    $SelectGroup_name = new XoopsFormSelectGroup("view_group", "catalog", true, $read_group, 6, true);
    $SelectGroup_name->setExtra("class='span12 form-control' id='view_group'");
    $enable_group = $SelectGroup_name->render();

    //可上傳群組
    $SelectGroup_name = new XoopsFormSelectGroup("upload_group", "catalog_up", true, $post_group, 6, true);
    $SelectGroup_name->setExtra("class='span12 form-control' id='upload_group'");
    $enable_upload_group = $SelectGroup_name->render();

    $xoopsTpl->assign('cata_select', $cata_select);
    $xoopsTpl->assign('cat_title', $cat_title);
    $xoopsTpl->assign('cat_desc', $cat_desc);
    $xoopsTpl->assign('enable_group', $enable_group);
    $xoopsTpl->assign('enable_upload_group', $enable_upload_group);
    $xoopsTpl->assign('cat_sn', $cat_sn);
    $xoopsTpl->assign('cat_count', $cat_count);
    $xoopsTpl->assign('cat_sort', $cat_sort);
    $xoopsTpl->assign('cat_enable', $cat_enable);
    $xoopsTpl->assign('cat_share', $cat_share);
}

//取得tad_uploader所有資料陣列
function get_tad_uploader_all()
{
    global $xoopsDB;
    $sql = "SELECT * FROM " . $xoopsDB->prefix("tad_uploader");
    $result = $xoopsDB->query($sql) or redirect_header($_SERVER['PHP_SELF'], 3, _MA_TADUP_DB_ERROR1);
    $data = $xoopsDB->fetchArray($result);
    return $data;
}

/*-----------執行動作判斷區----------*/
include_once $GLOBALS['xoops']->path('/modules/system/include/functions.php');
$op        = system_CleanVars($_REQUEST, 'op', '', 'string');
$cat_sn    = system_CleanVars($_REQUEST, 'cat_sn', 0, 'int');
$of_cat_sn = system_CleanVars($_REQUEST, 'of_cat_sn', 0, 'int');
$cfsn      = system_CleanVars($_REQUEST, 'cfsn', 0, 'int');

switch ($op) {
    case "add_tad_uploader":
        list_tad_uploader_cate_tree($cat_sn);
        add_tad_uploader($cat_sn, $_POST['cat_title'], $_POST['cat_desc'], $_POST['cat_enable'], $of_cat_sn, $_POST['add_to_cat'], $_POST['cat_share'], $_POST['cat_sort'], $_POST['cat_count'], $_POST['catalog'], $_POST['catalog_up'], 'admin');
        header("location: " . $_SERVER['PHP_SELF']);
        exit;

    //刪除資料
    case "delete_tad_uploader":
        delete_tad_uploader($cat_sn);
        header("location: " . $_SERVER['PHP_SELF']);
        exit;

    //輸入表格
    case "tad_uploader_cate_form":
        list_tad_uploader_cate_tree($cat_sn);
        tad_uploader_cate_form($cat_sn);
        break;

    case "del_file":
        del_file($cfsn);
        header("location: {$_SERVER['PHP_SELF']}?cat_sn={$cat_sn}");
        exit;

    default:
        list_tad_uploader_cate_tree($cat_sn);
        list_tad_uploader($cat_sn);
        break;
}

/*-----------秀出結果區--------------*/
$xoopsTpl->assign('op', $op);
include_once 'footer.php';
