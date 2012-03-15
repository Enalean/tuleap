<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
    {if $user->cat_username}
      <h4>{translate text='Your Fines'}</h4>
      {$finesData}
    {else}
      {include file="MyResearch/catalog-login.tpl"}
    {/if}
    </div>
  </div>

  {include file="MyResearch/menu.tpl"}

</div>