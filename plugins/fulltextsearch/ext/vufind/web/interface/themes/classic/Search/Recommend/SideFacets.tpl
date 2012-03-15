<div class="box submenu narrow">
  {if $recordCount > 0}
    <h4>{translate text='Narrow Search'}</h4>
  {else}
    {* We don't want to display a header if we don't have any checked checkbox filters: *}
    {assign var="hasCheckboxFilters" value="0"}
    {if isset($checkboxFilters) && count($checkboxFilters) > 0}
      {foreach from=$checkboxFilters item=current}
        {if $current.selected}
          {assign var="hasCheckboxFilters" value="1"}
        {/if}
      {/foreach}
    {/if}
    {if $hasCheckboxFilters || $filterList}
      <h4>{translate text="nohit_filters"}</h4>
    {else}
      <p>{translate text="nohit_no_filters"}</p>
    {/if}
  {/if}
  {if isset($checkboxFilters) && count($checkboxFilters) > 0}
  <p>
    <table>
      {foreach from=$checkboxFilters item=current}
          <tr{if $recordCount < 1 && !$current.selected && !$current.alwaysVisible} style="display: none;"{/if}>
            <td style="vertical-align:top; padding: 3px;">
              <input type="checkbox" name="filter[]" value="{$current.filter|escape}"
                {if $current.selected}checked="checked"{/if}
                onclick="document.location.href='{$current.toggleUrl|escape}';" />
            </td>
            <td>
              {translate text=$current.desc}<br />
            </td>
          </tr>
      {/foreach}
    </table>
  </p>
  {/if}
  {if $filterList}
    <ul class="filters">
    {foreach from=$filterList item=filters key=field}
        {foreach from=$filters item=filter}
      <li>{translate text=$field}: {$filter.display|escape} <a href="{$filter.removalUrl|escape}"><img src="{$path}/images/silk/delete.png" alt="Delete"></a></li>
        {/foreach}
    {/foreach}
    </ul>
  {/if}
  {if $sideFacetSet && $recordCount > 0}
    {foreach from=$sideFacetSet item=cluster key=title}
      <dl class="narrowList navmenu narrow_begin">
        <dt>{translate text=$cluster.label}</dt>
        {foreach from=$cluster.list item=thisFacet name="narrowLoop"}
          {if $smarty.foreach.narrowLoop.iteration == 6}
          <dd id="more{$title}"><a href="#" onClick="moreFacets('{$title}'); return false;">{translate text='more'} ...</a></dd>
        </dl>
        <dl class="narrowList navmenu narrowGroupHidden" id="narrowGroupHidden_{$title}">
          {/if}
          {if $thisFacet.isApplied}
            <dd>{$thisFacet.value|escape} <img src="{$path}/images/silk/tick.png" alt="Selected"></dd>
          {else}
            <dd><a href="{$thisFacet.url|escape}">{$thisFacet.value|escape}</a> ({$thisFacet.count})</dd>
          {/if}
        {/foreach}
        {if $smarty.foreach.narrowLoop.total > 5}<dd><a href="#" onClick="lessFacets('{$title}'); return false;">{translate text='less'} ...</a></dd>{/if}
      </dl>
    {/foreach}
  {/if}
</div>
