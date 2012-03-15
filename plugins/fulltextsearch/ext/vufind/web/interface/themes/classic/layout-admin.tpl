<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="{$language}">
  <head>
    <title>VuFind Administration - {$pageTitle}</title>
    {css media="screen" filename="styles.css"}
    {css media="print" filename="print.css"}
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
  </head>

  <body>
  
    <div id="doc2" class="yui-t5"> <!-- Change id for page width, class for menu layout. -->

      <div id="hd">
        <!-- Your header. Could be an include. -->
        <a href="{$url}"><img src="{$path}/images/vufind.jpg" alt="vufinder"></a>
        Administration
      </div>
    
      {include file="$module/$pageTemplate"}

      <div id="ft">
      </div>
      
    </div>
  </body>
</html>