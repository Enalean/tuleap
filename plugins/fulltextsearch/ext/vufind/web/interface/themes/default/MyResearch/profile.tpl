<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
    <b class="btop"><b></b></b>
    {if $user->cat_username}
      <div class="resulthead"><h3>{translate text='Your Profile'}</h3></div>
      
      <div class="page">
      <table class="citation" width="100%">
        <tr><th style="width:100px;">{translate text='First Name'}:</th><td>{$profile.firstname|escape}</td></tr>
        <tr><th>{translate text='Last Name'}:</th><td>{$profile.lastname|escape}</td></tr>
        <tr><th>{translate text='Address'} 1:</th><td>{$profile.address1|escape}</td></tr>
        <tr><th>{translate text='Address'} 2:</th><td>{$profile.address2|escape}</td></tr>
        <tr><th>{translate text='Zip'}:</th><td>{$profile.zip|escape}</td></tr>
        <tr><th>{translate text='Phone Number'}:</th><td>{$profile.phone|escape}</td></tr>
        <tr><th>{translate text='Group'}:</th><td>{$profile.group|escape}</td></tr>
      </table>
    {else}
      <div class="page">
      {include file="MyResearch/catalog-login.tpl"}
    {/if}</div>
    <b class="bbot"><b></b></b>
    </div>
  
</div>
  {include file="MyResearch/menu.tpl"}

</div>