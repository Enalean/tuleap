<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
        {if $user->cat_username}
          <h4>{translate text='Your Holds and Recalls'}</h4>
          {if is_array($recordList)}
          <ul class="filters">
          {foreach from=$recordList item=record name="recordLoop"}
            {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
            <li class="result alt">
            {else}
            <li class="result">
            {/if}
              <div class="yui-ge">
                <div class="yui-u first">
                  <img src="{$path}/bookcover.php?isn={$record.isbn|@formatISBN}&amp;size=small" class="alignleft">
    
                  <div class="resultitem">
                    <a href="{$url}/Record/{$record.id|escape:"url"}" class="title">{$record.title|escape}</a><br>
                    {if $record.author}
                    {translate text='by'}: <a href="{$url}/Author/Home?author={$record.author|escape:"url"}">{$record.author|escape}</a><br>
                    {/if}
                    <strong>{translate text='Created'}:</strong> {$record.createdate|escape} |
                    <strong>{translate text='Expires'}:</strong> {$record.expiredate|escape}
                    <br>
                    
                    {if is_array($record.format)}
                      {foreach from=$record.format item=format}
                        <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
                      {/foreach}
                    {else}
                      <span class="iconlabel {$record.format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$record.format}</span>
                    {/if}
                    
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
          {include file="MyResearch/catalog-login.tpl"}
        {/if}
    </div>
  </div>

  {include file="MyResearch/menu.tpl"}

</div>