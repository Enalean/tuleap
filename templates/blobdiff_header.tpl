{*
 *  blobdiff_header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blobdiff view header template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 {if $file}
 <div class="page_path"><b>/{$file}</b></div>
 {/if}
 <div class="page_body">
 <div class="diff_info">blob:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$hashparent}&hb={$hashbase}&f={$file}">{$hashparent}</a> -&gt; blob:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$hash}&hb={$hashbase}&f={$file}">{$hash}</a></div>
