<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <b class="btop"><b></b></b>
      <div class="yui-ge">

        <div class="record">
          {if !empty($recordId)}
            <a href="{$url}/Record/{$recordId|escape:"url"}/Home" class="backtosearch">&laquo; {translate text="Back to Record"}</a>
          {/if}

          {if $pageTitle}<h1>{$pageTitle}</h1>{/if}
          {include file="MyResearch/$subTemplate"}

        </div>

      </div>
      <b class="bbot"><b></b></b>
    </div>
  </div>
</div>