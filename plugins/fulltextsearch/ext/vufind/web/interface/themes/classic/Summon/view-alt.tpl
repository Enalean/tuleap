<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <div class="yui-ge">

        <div class="record">
          <a href="{$url}/Summon/Record?id={$id|escape:"url"}" class="backtosearch">&laquo; {translate text="Back to Record"}</a>

          {if $pageTitle}<h1>{$pageTitle}</h1>{/if}
          {include file="Summon/$subTemplate"}

        </div>

      </div>
    </div>
  </div>
</div>