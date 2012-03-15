<div class="searchbox">
  {if $searchType == 'WorldCatAdvanced'}
    <a href="{$path}/WorldCat/Advanced?edit={$searchId}" class="small">{translate text="Edit this Advanced Search"}</a> |
    <a href="{$path}/WorldCat/Advanced" class="small">{translate text="Start a new Advanced Search"}</a> |
    <a href="{$path}/WorldCat/Home" class="small">{translate text="Start a new Basic Search"}</a>
    <br>{translate text="Your search terms"} : "<b>{$lookfor|escape:"html"}</b>"
  {else}
    <form method="GET" action="{$path}/WorldCat/Search" name="searchForm" class="search">
      <input type="text" name="lookfor" size="30" value="{$lookfor|escape:"html"}">
      <select name="type">
        {foreach from=$worldCatSearchTypes item=searchDesc key=searchVal}
          <option value="{$searchVal}"{if $searchIndex == $searchVal} selected{/if}>{translate text=$searchDesc}</option>
        {/foreach}
      </select>
      <input type="submit" name="submit" value="{translate text="Find"}">
      <a href="{$path}/WorldCat/Advanced" class="small">{translate text="Advanced"}</a>
    </form>
  {/if}
</div>