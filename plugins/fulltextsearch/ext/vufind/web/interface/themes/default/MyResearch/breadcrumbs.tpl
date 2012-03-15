
<a href="{$url}/MyResearch/Home">{translate text='Your Account'}</a> <span>&gt;</span>
{if $pageTemplate == 'view-alt.tpl'}
<em>{$pageTitle}</em>
{else}
<em>{$pageTemplate|replace:'.tpl':''|capitalize|translate}</em>
{/if}
<span>&gt;</span>
