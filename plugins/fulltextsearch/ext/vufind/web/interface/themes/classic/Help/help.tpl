<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="en">
  <head>
    <title>{translate text="MyResearch Help"}</title>
    {css media="screen" filename="help.css"}
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
  </head>
  <body>
    {if $warning}
      <p class="warning">
        {translate text='Sorry, but the help you requested is unavailable in your language.'}
      </p>
    {/if}
    {include file="$pageTemplate"}
  </body>
</html>
