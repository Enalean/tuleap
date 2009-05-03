{*
 *  search_nav.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Search view nav template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
<div class="page_nav">
<a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$treehash}&hb={$hash}">tree</a>
<br />
