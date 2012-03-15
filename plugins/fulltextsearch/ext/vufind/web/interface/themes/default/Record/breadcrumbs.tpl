{if $lastsearch}
<a href="{$lastsearch|escape}#record{$id|escape:"url"}">{translate text="Search"}</a> <span>&gt;</span>
{/if}
{if $breadcrumbText}
<em>{$breadcrumbText|truncate:30:"..."|escape}</em> <span>&gt;</span>
{/if}
{if $subTemplate!=""}
<em>{$subTemplate|replace:'view-':''|replace:'.tpl':''|replace:'../MyResearch/':''|capitalize|translate}</em> 
{/if}
