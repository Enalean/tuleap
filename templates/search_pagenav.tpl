{*
 *  search_pagenav.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Log view page nav template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 {if $firstlink}<a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}">{/if}first{if $firstlink}</a>{/if} &sdot; {if $prevlink}<a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}{if $prevpage}&pg={$prevpage}{/if}" accesskey="p" title="Alt-p">{/if}prev{if $prevlink}</a>{/if} &sdot; {if $nextlink}<a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}&pg={$nextpage}" accesskey="n" title="Alt-n">{/if}next{if $nextlink}</a>{/if}
 <br />
 </div>
