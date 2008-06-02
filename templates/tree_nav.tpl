{*
 *  tree_nav.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tree view nav template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <div class="page_nav"><a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hashbase}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hashbase}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hashbase}">commitdiff</a> | tree<br /><br />
 </div>
 <div><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}" class="title">{$title}
 {if $hashbaseref}
 <span class="tag">{$hashbaseref}</span>
 {/if}
 </a></div>
