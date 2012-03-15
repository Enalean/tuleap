{if $topFacetSet}
  {foreach from=$topFacetSet item=cluster key=title}
  <div class="resulthead authorbox">
  <table class="facetsTop navmenu narrow_begin">
    <tr><th colspan="{$topFacetSettings.cols}">{translate text=$cluster.label}<span>{translate text="top_facet_suffix"}</span></th></tr>
        {foreach from=$cluster.list item=thisFacet name="narrowLoop"}
        {if $smarty.foreach.narrowLoop.iteration == ($topFacetSettings.rows * $topFacetSettings.cols) + 1}
    <tr id="more{$title}"><td><a href="#" onclick="moreFacets('{$title}'); return false;">{translate text='more'} ...</a></td></tr>
  </table>
  </div>
  <div class="resulthead authorbox narrowGroupHidden"  id="narrowGroupHidden_{$title}">
  <table class="facetsTop navmenu">
    <tr><th colspan="{$topFacetSettings.cols}">{translate text="top_facet_additional_prefix"}{translate text=$cluster.label}<span>{translate text="top_facet_suffix"}</span></th></tr>
        {/if}
    {if $smarty.foreach.narrowLoop.iteration % $topFacetSettings.cols == 1}
    <tr>
    {/if}
        {if $thisFacet.isApplied}
        <td>{$thisFacet.value|escape}</a> <img src="{$path}/images/silk/tick.png" alt="Selected"></td>
        {else}
        <td><a href="{$thisFacet.url|escape}">{$thisFacet.value|escape}</a> ({$thisFacet.count})</td>
        {/if}
    {if $smarty.foreach.narrowLoop.iteration % $topFacetSettings.cols == 0 || $smarty.foreach.narrowLoop.last}
    </tr>
    {/if}
        {if $smarty.foreach.narrowLoop.total > ($topFacetSettings.rows * $topFacetSettings.cols) && $smarty.foreach.narrowLoop.last}
    <tr><td><a href="#" onclick="lessFacets('{$title}'); return false;">{translate text='less'} ...</a></td></tr>
        {/if}
        {/foreach}
  </table>
  </div>
  {/foreach}
{/if}
