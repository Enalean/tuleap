<div class="yui-ge">
  <div class="yui-u first">
    <img src="{$path}/bookcover.php?isn={$listISBN|escape:"url"}&amp;size=small" class="alignleft">

    <div class="resultitem">
      <a href="{$url}/Record/{$listId|escape:"url"}" class="title">{$listTitle|escape}</a><br>
      {if $listAuthor}
        {translate text='by'}: <a href="{$url}/Author/Home?author={$listAuthor|escape:"url"}">{$listAuthor|escape}</a><br>
      {/if}
      {if $listTags}
        {translate text='Your Tags'}:
        {foreach from=$listTags item=tag name=tagLoop}
          <a href="{$url}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a>{if !$smarty.foreach.tagLoop.last},{/if}
        {/foreach}
        <br>
      {/if}
      {if $listNotes}
        {translate text='Notes'}: 
        {foreach from=$listNotes item=note}
          {$note|escape:"html"}<br>
        {/foreach}
      {/if}

      {foreach from=$listFormats item=format}
        <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
      {/foreach}
    </div>
  </div>

  {if $listEditAllowed}
    <div class="yui-u">
      <a href="{$url}/MyResearch/Edit?id={$listId|escape:"url"}{if !is_null($listSelected)}&amp;list_id={$listSelected|escape:"url"}{/if}" class="edit tool">{translate text='Edit'}</a>
      {* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
      <a
      {if is_null($listSelected)}
        href="{$url}/MyResearch/Home?delete={$listId|escape:"url"}"
      {else}
        href="{$url}/MyResearch/MyList/{$listSelected|escape:"url"}?delete={$listId|escape:"url"}"
      {/if}
      class="delete tool" onClick="return confirm('Are you sure you want to delete this?');">{translate text='Delete'}</a>
    </div>
  {/if}
</div>
