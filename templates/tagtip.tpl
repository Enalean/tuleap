{*
 * Tagtip
 *
 * Tag tooltip template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}

<div>
{$resources->GetResource('tag')}: {$tag->GetName()}
<br />
{foreach from=$tag->GetComment() item=line}
<br />{$line}
{/foreach}
</div>
