<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <div class="yui-ge">

        <div class="record">
          {if $lastsearch}
            <p>  <a href="{$lastsearch|escape}" class="backtosearch">&laquo; {translate text="Back to Search Results"}</a></p>
          {/if}

          {include file="Search/$subTemplate"}

        </div>

      </div>
    </div>
  </div>
</div>