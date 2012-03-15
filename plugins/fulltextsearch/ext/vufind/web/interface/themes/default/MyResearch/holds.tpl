<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
    <b class="btop"><b></b></b>
      {if $user->cat_username}
        <div class="resulthead"><h3>{translate text='Your Holds and Recalls'}</h3></div>
        <div class="page">
        {if is_array($recordList)}
        <ul class="filters">
        {foreach from=$recordList item=resource name="recordLoop"}
          {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
          <li class="result alt">
          {else}
          <li class="result">
          {/if}
            <div class="yui-ge">
              <div class="yui-u first">
                <img src="{$path}/bookcover.php?isn={$resource.isbn.0|@formatISBN}&amp;size=small" class="alignleft">

                <div class="resultitem">
                  <a href="{$url}/Record/{$resource.id|escape:"url"}" class="title">{$resource.title|escape}</a><br>
                  {if $resource.author}
                  {translate text='by'}: <a href="{$url}/Author/Home?author={$resource.author|escape:"url"}">{$resource.author|escape}</a><br>
                  {/if}
                  {if $resource.tags}
                  {translate text='Your Tags'}:
                  {foreach from=$resource.tags item=tag name=tagLoop}
                    <a href="{$url}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape}</a>{if !$smarty.foreach.tagLoop.last},{/if}
                  {/foreach}
                  <br>
                  {/if}
                  {if $resource.notes}
                  {translate text='Notes'}: {$resource.notes|escape}<br>
                  {/if}

                  {if is_array($resource.format)}
                    {foreach from=$resource.format item=format}
                      <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
                    {/foreach}
                  {else}
                    <span class="iconlabel {$resource.format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$resource.format}</span>
                  {/if}

                  <br>

                 <b>{translate text='Created'}:</b> {$resource.createdate|escape} |
                 <b>{translate text='Expires'}:</b> {$resource.expiredate|escape}

                </div>
              </div>
            </div>
          </li>
        {/foreach}
        </ul>
        {else}
        {translate text='You do not have any holds or recalls placed'}.
        {/if}
      {else}
        <div class="page">
        {include file="MyResearch/catalog-login.tpl"}
      {/if}</div>
    <b class="bbot"><b></b></b>
    </div>
  </div>

  {include file="MyResearch/menu.tpl"}

</div>
