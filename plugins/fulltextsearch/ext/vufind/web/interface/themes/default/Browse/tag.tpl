<script language="JavaScript" type="text/javascript" src="{$path}/services/Browse/ajax.js"></script>

<div id="bd">
  <div id="yui-main" class="content">
    <div class="contentbox" style="margin-right: 15px;">

      <div class="yui-g">
        <div class="yui-g first" style="background-color:#EEE;">
          <div class="yui-u first">
            <div class="browseNav" style="margin: 0px;">
            {include file="Browse/top_list.tpl" currentAction="Tag"}
            </div>
          </div>
          <div class="yui-u" id="browse2">
            <div class="browseNav" style="margin: 0px;">
            <ul class="browse" id="list2">
              <li {if $findby == "alphabetical"} class="active"{/if}><a href="{$url}/Browse/Tag?findby=alphabetical">{translate text="By Alphabetical"}</a></li>
              <li {if $findby == "popularity"} class="active"{/if}><a href="{$url}/Browse/Tag?findby=popularity">{translate text="By Popularity"}</a></li>
              <li {if $findby == "recent"} class="active"{/if}><a href="{$url}/Browse/Tag?findby=recent">{translate text="By Recent"}</a></li>
            </ul>
            </div>
          </div>
        </div>
        <div class="yui-g">
        {if !empty($alphabetList)}
          <div class="yui-u first" id="browse3">
            <div class="browseNav" style="margin: 0px;">
            <ul class="browse" id="list3">
            {foreach from=$alphabetList item=letter}
              <li {if $startLetter == $letter}class="active" {/if}style="float: none;">
                <a href="{$url}/Browse/Tag?findby=alphabetical&amp;letter={$letter|escape:"url"}">{$letter|escape:"html"}</a>
              </li>
            {/foreach}
            </ul>
            </div>
          </div>
        {/if}
          <div class="yui-u{if empty($alphabetList)} first{/if}" id="browse4">
            <div class="browseNav" style="margin: 0px;">
            <ul class="browse" id="list4">
            {foreach from=$tagList item=tag}
              <li style="float: none;"><a href="{$url}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"} ({$tag->cnt})</a></li>
            {/foreach}
            </ul>
            </div>
          </div>
        </div>
      </div>
  
    </div>
  </div>
</div>