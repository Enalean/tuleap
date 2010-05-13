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
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$head}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$head}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&hb={$head}">tree</a>
   <br /><br />
 </div>

{include file='title.tpl' target='summary'}
 
 {* Display tags *}
 <table cellspacing="0">
   {foreach name=tags from=$taglist item=tag}
     {assign var=object value=$tag->GetObject()}
     <tr class="{cycle values="light,dark"}">
       <td><em>{$object->GetAge()|agestring}</em></td>
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a={$tag->GetType()}&h={$object->GetHash()}" class="list"><strong>{$tag->GetName()}</strong></a></td>
       <td>
         {assign var=comment value=$tag->GetComment()}
         {if count($comment) > 0}
           <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$tag->GetName()}" class="list">{$comment[0]}</a>
         {/if}
       </td>
       <td class="link">
         {if !$tag->LightTag()}
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$tag->GetName()}">tag</a> | 
	 {/if}
	 <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a={$tag->GetType()}&h={$object->GetHash()}">{$tag->GetType()}</a>
	 {if $tag->GetType() == "commit"}
	   | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/tags/{$tag->GetName()}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h=refs/tags/{$tag->GetName()}">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=snapshot&h={$object->GetHash()}">snapshot</a>
	 {/if}
       </td>
     </tr>
   {/foreach}
 </table>

 {include file='footer.tpl'}

