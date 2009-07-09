{*
 *  heads_nav.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Head view template
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
 <table cellspacing="0">
   {* Loop and display each head *}
   {foreach from=$headlist item=head}
     <tr class="{cycle values="light,dark"}">
       <td><i>{$head.age_string}</i></td>
       <td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$head.name}" class="list"><b>{$head.name}</b></a></td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$head.name}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/heads/{$head.name}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h=refs/heads/{$head.name}&hb={$head.name}">tree</a></td>
     </tr>
   {/foreach}
 </table>

 {include file='footer.tpl'}

