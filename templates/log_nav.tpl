{*
 *  log_nav.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Log view nav template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <div class="page_nav">
 <a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}">shortlog</a> | log | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$hash}&hb={$hash}">tree</a>
 <br />
