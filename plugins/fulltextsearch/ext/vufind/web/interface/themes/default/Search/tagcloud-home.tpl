<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <b class="btop"><b></b></b>
      {* display tag cloud *}
      <h3>Browse By Tag</h3>
      {foreach from=$tagCloud item=font_sz key=tag}
        <span class="cloud{$font_sz}">
        <a href="{$path}/Search/Results?tag={$tag|escape:"url"}">{$tag|escape}</a>
        </span>
      {/foreach}
      <b class="bbot"><b></b></b>
    </div>
  </div>
</div>
