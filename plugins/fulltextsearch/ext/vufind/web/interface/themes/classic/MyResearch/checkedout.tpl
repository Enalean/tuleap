<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
    
        {if $user->cat_username}
          <h4>{translate text='Your Checked Out Items'}</h4>
          {if $transList}
          <ul class="filters">
          {foreach from=$transList item=resource name="recordLoop"}
            {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
            <li class="result alt">
            {else}
            <li class="result">
            {/if}
              <div class="yui-ge">
                <div class="yui-u first">
                  <img src="{$path}/bookcover.php?isn={$resource.isbn|@formatISBN}&amp;size=small" class="alignleft">

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
                    
                    <b>{translate text='Due'}: {$resource.duedate|escape}</b>

                  </div>
                </div>

              </div>
            </li>
          {/foreach}
          </ul>
          {else}
          {translate text='You do not have any items checked out'}.
          {/if}
        {else}
          {include file="MyResearch/catalog-login.tpl"}
        {/if}

    
    </div>
  </div>

  {include file="MyResearch/menu.tpl"}

</div>
