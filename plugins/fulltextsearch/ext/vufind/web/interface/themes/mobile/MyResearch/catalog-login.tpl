<span class="greytitle">{translate text='Library Catalog Profile'}</span>
{if $loginError}
<ul class="pageitem"><li>{translate text=$loginError}</li></ul>
{/if}

<form method="post">
<ul class="pageitem">
  <li class="textbox">{translate text='cat_establish_account'}</li>
  <li class="form"><input type="text" name="cat_username" placeholder="{translate text='cat_username_abbrev'}"></li>
  <li class="form"><input type="text" name="cat_password" placeholder="{translate text='cat_password_abbrev'}"></li>
  <li class="form"><input type="submit" name="submit" value="{translate text='Save'}"></li>
</ul>
</form>
