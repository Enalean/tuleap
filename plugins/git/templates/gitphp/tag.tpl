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
   {include file='nav.tpl' commit=$head treecommit=$head}
   <br /><br />
 </div>
 {* Tag data *}
 {assign var=object value=$tag->GetObject()}
 {assign var=objtype value=$tag->GetType()}
 <div class="title">
   {if $objtype == 'blob'}
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$object->GetHash()|urlencode}" class="title">{$tag->GetName()|escape}</a>
   {else}
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$object->GetHash()|urlencode}" class="title">{$tag->GetName()|escape}</a>
   {/if}
 </div>
 <div class="title_text">
   <table cellspacing="0">
     <tr>
       <td>{t}object{/t}</td>
       {if $objtype == 'commit'}
         <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$object->GetHash()|urlencode}" class="list">{$object->GetHash()|escape}</a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$object->GetHash()|urlencode}">{t}commit{/t}</a></td>
       {elseif $objtype == 'tag'}
         <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tag&amp;h={$object->GetName()|urlencode}" class="list">{$object->GetHash()|escape}</a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tag&amp;h={$object->GetName()|urlencode}">{t}tag{/t}</a></td>
       {elseif $objtype == 'blob'}
         <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$object->GetHash()|urlencode}" class="list">{$object->GetHash()|escape}</a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$object->GetHash()|urlencode}">{t}blob{/t}</a></td>
       {/if}
     </tr>
     {if $tag->GetTagger()|escape}
       <tr>
         <td>{t}author{/t}</td>
	 <td>{$tag->GetTagger()|escape}</td>
       </tr>
       <tr>
         <td></td>
	 <td> {$tag->GetTaggerEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"} 
	 {assign var=hourlocal value=$tag->GetTaggerLocalEpoch()|date_format:"%H"}
	 {if $hourlocal < 6}
	 (<span class="latenight">{$tag->GetTaggerLocalEpoch()|date_format:"%R"}</span> {$tag->GetTaggerTimezone()|escape})
	 {else}
	 ({$tag->GetTaggerLocalEpoch()|date_format:"%R"} {$tag->GetTaggerTimezone()|escape})
	 {/if}
         </td>
       </tr>
     {/if}
   </table>
 </div>
 <div class="page_body">
   {assign var=bugpattern value=$project->GetBugPattern()}
   {assign var=bugurl value=$project->GetBugUrl()}
   {foreach from=$tag->GetComment() item=line}
     {if strncasecmp(trim($line),'-----BEGIN PGP',14) == 0}
     <span class="pgpSig">
     {/if}
     {$line|htmlspecialchars|buglink:$bugpattern:$bugurl}<br />
     {if strncasecmp(trim($line),'-----END PGP',12) == 0}
     </span>
     {/if}
   {/foreach}
 </div>

 {include file='footer.tpl'}

