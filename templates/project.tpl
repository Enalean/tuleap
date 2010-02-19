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
   summary | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$head}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$head}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree">tree</a>
   <br /><br />
 </div>
 <div class="title">&nbsp;</div>
 {* Project brief *}
 <table cellspacing="0">
   <tr><td>description</td><td>{$description}</td></tr>
   <tr><td>owner</td><td>{$owner}</td></tr>
   <tr><td>last change</td><td>{$lastchange}</td></tr>
   {if $cloneurl}
     <tr><td>clone url</td><td>{$cloneurl}</td></tr>
   {/if}
   {if $pushurl}
     <tr><td>push url</td><td>{$pushurl}</td></tr>
   {/if}
 </table>
 <div>
   <a class="title" href="{$SCRIPT_NAME}?p={$project}&a=shortlog">shortlog</a>
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
         <td><em>{$revlist[rev].commitage}</em></td>
         <td><em>{$revlist[rev].commitauthor}</em></td>
         <td>
           <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$revlist[rev].commit}" class="list" {if $revlist[rev].title}title="{$revlist[rev].title}"{/if}><strong>{$revlist[rev].title_short}
             {if $revlist[rev].commitref}
               <span class="tag">{$revlist[rev].commitref}</span>
             {/if}
           </strong>
         </td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$revlist[rev].commit}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$revlist[rev].commit}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$revlist[rev].commit}&hb={$revlist[rev].commit}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$revlist[rev].commit}">snapshot</a></td>
       </tr>
     {/if}
   {/section}
 </table>
 {if $taglist}
   {* Tags *}
   <div>
     <a href="{$SCRIPT_NAME}?p={$project}&a=tags" class="title">tags</a>
   </div>
   <table cellspacing="0">
     {section name=tag max=17 loop=$taglist}
       <tr class="{cycle name=tags values="light,dark"}">
         {if $smarty.section.tag.index == 16}
           <td><a href="{$SCRIPT_NAME}?p={$project}&a=tags">...</a></td>
         {else}
	   {assign var=object value=$taglist[tag]->GetObject()}
           <td><em>{$object->GetAge()|agestring}</em></td>
           <td><a href="{$SCRIPT_NAME}?p={$project}&a={$taglist[tag]->GetType()}&h={$object->GetHash()}" class="list"><strong>{$taglist[tag]->GetName()}</strong></a></td>
           <td>
	     {assign var=comment value=$taglist[tag]->GetComment()}
             {if count($comment) > 0}
               <a class="list" href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$taglist[tag]->GetName()}">{$comment[0]}</a>
             {/if}
           </td>
           <td class="link">
             {if !$taglist[tag]->LightTag()}
   	       <a href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$taglist[tag]->GetName()}">tag</a> | 
             {/if}
             <a href="{$SCRIPT_NAME}?p={$project}&a={$taglist[tag]->GetType()}&h={$taglist[tag]->GetHash()}">{$taglist[tag]->GetType()}</a>
	     {if $taglist[tag]->GetType() == "commit"}
	      | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/tags/{$taglist[tag]->GetName()}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/tags/{$taglist[tag]->GetName()}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$object->GetHash()}">snapshot</a>{/if}
           </td>
         {/if}
       </tr>
     {/section}
   </table>
 {/if}
 {if $headlist}
   {* Heads *}
   <div>
     <a href="{$SCRIPT_NAME}?p={$project}&a=heads" class="title">heads</a>
   </div>
   <table cellspacing="0">
     {section name=head max=17 loop=$headlist}
       <tr class="{cycle name=heads values="light,dark"}">
         {if $smarty.section.head.index == 16}
           <td><a href="{$SCRIPT_NAME}?p={$project}&a=heads">...</a></td>
         {else}
           <td><em>{$headlist[head].age_string}</em></td>
           <td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$headlist[head].name}" class="list"><strong>{$headlist[head].name}</strong></td>
           <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$headlist[head].name}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/heads/{$headlist[head].name}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h=refs/heads/{$headlist[head].name}&hb={$headlist[head].name}">tree</a></td>
         {/if}
       </tr>
     {/section}
   </table>
 {/if}

 {include file='footer.tpl'}

