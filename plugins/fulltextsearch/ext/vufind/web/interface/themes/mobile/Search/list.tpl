{if $subpage}
  {if $recordCount}
  <span class="graytitle">
    {translate text="Showing"}
    <b>{$recordStart}</b> - <b>{$recordEnd}</b>
    {translate text='of'} <b>{$recordCount}</b>
  </span>
  {/if}

<ul class="pageitem autolist">

  {include file=$subpage}
  {if $pageLinks.all}
  <li class="autotext"><div class="pagination">{$pageLinks.all}</div></li>
  {/if}

</ul>

{else}
  {$pageContent}
{/if}
