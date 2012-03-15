<span class="graytitle">{translate text='Login'}</span>
{if $message}
<ul class="pageitem">
  <li>{$message|translate}</li>
</ul>
{/if}

{if $authMethod != 'Shibboleth'}
  <form method="post" action="{$url}/MyResearch/Home" name="loginForm">
  <ul class="pageitem">
    <li class="form"><input type="text" name="username" value="{$username}" placeholder="{translate text="Username"}"></li>
    <li class="form"><input type="password" name="password" placeholder="{translate text="Password"}"></li>
    <li class="form"><input type="submit" name="submit" value="{translate text='Login'}"></li>
  </ul>
  {if $followup}
    <input type="hidden" name="followup" value="{$followup}"/>
    {if $followupModule}<input type="hidden" name="followupModule" value="{$followupModule}"/>{/if}
    {if $followupAction}<input type="hidden" name="followupAction" value="{$followupAction}"/>{/if}
    {if $recordId}<input type="hidden" name="recordId" value="{$recordId|escape:"html"}"/>{/if}
  {/if}
  {if $comment}
    <input type="hidden" name="comment" name="comment" value="{$comment|escape:"html"}"/>
  {/if}
  </form>
  
  {if $authMethod == 'DB'}
  <ul class="pageitem">
    <li class="menu"><a href="{$url}/MyResearch/Account"><span class="name">{translate text="Create New Account"}</span></a></li>
  </ul>
  {/if}
{/if}