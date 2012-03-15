<div class="searchHome">
  <b class="btop"><b></b></b>
  <div class="searchHomeContent">
    <img src="{$path}/interface/themes/default/images/vufind_logo_large.gif" alt="VuFind">
    
    <div class="searchHomeForm">
      {include file="Search/searchbox.tpl"}
    </div>

  </div>
</div>

{if $facetList}
  <div class="searchHomeBrowseHeader">
    {foreach from=$facetList item=details key=field}
      {* Special case: extra-wide header for call number facets: *}
      <div{if $field == "callnumber-first" || $field == "dewey-hundreds"} class="searchHomeBrowseExtraWide"{/if}>
        <h2>{translate text="home_browse"} {translate text=$details.label}</h2>
      </div>
    {/foreach}
    <br clear="all">
  </div>
  
  <div class="searchHomeBrowse">
    <div class="searchHomeBrowseInner">
      {foreach from=$facetList item=details key=field}
        {assign var=list value=$details.sortedList}
        {* Special case: single, extra-wide column for Dewey call numbers... *}
        <div{if $field == "dewey-hundreds"} class="searchHomeBrowseExtraWide"{/if}>
          <ul>
            {* Special case: two columns for LC call numbers... *}
            {if $field == "callnumber-first"}
              {foreach from=$list item=url key=value name="callLoop"}
                <li><a href="{$url|escape}">{$value|escape}</a></li>
                {if $smarty.foreach.callLoop.iteration == 10}
                  </ul>
                  </div>
                  <div>
                  <ul>
                {/if}
              {/foreach}
            {else}
              {assign var=break value=false}
              {foreach from=$list item=url key=value name="listLoop"}
                {if $smarty.foreach.listLoop.iteration > 12}
                  {if !$break}
                    <li><a href="{$path}/Search/Advanced"><strong>{translate text="More options"}...</strong></a></li>
                    {assign var=break value=true}
                  {/if}
                {else}
                  <li><a href="{$url|escape}">{$value|escape}</a></li>
                {/if}
              {/foreach}
            {/if}
          </ul>
        </div>
      {/foreach}
      <br clear="all">
    </div>
    <b class="gbot"><b></b></b>
  </div>
{/if}
