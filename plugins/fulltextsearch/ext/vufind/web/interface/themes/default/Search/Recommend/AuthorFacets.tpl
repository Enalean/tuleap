{if $similarAuthors}
  <div class="yui-g authorbox">
  <p>{translate text='Author Results for'} <strong>{$lookfor|escape}</strong></p>
    <div class="yui-u first">
    {foreach from=$similarAuthors.list item=author name=authorLoop}
      {if $smarty.foreach.authorLoop.iteration == 6}
        <br><a href="{$similarAuthors.lookfor|escape}"><strong>{translate text='see all'}{if $similarAuthors.count} {$similarAuthors.count}{/if} &raquo;</strong></a>
        </div>
        <div class="yui-u">
      {/if}
      <a href="{$author.url|escape}">{$author.value|escape}</a><br>
    {/foreach}
    </div>
  </div>
{/if}