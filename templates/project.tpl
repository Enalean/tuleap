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
   summary | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$head->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$head->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree">tree</a>
   <br /><br />
 </div>

 {include file='title.tpl'}

 {* Project brief *}
 <table cellspacing="0">
   <tr><td>description</td><td>{$project->GetDescription()}</td></tr>
   <tr><td>owner</td><td>{$project->GetOwner()}</td></tr>
   <tr><td>last change</td><td>{$head->GetCommitterEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"}</td></tr>
   {if $project->GetCloneUrl()}
     <tr><td>clone url</td><td>{$project->GetCloneUrl()}</td></tr>
   {/if}
   {if $project->GetPushUrl()}
     <tr><td>push url</td><td>{$project->GetPushUrl()}</td></tr>
   {/if}
 </table>

 {include file='title.tpl' target='shortlog'}
 
 <table cellspacing="0">
   {foreach from=$revlist item=rev}
     <tr class="{cycle name=revs values="light,dark"}">
     <td><em>{$rev->GetAge()|agestring}</em></td>
     <td><em>{$rev->GetAuthorName()}</em></td>
     <td>
       <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$rev->GetHash()}" class="list" {if strlen($rev->GetTitle()) > 50}title="{$rev->GetTitle()}"{/if}><strong>{$rev->GetTitle(50)}</strong></a>
       <span class="refs">
       {foreach item=revhead from=$rev->GetHeads()}
         <span class="head">
           <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/heads/{$revhead->GetName()}">{$revhead->GetName()}</a>
         </span>
       {/foreach}
       {foreach item=revtag from=$rev->GetTags()}
         <span class="tag">
           <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$revtag->GetName()}">{$revtag->GetName()}</a>
         </span>
       {/foreach}
       </span>
     </td>
     <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$rev->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$rev->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h={$rev->GetHash()}&hb={$rev->GetHash()}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=snapshot&h={$rev->GetHash()}">snapshot</a></td>
     </tr>
   {/foreach}
   {if $hasmorerevs}
     <tr class="light">
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog">...</a></td>
     </tr>
   {/if}
 </table>
 {if $taglist}
   {* Tags *}
  
  {include file='title.tpl' target='tags'}
   
   <table cellspacing="0">
     {section name=tag max=17 loop=$taglist}
       <tr class="{cycle name=tags values="light,dark"}">
         {if $smarty.section.tag.index == 16}
           <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tags">...</a></td>
         {else}
	   {assign var=object value=$taglist[tag]->GetObject()}
           <td><em>{$object->GetAge()|agestring}</em></td>
           <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a={$taglist[tag]->GetType()}&h={$object->GetHash()}" class="list"><strong>{$taglist[tag]->GetName()}</strong></a></td>
           <td>
	     {assign var=comment value=$taglist[tag]->GetComment()}
             {if count($comment) > 0}
               <a class="list" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$taglist[tag]->GetName()}">{$comment[0]}</a>
             {/if}
           </td>
           <td class="link">
             {if !$taglist[tag]->LightTag()}
   	       <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$taglist[tag]->GetName()}">tag</a> | 
             {/if}
             <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a={$taglist[tag]->GetType()}&h={$taglist[tag]->GetHash()}">{$taglist[tag]->GetType()}</a>
	     {if $taglist[tag]->GetType() == "commit"}
	      | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/tags/{$taglist[tag]->GetName()}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h=refs/tags/{$taglist[tag]->GetName()}">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=snapshot&h={$object->GetHash()}">snapshot</a>{/if}
           </td>
         {/if}
       </tr>
     {/section}
   </table>
 {/if}
 {if $headlist}
   {* Heads *}

  {include file='title.tpl' target='heads'}

   <table cellspacing="0">
     {section name=head max=17 loop=$headlist}
       <tr class="{cycle name=heads values="light,dark"}">
         {if $smarty.section.head.index == 16}
           <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=heads">...</a></td>
         {else}
	   {assign var=headcommit value=$headlist[head]->GetCommit()}
           <td><em>{$headcommit->GetAge()|agestring}</em></td>
           <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/heads/{$headlist[head]->GetName()}" class="list"><strong>{$headlist[head]->GetName()}</strong></td>
           <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/heads/{$headlist[head]->GetName()}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h=refs/heads/{$headlist[head]->GetName()}">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h=refs/heads/{$headlist[head]->GetName()}&hb={$headcommit->GetHash()}">tree</a></td>
         {/if}
       </tr>
     {/section}
   </table>
 {/if}

 {include file='footer.tpl'}

