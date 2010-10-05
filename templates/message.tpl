{*
 *  message.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Warning/error message template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

<div class="message {if $error}error{/if}">{$message}</div>

{include file='footer.tpl'}
