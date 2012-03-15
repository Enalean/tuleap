  <form action="{$url}/Record/{$id|escape:"url"}/Hold" method="post" onSubmit="PlaceHold({$id|escape}, this); return false;">
    <b>{translate text='Username'}:</b><br>
    <input type="text" name="username" size="40"><br>
    <b>{translate text='Password'}:</b><br>
    <input type="password" name="password" size="40"><br>
    <input type="submit" name="submit" value="{translate text='Submit'}">
  </form>