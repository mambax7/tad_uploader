<{$toolbar}>
<{$jqueryui}>

<div style="margin-bottom: 30px;">
    <{$path}>
</div>

<h3><{$cat_title}>
    <{if $up_power}>
        <a href="index.php?op=tad_uploader_cate_form&cat_sn=<{$cat_sn}>" class="btn btn-warning btn-sm"><{$smarty.const._TAD_EDIT}></a>
    <{/if}>
</h3>

<div id="save_msg"></div>

<div style="clear:both;"></div>

<{if $cat_desc}>
    <div class="card card-body bg-light m-1"><{$cat_desc}></div>
<{/if}>

<form action="index.php" method="POST" enctype="multipart/form-data" role="form">
    <{if $list_mode=="icon"}>
        <{includeq file="$xoops_rootpath/modules/$xoops_dirname/templates/b4/sub_list_all_data_icon.tpl"}>
    <{else}>
        <{includeq file="$xoops_rootpath/modules/$xoops_dirname/templates/b4/sub_list_all_data.tpl"}>
    <{/if}>
    <{if $up_power}>
        <{includeq file="$xoops_rootpath/modules/$xoops_dirname/templates/b4/sub_selected_files_tool.tpl"}>
    <{/if}>
</form>


<{if $up_power}>
    <{includeq file="$xoops_rootpath/modules/$xoops_dirname/templates/b4/sub_js.tpl"}>
    <{includeq file="$xoops_rootpath/modules/$xoops_dirname/templates/b4/sub_batch_tool.tpl"}>
<{/if}>
