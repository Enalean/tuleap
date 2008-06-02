{*
 *  commit_nav.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit view nav template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <div class="page_nav">
 <a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}">log</a> | commit | {if $parent}<a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}">commitdiff</a> | {/if}<a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}">tree</a>
 <br /><br />
 </div>
