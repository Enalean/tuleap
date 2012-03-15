{if $similarAuthors}
  <div class="yui-g resulthead authorbox">
  <p>{translate text='Author Results for'} <strong>{$lookfor|escape}</strong></p>
    <div class="yui-u first">
    {foreach from=$similarAuthors.list item=author name=authorLoop}
      {if $smarty.foreach.authorLoop.iteration == 6}
        </div>
        <div class="yui-u">
      {/if}
      <a href="{$author.url|escape}">{$author.value|escape}</a><br>
    {/foreach}
    </div>
    <a href="{$similarAuthors.lookfor|escape}" style="float: right;">{translate text='see all'}{if $similarAuthors.count} ({$similarAuthors.count}){/if}</a>
  </div>
{/if}