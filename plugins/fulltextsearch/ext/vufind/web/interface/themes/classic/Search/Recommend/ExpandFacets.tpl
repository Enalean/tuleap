{if $expandFacetSet}
  <div class="box submenu narrow">
  {foreach from=$expandFacetSet item=cluster key=title}
    <h4>{translate text=$cluster.label}</h4>
    <ul class="similar">
    {foreach from=$cluster.list item=thisFacet}
      <li><a href="{$thisFacet.expandUrl|escape}">{$thisFacet.value|escape}</a></li>
    {/foreach}
    </ul>
  {/foreach}
  </div>
{/if}
