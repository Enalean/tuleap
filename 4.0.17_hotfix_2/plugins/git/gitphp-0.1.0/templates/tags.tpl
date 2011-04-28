{*
 *  tags.tpl
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
 <div>
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary" class="title">&nbsp;</a>
 </div>
 {* Display tags *}
 <table cellspacing="0">
   {section name=tag loop=$taglist}
     <tr class="{cycle values="light,dark"}">
       <td><i>{$taglist[tag].age_string}</i></td>
       <td><a href="{$SCRIPT_NAME}?p={$project}&a={$taglist[tag].reftype}&h={$taglist[tag].refid}" class="list"><b>{$taglist[tag].name}</b></a></td>
       <td>
         {if count($taglist[tag].comment) > 0}
           <a href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$taglist[tag].id}" class="list">{$taglist[tag].comment[0]}</a>
         {/if}
       </td>
       <td class="link">
         {if $taglist[tag].type == "tag"}
	   <a href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$taglist[tag].id}">tag</a> | 
	 {/if}
	 <a href="{$SCRIPT_NAME}?p={$project}&a={$taglist[tag].reftype}&h={$taglist[tag].refid}">{$taglist[tag].reftype}</a>
	 {if $taglist[tag].reftype == "commit"}
	   | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/tags/{$taglist[tag].name}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/tags/{$taglist[tag].name}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$taglist[tag].refid}&noheader=1">snapshot</a>
	 {/if}
       </td>
     </tr>
   {/section}
 </table>

 {include file='footer.tpl'}

