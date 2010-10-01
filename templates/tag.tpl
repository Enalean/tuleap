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
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$object->GetHash()}" class="title">{$tag->GetName()}</a>
 </div>
 <div class="title_text">
   <table cellspacing="0">
     <tr>
       <td>{t}object{/t}</td>
       {if $objtype == 'commit'}
         <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$object->GetHash()}" class="list">{$object->GetHash()}</a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$object->GetHash()}">{t}commit{/t}</a></td>
       {elseif $objtype == 'tag'}
         <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$object->GetName()}" class="list">{$object->GetHash()}</a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$object->GetName()}">{t}tag{/t}</a></td>
       {/if}
     </tr>
     {if $tag->GetTagger()}
       <tr>
         <td>{t}author{/t}</td>
	 <td>{$tag->GetTagger()}</td>
       </tr>
       <tr>
         <td></td>
	 <td> {$tag->GetTaggerEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"} 
	 {assign var=hourlocal value=$tag->GetTaggerLocalEpoch()|date_format:"%H"}
	 {if $hourlocal < 6}
	 (<span class="latenight">{$tag->GetTaggerLocalEpoch()|date_format:"%R"}</span> {$tag->GetTaggerTimezone()})
	 {else}
	 ({$tag->GetTaggerLocalEpoch()|date_format:"%R"} {$tag->GetTaggerTimezone()})
	 {/if}
         </td>
       </tr>
     {/if}
   </table>
 </div>
 <div class="page_body">
   {foreach from=$tag->GetComment() item=line}
     {$line}<br />
   {/foreach}
 </div>

 {include file='footer.tpl'}

