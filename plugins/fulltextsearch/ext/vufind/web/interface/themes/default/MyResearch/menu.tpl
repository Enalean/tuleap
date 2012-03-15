<div class="yui-b">
  <div class="sidegroup">
    <h4>{translate text='Your Account'}</h4>
    <ul class="bulleted">
      <li{if $pageTemplate=="favorites.tpl"} class="active"{/if} style="float: none;"><a href="{$url}/MyResearch/Home">{translate text='Favorites'}</a></li>
      <li{if $pageTemplate=="checkedout.tpl"} class="active"{/if} style="float: none;"><a href="{$url}/MyResearch/CheckedOut">{translate text='Checked Out Items'}</a></li>
      <li{if $pageTemplate=="holds.tpl"} class="active"{/if} style="float: none;"><a href="{$url}/MyResearch/Holds">{translate text='Holds and Recalls'}</a></li>
      <li{if $pageTemplate=="fines.tpl"} class="active"{/if} style="float: none;"><a href="{$url}/MyResearch/Fines">{translate text='Fines'}</a></li>
      <li{if $pageTemplate=="profile.tpl"} class="active"{/if} style="float: none;"><a href="{$url}/MyResearch/Profile">{translate text='Profile'}</a></li>
      {* Only highlight saved searches as active if user is logged in: *}
      <li{if $user && $pageTemplate=="history.tpl"} class="active"{/if} style="float: none;"><a href="{$url}/Search/History?require_login">{translate text='history_saved_searches'}</a></li>
    </ul>
  </div>
</div>
