{*
 *  history_header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: History view header template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <div><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}" class="title">{$title}
 {if $hashbaseref}
 <span class="tag">{$hashbaseref}</span>
 {/if}
 </a>
 </div>
 <div class="page_path"><b><a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hash}&h={$hash}">[{$project}]</a> / {foreach from=$paths item=path name=paths}{if $smarty.foreach.paths.last}<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$path.tree}&f={$path.full}">{$path.short}</a>{else}<a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hash}&h={$path.tree}&f={$path.full}">{$path.short}</a> / {/if}{/foreach}</b></div>
 <table cellspacing="0">
