{if $apaDetails.authors}{$apaDetails.authors|escape} {/if}
{if $apaDetails.year}({$apaDetails.year|escape}). {/if}
<span style="font-style:italic;">{$apaDetails.title|escape}</span> 
{if $apaDetails.edition}{$apaDetails.edition|escape} {/if}
{if $apaDetails.publisher}{$apaDetails.publisher|escape}.{/if}
