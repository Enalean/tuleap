<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">

      <b class="btop"><b></b></b>

      <div class="yui-ge">
        <div class="yui-u first">

      {* Internal Grid *}
      <div class="resulthead"><h3 class="fav">{translate text='Your Favorites'}</h3></div>
          
      {if $resourceList}
      <div class="yui-gd resulthead">
        <div class="yui-u first">
        {if $recordCount}
          {translate text="Showing"}
          <b>{$recordStart}</b> - <b>{$recordEnd}</b>
          {translate text='of'} <b>{$recordCount}</b>
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
      <ul>
          {foreach from=$resourceList item=resource name="recordLoop"}
            <li class="result{if ($smarty.foreach.recordLoop.iteration % 2) == 0} alt{/if}">
              {* This is raw HTML -- do not escape it: *}
              {$resource}
            </li>
          {/foreach}
      </ul>
      {if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
      {else}
      <div class="page">{translate text='You do not have any saved resources'}</div>
      {/if}

        </div>
        <div class="yui-u">

      {if $listList}
      <div>
        <h3 class="list">{translate text='Your Lists'}</h3>
        <ul class="bulleted">
          {foreach from=$listList item=list}
          <li><a href="{$url}/MyResearch/MyList/{$list->id}">{$list->title|escape:"html"}</a> ({$list->cnt})</li>
          {/foreach}
        </ul>
      </div>
      {/if}

      {if $tagList}
      <div>
        <h4 class="tag">{translate text='Your Tags'}</h4>
        
        {if $tags}
          <ul>
          {foreach from=$tags item=tag}
            <li>{translate text='Tag'}: {$tag|escape:"html"}
            <a href="{$url}/MyResearch/Home?{foreach from=$tags item=mytag}{if $tag != $mytag}tag[]={$mytag|escape:"url"}&amp;{/if}{/foreach}">X</a>
            </li>
          {/foreach}
          </ul>
        {/if}
          
        <ul class="bulleted">
          {foreach from=$tagList item=tag}
          <li><a href="{$url}/MyResearch/Home?tag[]={$tag->tag|escape:"url"}{foreach from=$tags item=mytag}&amp;tag[]={$mytag|escape:"url"}{/foreach}">{$tag->tag|escape:"html"}</a> ({$tag->cnt})</li>
          {/foreach}
        </ul>
      </div>
      {/if}

        </div>
      </div>
        
      {* End of Internal Grid *}
      <br>
      <b class="bbot"><b></b></b>

    </div>


    {* End of first Body *}
    
  </div>
  
  {include file="MyResearch/menu.tpl"}
  

</div>
