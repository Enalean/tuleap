<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title>{translate text='An error has occurred'}</title>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
</head>
<body>
<div align="center">
  <h2>{translate text="An error has occurred"}</h2>
    <p class="errorMsg">{$error->getMessage()}</p>
    {if $debug}
    <p class="errorStmt">{$error->getDebugInfo()}</p>
    {/if}
  <p>
    {translate text="Please contact the Library Reference Department for assistance"}<br>
    <a href="mailto:{$supportEmail}">{$supportEmail}</a>
  </p>
</div>
{if $debug}
  <h2>{translate text="Debug Information"}</h2>
  {assign var=errorCode value=$error->getCode()}
  {if $errorCode}
  <p class="errorMsg">{translate text="Code"}: {$errorCode}</p>
  {/if}
  <p>{translate text="Backtrace"}:</p>
  {foreach from=$error->backtrace item=trace}
    [{$trace.line}] {$trace.file}<br>
  {/foreach}
{/if}
</body>
</html>