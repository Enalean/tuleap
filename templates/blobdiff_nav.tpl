{*
 *  blobdiff_nav.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blobdiff view nav template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <div class="page_nav">
 <a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hashbase}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hashbase}">tree</a><br />
 <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff_plain&h={$hash}&hp={$hashparent}&f={$file}">plain</a>
 </div>
 <div><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}" class="title">{$title}</a></div>
