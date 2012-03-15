<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">

      <div class="yui-gf resulthead">
        {include file="Admin/menu.tpl"}
        <div class="yui-u">
          <h1>Stop Words Configuration</h1>
          {include file="Admin/savestatus.tpl"}
          
          <p>
            The Stop Words are a list of words that VuFind will ignore when a user searches for the term.
            Each word should be on a new line.
          </p>

          <form method="post">
            <textarea name="stopwords" rows="20" cols="20">{$stopwords|escape}</textarea><br>
            <input type="submit" name="submit" value="Save">
          </form>
        </div>
      </div>

    </div>
  </div>
</div>