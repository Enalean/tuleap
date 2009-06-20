{*
 *  tree_filelist_header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tree view filelist header template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <div class="page_path"><b><a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hashbase}&h={$hashbase}">[{$project}]</a> / {foreach from=$paths item=path}<a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hashbase}&h={$path.tree}&f={$path.full}">{$path.short}</a> / {/foreach}</b></div>
 <div class="page_body">
 <table cellspacing="0">
