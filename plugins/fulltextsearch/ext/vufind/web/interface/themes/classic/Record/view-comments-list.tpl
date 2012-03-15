{foreach from=$commentList item=comment}
  <li>
    {$comment->comment|escape:"html"}
    <div class="posted">{translate text='Posted by'} <strong>{$comment->fullname|escape:"html"}</strong>
    {translate text='posted_on'} {$comment->created|escape:"html"}</div>
    {if $comment->user_id == $user->id}
    <a href="{$url}/Record/{$id|escape:"url"}/UserComments?delete={$comment->id}" class="delete tool">{translate text='Delete'}</a>
    {/if}
  </li>
{foreachelse}
  <li>{translate text='Be the first to leave a comment'}!</li>
{/foreach}
