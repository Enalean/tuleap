<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
    {if $user->cat_username}
      <table>
        <tr><th>{translate text='First Name'}:</th><td>{$profile.firstname|escape}</td></tr>
        <tr><th>{translate text='Last Name'}:</th><td>{$profile.lastname|escape}</td></tr>
        <tr><th>{translate text='Address'} 1:</th><td>{$profile.address1|escape}</td></tr>
        <tr><th>{translate text='Address'} 2:</th><td>{$profile.address2|escape}</td></tr>
        <tr><th>{translate text='Zip'}:</th><td>{$profile.zip|escape}</td></tr>
        <tr><th>{translate text='Phone Number'}:</th><td>{$profile.phone|escape}</td></tr>
        <tr><th>{translate text='Group'}:</th><td>{$profile.group|escape}</td></tr>
      </table>
    {else}
      {include file="MyResearch/catalog-login.tpl"}
    {/if}
    </div>
  </div>

  {include file="MyResearch/menu.tpl"}

</div>