<span class="graytitle">User Account</span>
{if $message}<ul class="pageitem"><li>{$message|translate}</li></ul>{/if}

<form method="post" action="{$url}/MyResearch/Account" name="loginForm">
<ul class="pageitem">
  <li class="form"><input type="text" name="firstname" value="{$formVars.firstname|escape}" placeholder="{translate text='First Name'}"></li>
  <li class="form"><input type="text" name="lastname" value="{$formVars.lastname|escape}" placeholder="{translate text='Last Name'}"></li>
  <li class="form"><input type="text" name="email" value="{$formVars.email|escape}" placeholder="{translate text='Email Address'}"></li>
  <li class="form"><input type="text" name="username" value="{$formVars.username|escape}" placeholder="{translate text='Desired Username'}"></li>
  <li class="form"><input type="password" name="password" placeholder="{translate text='Password'}"></li>
  <li class="form"><input type="password" name="password2" placeholder="{translate text='Password Again'}"></li>
  <li class="form"><input type="submit" name="submit" value="{translate text='Submit'}"></li>
</ul>
</form>
