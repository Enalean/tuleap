{*
 *  tag.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tag view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* Nav *}
 <div class="page_nav">
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$head}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$head}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$head}">tree</a>
   <br /><br />
 </div>
 {* Tag data *}
 <div>
   <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}" class="title">{$tag.name}</a>
 </div>
 <div class="title_text">
   <table cellspacing="0">
     <tr>
       <td>object</td>
       <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project}&a={$tag.type}&h={$tag.object}" class="list">{$tag.object}</a></td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a={$tag.type}&h={$tag.object}">{$tag.type}</a></td>
     </tr>
     {if $tag.author}
       <tr>
         <td>author</td>
	 <td>{$tag.author}</td>
       </tr>
       <tr>
         <td></td>
	 <td> {$datedata.rfc2822} ({if $datedata.hour_local < 6}<span class="latenight">{/if}{$datedata.hour_local}:{$datedata.minute_local}{if $datedata.hour_local < 6}</span>{/if} {$datedata.tz_local})
         </td>
       </tr>
     {/if}
   </table>
 </div>
 <div class="page_body">
   {foreach from=$tag.comment item=line}
     {$line}<br />
   {/foreach}
 </div>

 {include file='footer.tpl'}

