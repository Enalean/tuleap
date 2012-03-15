<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
    <b class="btop"><b></b></b>
    {if $user->cat_username}
      <div class="resulthead"><h3>{translate text='Your Fines'}</h3></div>
      <div class="page">
      {$finesData}
    {else}
      <div class="page">
      {include file="MyResearch/catalog-login.tpl"}
    {/if}</div>
    <b class="bbot"><b></b></b>
    </div>
  </div>

  {include file="MyResearch/menu.tpl"}

</div>