<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="{$userLang}">

{* We should hide the top search bar and breadcrumbs in some contexts: *}
{if ($module=="Search" || $module=="Summon" || $module=="WorldCat") && $pageTemplate=="home.tpl"}
    {assign var="showTopSearchBox" value=0}
    {assign var="showBreadcrumbs" value=0}
{else}
    {assign var="showTopSearchBox" value=1}
    {assign var="showBreadcrumbs" value=1}
{/if}

  <head>
    <title>{$pageTitle|truncate:64:"..."}</title>
    {if $addHeader}{$addHeader}{/if}
    <link rel="search" type="application/opensearchdescription+xml" title="Library Catalog Search" href="{$url}/Search/OpenSearch?method=describe">
    {css media="screen" filename="styles.css"}
    {css media="print" filename="print.css"}
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <script language="JavaScript" type="text/javascript">
      path = '{$url}';
    </script>

    <script language="JavaScript" type="text/javascript" src="{$path}/js/yui/yahoo-dom-event.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/yui/yahoo-min.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/yui/event-min.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/yui/connection-min.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/yui/dragdrop-min.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/scripts.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/rc4.js"></script>
    <script language="JavaScript" type="text/javascript" src="{$path}/js/ajax.yui.js"></script>
  </head>

  <body>

    {* LightBox *}
    <div id="lightboxLoading" style="display: none;">{translate text="Loading"}...</div>
    <div id="lightboxError" style="display: none;">{translate text="lightbox_error"}</div>
    <div id="lightbox" onClick="hideLightbox(); return false;"></div>
    <div id="popupbox" class="popupBox"><b class="btop"><b></b></b></div>
    {* End LightBox *}
    
    <div class="searchheader">
      <div class="searchcontent">
        <div class="alignright" style="text-align:right;">
          <div id="logoutOptions"{if !$user} style="display: none;"{/if}>
            <a href="{$path}/MyResearch/Home">{translate text="Your Account"}</a> |
            <a href="{$path}/MyResearch/Logout">{translate text="Log Out"}</a>
          </div>
          <div id="loginOptions"{if $user} style="display: none;"{/if}>
            {if $authMethod == 'Shibboleth'}
              <a href="{$sessionInitiator}">{translate text="Institutional Login"}</a>
            {else}
              <a href="{$path}/MyResearch/Home">{translate text="Login"}</a>
            {/if}
          </div>
          {if is_array($allLangs) && count($allLangs) > 1}
            <form method="post" name="langForm" action="">
              <select name="mylang" onChange="document.langForm.submit();">
                {foreach from=$allLangs key=langCode item=langName}
                  <option value="{$langCode}"{if $userLang == $langCode} selected{/if}>{translate text=$langName}</option>
                {/foreach}
              </select>
              <noscript><input type="submit" value="{translate text="Set"}" /></noscript>
            </form>
          {/if}
        </div>

        {if $showTopSearchBox}
          <a href="{$url}"><img src="{$path}/interface/themes/default/images/vufind_logo.png" alt="VuFind" class="alignleft"></a>
          {if $pageTemplate != 'advanced.tpl'}
            {if $module=="Summon"}
              {include file="Summon/searchbox.tpl"}
            {elseif $module=="WorldCat"}
              {include file="WorldCat/searchbox.tpl"}
            {else}
              {include file="Search/searchbox.tpl"}
            {/if}
          {/if}
        {/if}

        <br clear="all">
      </div>
    </div>
    
    {if $showBreadcrumbs}
    <div class="breadcrumbs">
      <div class="breadcrumbinner">
        <a href="{$url}">{translate text="Home"}</a> <span>&gt;</span>
        {include file="$module/breadcrumbs.tpl"}
      </div>
    </div>
    {/if}
    
    <div id="doc2" class="yui-t4"> {* Change id for page width, class for menu layout. *}

      {if $useSolr || $useWorldcat || $useSummon}
      <div id="toptab">
        <ul>
          {if $useSolr}
          <li{if $module != "WorldCat" && $module != "Summon"} class="active"{/if}><a href="{$url}/Search/Results?lookfor={$lookfor|escape:"url"}">{translate text="University Library"}</a></li>
          {/if}
          {if $useWorldcat}
          <li{if $module == "WorldCat"} class="active"{/if}><a href="{$url}/WorldCat/Search?lookfor={$lookfor|escape:"url"}">{translate text="Other Libraries"}</a></li>
          {/if}
          {if $useSummon}
          <li{if $module == "Summon"} class="active"{/if}><a href="{$url}/Summon/Search?lookfor={$lookfor|escape:"url"}">{translate text="Journal Articles"}</a></li>
          {/if}
        </ul>
      </div>
      <div style="clear: left;"></div>
      {/if}

      {include file="$module/$pageTemplate"}

      <div id="ft">
      {include file="footer.tpl"}
      </div> {* End ft *}

    </div> {* End doc *}
    
  </body>
</html>