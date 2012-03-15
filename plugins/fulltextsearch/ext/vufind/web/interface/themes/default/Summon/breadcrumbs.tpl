<a href="{if $lastsearch}{$lastsearch|escape}{else}{$url}/Summon/Search{/if}">{translate text="Search"}{if $lookfor}: {$lookfor|escape}{/if}</a> 
<span>&gt;</span>
{if $id}
<em>{$record.Title.0|escape}</em>
{/if}