{*
 *  log_pagenav.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Log view page nav template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 {if $headlink}<a href="{$SCRIPT_NAME}?p={$project}&a=log">{/if}HEAD{if $headlink}</a>{/if} &sdot; {if $prevlink}<a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}&pg={$prevpage}" accesskey="p" title="Alt-p">{/if}prev{if $prevlink}</a>{/if} &sdot; {if $nextlink}<a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}&pg={$nextpage}" accesskey="n" title="Alt-n">{/if}next{if $nextlink}</a>{/if}
 <br />
 </div>
