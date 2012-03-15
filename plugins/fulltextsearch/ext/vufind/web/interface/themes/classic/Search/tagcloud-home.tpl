<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      {* display tag cloud *}
      <h3>Browse By Tag</h3>
      {foreach from=$tagCloud item=font_sz key=tag}
        <span class="cloud{$font_sz}">
        <a href="{$path}/Search/Results?tag={$tag|escape:"url"}">{$tag|escape}</a>
        </span>
      {/foreach}
    </div>
  </div>
</div>
