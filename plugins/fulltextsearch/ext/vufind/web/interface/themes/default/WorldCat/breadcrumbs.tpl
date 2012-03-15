<a href="{if $lastsearch}{$lastsearch|escape}{else}{$url}/WorldCat/Search{/if}">{translate text="Search"}{if $lookfor}: {$lookfor|escape}{/if}</a> <span>&gt;</span>
{if $id}
{assign var=marcField value=$marc->getField('245')}
<em>{$marcField|getvalue:'a'|escape} {$marcField|getvalue:'b'|escape}</em>
{/if}