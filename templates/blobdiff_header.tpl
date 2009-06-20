{*
 *  blobdiff_header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blobdiff view header template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <div class="page_path"><b><a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hashbase}&h={$hashbase}">[{$project}]</a> / {foreach from=$paths item=path name=paths}{if $smarty.foreach.paths.last}<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$path.tree}&hb={$hashbase}&f={$path.full}">{$path.short}</a>{else}<a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hashbase}&h={$path.tree}&f={$path.full}">{$path.short}</a> / {/if}{/foreach}</b></div>
 <div class="page_body">
 <div class="diff_info">blob:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$hashparent}&hb={$hashbase}&f={$file}">{if $file}a/{$file}{else}{$hashparent}{/if}</a> -&gt; blob:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$hash}&hb={$hashbase}&f={$file}">{if $file}b/{$file}{else}{$hash}{/if}</a></div>
