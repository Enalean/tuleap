
{if $lastsearch}<a href="{$lastsearch|escape}">{translate text="Search"}</a> <span>&gt;</span>{/if}
 
{if $pageTemplate=="home.tpl"}<em>{$author.0|escape}, {$author.1|escape}</em> <span>&gt;</span>{/if}

{if $pageTemplate=="list.tpl"}<em>{translate text="Author Results for"} {$lookfor|escape}</em> <span>&gt;</span>{/if}
