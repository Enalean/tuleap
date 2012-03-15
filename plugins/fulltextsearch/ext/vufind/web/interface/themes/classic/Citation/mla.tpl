{if $mlaDetails.authors}{$mlaDetails.authors|escape}. {/if}
<span style="font-style: italic;">{$mlaDetails.title|escape}</span> 
{if $mlaDetails.edition}{$mlaDetails.edition|escape} {/if}
{if $mlaDetails.publisher}{$mlaDetails.publisher|escape}, {/if}
{if $mlaDetails.year}{$mlaDetails.year|escape}.{/if}
