<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta content="yes" name="apple-mobile-web-app-capable" />
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <meta content="minimum-scale=1.0, width=device-width, maximum-scale=0.6667, user-scalable=no" name="viewport" />
    <link href="{$path}/interface/themes/mobile/iWebKit/css/style.css" rel="stylesheet" type="text/css" />
    {css filename="extra_styles.css"}
    <script src="{$path}/interface/themes/mobile/iWebKit/javascript/functions.js" type="text/javascript"></script>
    <title>{$site.title}</title>
  </head>

  <body>
    <div id="topbar">
      {if !($module == "Search" && $pageTemplate == "home.tpl")}
      <div id="leftnav"><a href="{$path}/Search/Home"><img alt="home" src="{$path}/interface/themes/mobile/iWebKit/images/home.png" /></a></div>
      {/if}
      <div id="title">{$pageTitle}</div>
    </div>
    <div id="content">
      {include file="$module/$pageTemplate"}
    </div>
    <div id="footer">
      <a href="?ui=standard">{translate text="Go to Standard View"}</a>
    </div>
  </body>

</html>
