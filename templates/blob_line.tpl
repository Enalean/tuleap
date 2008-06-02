{*
 *  blob_line.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blob view line template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <div class="pre">{if $nr < 10} {/if}{if $nr < 100} {/if}{if $nr < 1000} {/if}<a id="l{$nr}" href="#l{$nr}" class="linenr">{$nr}</a> {$line}</div>
