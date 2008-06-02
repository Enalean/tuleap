{*
 *  blob_nav.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blob view nav template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <div class="page_nav">
 <a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hashbase}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hashbase}">tree</a><br />
 {if $file}
 <a href="{$SCRIPT_NAME}?p={$project}&a=blob_plain&h={$hash}&f={$file}">plain</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=blob&hb=HEAD&f={$file}">head</a><br />
 {else}
 <a href="{$SCRIPT_NAME}?p={$project}&a=blob_plain&h={$hash}">plain</a><br />
 {/if}
 </div>
 <div><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}" class="title">{$title}</a></div>
