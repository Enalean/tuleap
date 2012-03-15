<div class="searchbox">
  {if $searchType == 'SummonAdvanced'}
    <a href="{$path}/Summon/Advanced?edit={$searchId}" class="small">{translate text="Edit this Advanced Search"}</a> |
    <a href="{$path}/Summon/Advanced" class="small">{translate text="Start a new Advanced Search"}</a> |
    <a href="{$path}/Summon/Home" class="small">{translate text="Start a new Basic Search"}</a>
    <br>{translate text="Your search terms"} : "<b>{$lookfor|escape:"html"}</b>"
  {else}
    <form method="GET" action="{$path}/Summon/Search" name="searchForm" id="searchForm" class="search">
      <input type="text" name="lookfor" size="30" value="{$lookfor|escape:"html"}">
      <select name="type">
        {foreach from=$summonSearchTypes item=searchDesc key=searchVal}
          <option value="{$searchVal}"{if $searchIndex == $searchVal} selected{/if}>{translate text=$searchDesc}</option>
        {/foreach}
      </select>
      <input type="submit" name="submit" value="{translate text="Find"}">
      <a href="{$path}/Summon/Advanced" class="small">{translate text="Advanced"}</a>

      {* Do we have any checkbox filters? *}
      {assign var="hasCheckboxFilters" value="0"}
      {if isset($checkboxFilters) && count($checkboxFilters) > 0}
        {foreach from=$checkboxFilters item=current}
          {if $current.selected}
            {assign var="hasCheckboxFilters" value="1"}
          {/if}
        {/foreach}
      {/if}
      {if $filterList || $hasCheckboxFilters}
        <div class="keepFilters">
          <input type="checkbox" checked="checked" onclick="filterAll(this);" /> {translate text="basic_search_keep_filters"}
          <div style="display:none;">
            {foreach from=$filterList item=data key=field}
              {foreach from=$data item=value}
                <input type="checkbox" checked="checked" name="filter[]" value='{$value.field}:"{$value.value|escape}"' />
              {/foreach}
            {/foreach}
            {foreach from=$checkboxFilters item=current}
              {if $current.selected}
                <input type="checkbox" checked="checked" name="filter[]" value="{$current.filter|escape}" />
              {/if}
            {/foreach}
          </div>
        </div>
      {/if}
    </form>
  {/if}
</div>