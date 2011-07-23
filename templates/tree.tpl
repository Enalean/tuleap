{*
 *  tree.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tree view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=javascript}
    <script src="js/ext/require.js"></script>
    {include file='jsconst.tpl'}
    <script type="text/javascript">
    require({ldelim}
    	baseUrl: 'js',
	paths: {ldelim}
	  {if file_exists("js/tree.min.js")}
	  	tree: "tree.min",
	  {/if}
	{if $googlejs}
		jquery: 'https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min'
	{else}
		jquery: 'ext/jquery-1.4.2.min'
	{/if}
	{rdelim},
	priority: ['jquery']
    {rdelim}, [
	  'tree'
      ]);
    </script>
{/block}

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
