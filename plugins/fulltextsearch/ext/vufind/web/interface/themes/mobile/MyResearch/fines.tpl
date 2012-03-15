    {if $user->cat_username}
      {$finesData}
    {else}
      {include file="MyResearch/catalog-login.tpl"}
    {/if}

  {include file="MyResearch/menu.tpl"}
