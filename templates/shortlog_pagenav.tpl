{*
 *  shortlog_page.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Shortlog view page nav template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 {if $headlink}<a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">{/if}HEAD{if $headlink}</a>{/if} &sdot; {if $prevlink}<a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}&pg={$prevpage}" accesskey="p" title="Alt-p">{/if}prev{if $prevlink}</a>{/if} &sdot; {if $nextlink}<a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}&pg={$nextpage}" accesskey="n" title="Alt-n">{/if}next{if $nextlink}</a>{/if}
 <br />
 </div>
 <div>
 <a href="{$SCRIPT_NAME}?p={$project}&a=summary" class="title">&nbsp;</a>
 </div>
 <table cellspacing="0">
