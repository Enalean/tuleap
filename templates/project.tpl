{*
 *  project.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project summary template
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
   {$localize.summary} | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$head}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$head}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree">{$localize.tree}</a>
   <br /><br />
 </div>
 <div class="title">&nbsp;</div>
 {* Project brief *}
 <table cellspacing="0">
   {* i18n: description = description *}
   {* i18n: owner = owner *}
   {* i18n: lastchange = last change *}
   <tr><td>{$localize.description}</td><td>{$description}</td></tr>
   <tr><td>{$localize.owner}</td><td>{$owner}</td></tr>
   <tr><td>{$localize.lastchange}</td><td>{$lastchange}</td></tr>
 </table>
 <div>
   {* i18n: shortlog = shortlog *}
   <a class="title" href="{$SCRIPT_NAME}?p={$project}&a=shortlog">{$localize.shortlog}</a>
 </div>
 <table cellspacing="0">
   {* Recent revisions *}
   {section name=rev max=17 loop=$revlist}
     {if $smarty.section.rev.index == 16}
       <tr class="light">
         <td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">...</a></td>
       </tr>
     {else}
         <tr class="{cycle name=revs values="light,dark"}">
         <td><i>{$revlist[rev].commitage}</i></td>
         <td><i>{$revlist[rev].commitauthor}</i></td>
         <td>
           <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$revlist[rev].commit}" class="list" {if $revlist[rev].title}title="{$revlist[rev].title}"{/if}><b>{$revlist[rev].title_short}
             {if $revlist[rev].commitref}
               <span class="tag">{$revlist[rev].commitref}</span>
             {/if}
           </b></a>
         </td>
	 {* i18n: snapshot = snapshot *}
	 {* i18n: tree = tree *}
	 {* i18n: commit = commit *}
	 {* i18n: commitdiff = commitdiff *}
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$revlist[rev].commit}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$revlist[rev].commit}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$revlist[rev].commit}&hb={$revlist[rev].commit}">{$localize.tree}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$revlist[rev].commit}">{$localize.snapshot}</a></td>
       </tr>
     {/if}
   {/section}
 </table>
 {if $taglist}
   {* Tags *}
   <div>
     {* i18n: tags = tags *}
     <a href="{$SCRIPT_NAME}?p={$project}&a=tags" class="title">{$localize.tags}</a>
   </div>
   <table cellspacing="0">
     {section name=tag max=17 loop=$taglist}
       <tr class="{cycle name=tags values="light,dark"}">
         {if $smarty.section.tag.index == 16}
           <td><a href="{$SCRIPT_NAME}?p={$project}&a=tags">...</a></td>
         {else}
           <td><i>{$taglist[tag].age}</i></td>
           <td><a href="{$SCRIPT_NAME}?p={$project}&a={$taglist[tag].reftype}&h={$taglist[tag].refid}" class="list"><b>{$taglist[tag].name}</b></a></td>
           <td>
             {if $taglist[tag].comment}
               <a class="list" href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$taglist[tag].id}">{$taglist[tag].comment}</a>
             {/if}
           </td>
           <td class="link">
             {if $taglist[tag].type == "tag"}
	       {* i18n: tag = tag *}
   	       <a href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$taglist[tag].id}">{$localize.tag}</a> | 
             {/if}
	     {* i18n: shortlog = shortlog *}
	     {* i18n: log = log *}
	     {* i18n: snapshot = snapshot *}
             <a href="{$SCRIPT_NAME}?p={$project}&a={$taglist[tag].reftype}&h={$taglist[tag].refid}">{$taglist[tag].reftype_localized}</a>{if $taglist[tag].reftype == "commit"} | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/tags/{$taglist[tag].name}">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/tags/{$taglist[tag].name}">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$taglist[tag].refid}">{$localize.snapshot}</a>{/if}
           </td>
         {/if}
       </tr>
     {/section}
   </table>
 {/if}
 {if $headlist}
   {* Heads *}
   <div>
     {* i18n: heads = heads *}
     <a href="{$SCRIPT_NAME}?p={$project}&a=heads" class="title">{$localize.heads}</a>
   </div>
   <table cellspacing="0">
     {section name=head max=17 loop=$headlist}
       <tr class="{cycle name=heads values="light,dark"}">
         {if $smarty.section.head.index == 16}
           <td><a href="{$SCRIPT_NAME}?p={$project}&a=heads">...</a></td>
         {else}
           <td><i>{$headlist[head].age}</i></td>
           <td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$headlist[head].name}" class="list"><b>{$headlist[head].name}</b></td>
	   {* i18n: shortlog = shortlog *}
	   {* i18n: log = log *}
	   {* i18n: tree = tree *}
           <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$headlist[head].name}">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/heads/{$headlist[head].name}">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h=refs/heads/{$headlist[head].name}&hb={$headlist[head].name}">{$localize.tree}</a></td>
         {/if}
       </tr>
     {/section}
   </table>
 {/if}

 {include file='footer.tpl'}

