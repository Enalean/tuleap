{*
 *  commitdiff_nav.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commitdiff view nav template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <div class="page_nav">
 <a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}">commit</a> | commitdiff | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}">tree</a><br /><a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff_plain&h={$hash}&hp={$hashparent}">plain</a>
 </div>
 <br /><br />
 </div>
