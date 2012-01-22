{*
 *  message.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Warning/error message template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='main.tpl'}

{block name=main}

<div class="message {if $error}error{/if}">{$message|escape}</div>

{/block}
