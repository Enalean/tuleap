<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
    <b class="btop"><b></b></b>
      <h2>{translate text="User Account"}</h2><br>

      {if $message}<div class="error">{$message|translate}</div>{/if}
      <div class="result">
      <table class="citation">
        <form method="post" action="{$url}/MyResearch/Account" name="loginForm">
        <tr>
          <td>{translate text="First Name"}: </td>
          <td><input id="mainFocus" type="text" name="firstname" value="{$formVars.firstname|escape}" size="30"></td>
        </tr>
        <tr>
          <td>{translate text="Last Name"}: </td>
          <td><input type="text" name="lastname" value="{$formVars.lastname|escape}" size="30"></td>
        </tr>
        <tr>
          <td>{translate text="Email Address"}: </td>
          <td><input type="text" name="email" value="{$formVars.email|escape}" size="30"></td>
        </tr>

        <tr>
          <td>{translate text="Desired Username"}: </td>
          <td><input type="text" name="username" value="{$formVars.username|escape}" size="30"></td>
        </tr>
        <tr>
          <td>{translate text="Password"}: </td>
          <td><input type="password" name="password" size="15"></td>
        </tr>
        <tr>
          <td>{translate text="Password Again"}: </td>
          <td><input type="password" name="password2" size="15"></td>
        </tr>

        <tr>
          <td></td>
          <td><input type="submit" name="submit" value="{translate text="Submit"}"></td>
        </tr>
        </form>
      </table>
      <script type="text/javascript">var o = document.getElementById('mainFocus'); if (o) o.focus();</script>
      </div>
    <b class="bbot"><b></b></b>
    </div>
  </div>
</div>