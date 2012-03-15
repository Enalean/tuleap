<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">

      <div class="yui-gf resulthead">
        {include file="Admin/menu.tpl"}
        <div class="yui-u">
          <h1>{$pageTitle}</h1>
          
          {include file="Admin/savestatus.tpl"}
          
          <p>
            You are viewing the file at {$configPath}.
          </p>

          <form method="post">
            <textarea name="config_file" rows="20" cols="70" class="configEditor">{$configFile|escape}</textarea><br>
            <input type="submit" name="submit" value="Save">
          </form>
        </div>
      </div>

    </div>
  </div>
</div>