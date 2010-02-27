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
   <tr><td>last change</td><td>{$lastchange|date_format:"%a, %d %b %Y %H:%M:%S %z"}</td></tr>
   {if $cloneurl}
     <tr><td>clone url</td><td>{$cloneurl}</td></tr>
   {/if}
   {if $pushurl}
     <tr><td>push url</td><td>{$pushurl}</td></tr>
   {/if}
 </table>
 <div class="title">
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
         <td><em>{$revlist[rev]->GetAge()|agestring}</em></td>
         <td><em>{$revlist[rev]->GetAuthorName()}</em></td>
         <td>
           <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$revlist[rev]->GetHash()}" class="list" title="{$revlist[rev]->GetTitle()}"><strong>{$revlist[rev]->GetTitle(50)}</strong></a>
	   <span class="refs">
	   {assign var=revheads value=$revlist[rev]->GetHeads()}
	   {if count($revheads) > 0}
	     {foreach item=revhead from=$revheads}
	     <span class="head">
	       <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$revhead->GetName()}">{$revhead->GetName()}</a>
	     </span>
	     {/foreach}
	   {/if}
	   {assign var=revtags value=$revlist[rev]->GetTags()}
	   {if count($revtags) > 0}
	     {foreach item=revtag from=$revtags}
	     <span class="tag">
	       <a href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$revtag->GetName()}">{$revtag->GetName()}</a>
	     </span>
	     {/foreach}
	   {/if}
	   </span>
         </td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$revlist[rev]->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$revlist[rev]->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$revlist[rev]->GetHash()}&hb={$revlist[rev]->GetHash()}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$revlist[rev]->GetHash()}">snapshot</a></td>
       </tr>
     {/if}
   {/section}
 </table>
 {if $taglist}
   {* Tags *}
   <div class="title">
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
   <div class="title">
     <a href="{$SCRIPT_NAME}?p={$project}&a=heads" class="title">heads</a>
   </div>
   <table cellspacing="0">
     {section name=head max=17 loop=$headlist}
       <tr class="{cycle name=heads values="light,dark"}">
         {if $smarty.section.head.index == 16}
           <td><a href="{$SCRIPT_NAME}?p={$project}&a=heads">...</a></td>
         {else}
	   {assign var=commit value=$headlist[head]->GetCommit()}
           <td><em>{$commit->GetAge()|agestring}</em></td>
           <td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$headlist[head]->GetName()}" class="list"><strong>{$headlist[head]->GetName()}</strong></td>
           <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$headlist[head]->GetName()}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/heads/{$headlist[head]->GetName()}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h=refs/heads/{$headlist[head]->GetName()}&hb={$commit->GetHash()}">tree</a></td>
         {/if}
       </tr>
     {/section}
   </table>
 {/if}

 {include file='footer.tpl'}

