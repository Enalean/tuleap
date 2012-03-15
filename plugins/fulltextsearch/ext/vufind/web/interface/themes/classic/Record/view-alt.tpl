<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <div class="yui-ge">

        <div class="record">
          <a href="{$url}/Record/{$id|escape:"url"}/Home" class="backtosearch">&laquo; {translate text="Back to Record"}</a>

          {if $pageTitle}<h1>{$pageTitle}</h1>{/if}
          {include file="Record/$subTemplate"}

        </div>

      </div>
    </div>
  </div>
</div>