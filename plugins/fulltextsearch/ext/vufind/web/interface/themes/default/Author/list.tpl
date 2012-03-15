
<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
      <b class="btop"><b></b></b>

      {* Listing Options *}
      <div class="yui-ge resulthead">
        <div class="yui-u first">
        {if $recordCount}
          {translate text="Showing"}
          <b>{$recordStart}</b> - <b>{$recordEnd}</b>
          {* total record count is not currently reliable due to Solr facet paging
             limitations -- for now, displaying it is disabled.
          {translate text='of'} <b>{$recordCount}</b>
           *}
          {translate text='for search'} <b>'{$lookfor|escape}'</b>
        {/if}
        </div>

        <div class="yui-u toggle">
          {translate text='Sort'}
          <select name="sort" onChange="document.location.href = this.options[this.selectedIndex].value;">
          {foreach from=$sortList item=sortData key=sortLabel}
            <option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected{/if}>{translate text=$sortData.desc}</option>
          {/foreach}
          </select>
        </div>
      </div>
      {* End Listing Options *}

        {foreach from=$recordSet item=record name="recordLoop"}
          {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
          <div class="result alt record{$smarty.foreach.recordLoop.iteration}">
          {else}
          <div class="result record{$smarty.foreach.recordLoop.iteration}">
          {/if}

            <div class="yui-ge">
              <div class="yui-u first">
                <a href="{$url}/Author/Home?author={$record.0|escape:"url"}">{$record.0|escape:"html"}</a>
              </div>
              <div class="yui-u">
                {$record.1}
              </div>
            </div>
          </div>
        {/foreach}
        
        {if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
      <b class="bbot"><b></b></b>
    </div>
  </div>
</div>