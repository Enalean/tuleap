{*
 *  tree.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tree view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='main.tpl'}

{block name=main}

 {* Nav *}
   <div class="page_nav">
     {include file='nav.tpl' current='tree' logcommit=$commit}
     <br /><br />
   </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$tree target='tree'}
 
 <div class="page_body">
   {* List files *}
<table cellspacing="0" class="treeTable">
     {include file='treelist.tpl'}
</table>
 </div>

{/block}
