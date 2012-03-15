<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <b class="btop"><b></b></b>
      <div class="yui-ge">

        <div class="record">
          <a href="{$url}/Record/{$id|escape:"url"}/Home" class="backtosearch">&laquo; {translate text="Back to Record"}</a>

          {if $pageTitle}<h1>{$pageTitle}</h1>{/if}
          {include file="Record/$subTemplate"}

        </div>

      </div>
      <b class="bbot"><b></b></b>
    </div>
  </div>
</div>