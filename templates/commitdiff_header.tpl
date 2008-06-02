{*
 *  commitdiff_header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commitdiff view header template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <div><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}" class="title">{$title}{if $commitref} <span class="tag">{$commitref}</span>{/if}</a></div>
 <div class="page_body">
 {foreach from=$comment item=line}
 {$line}<br />
 {/foreach}
 <br />
