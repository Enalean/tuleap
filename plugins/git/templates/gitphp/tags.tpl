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
   {include file='nav.tpl' commit=$head treecommit=$head}
   <br /><br />
 </div>

{include file='title.tpl' target='tree'}
 
 {* Display tags *}

 {include file='taglist.tpl'}

 {include file='footer.tpl'}

