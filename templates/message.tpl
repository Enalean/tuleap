{*
 *  message.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Warning/error message template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

{if $standalone}
  {include file='header.tpl'}
{/if}

<div class="message {if $error}error{/if}">{$message}</div>

{if $standalone}
  {include file='footer.tpl'}
{/if}
