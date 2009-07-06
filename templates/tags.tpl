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
   {* i18n: summary = summary *}
   {* i18n: shortlog = shortlog *}
   {* i18n: log = log *}
   {* i18n: commit = commit *}
   {* i18n: commitdiff = commitdiff *}
   {* i18n: tree = tree *}
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$localize.summary}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$head}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$head}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$head}">{$localize.tree}</a>
   <br /><br />
 </div>
 <div>
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary" class="title">&nbsp;</a>
 </div>
 {* Display tags *}
 <table cellspacing="0">
   {section name=tag loop=$taglist}
     <tr class="{cycle values="light,dark"}">
       <td><i>{$taglist[tag].age}</i></td>
       <td><a href="{$SCRIPT_NAME}?p={$project}&a={$taglist[tag].reftype}&h={$taglist[tag].refid}" class="list"><b>{$taglist[tag].name}</b></a></td>
       <td>
         {if count($taglist[tag].comment) > 0}
           <a href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$taglist[tag].id}" class="list">{$taglist[tag].comment[0]}</a>
         {/if}
       </td>
       <td class="link">
         {if $taglist[tag].type == "tag"}
	   {* i18n: tag = tag *}
	   <a href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$taglist[tag].id}">{$localize.tag}</a> | 
	 {/if}
	 <a href="{$SCRIPT_NAME}?p={$project}&a={$taglist[tag].reftype}&h={$taglist[tag].refid}">{$taglist[tag].reftype_localized}</a>
	 {if $taglist[tag].reftype == "commit"}
	   {* i18n: shortlog = shortlog *}
	   {* i18n: log = log *}
	   {* i18n: snapshot = snapshot *}
	   | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/tags/{$taglist[tag].name}">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/tags/{$taglist[tag].name}">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$taglist[tag].refid}">{$localize.snapshot}</a>
	 {/if}
       </td>
     </tr>
   {/section}
 </table>

 {include file='footer.tpl'}

