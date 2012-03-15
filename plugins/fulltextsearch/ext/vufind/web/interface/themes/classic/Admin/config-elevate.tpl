<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">

      <div class="yui-gf resulthead">
        {include file="Admin/menu.tpl"}
        <div class="yui-u">
          <h1>Elevated Terms Configuration</h1>
          {include file="Admin/savestatus.tpl"}

          <p>
            Elevated terms are terms that have a more important meaning.
            These words will make matching records match higher in the results.
          </p>

          <form method="post">
            Term:<br><input type="text" name="term[]" value=""><br>
            Record IDs:<br><textarea name="idList[]" rows="5" cols="20">{$idList}</textarea><br>
            Term:<br><input type="text" name="term[]" value=""><br>
            Record IDs:<br><textarea name="idList[]" rows="5" cols="20">{$idList}</textarea><br>
            Term:<br><input type="text" name="term[]" value=""><br>
            Record IDs:<br><textarea name="idList[]" rows="5" cols="20">{$idList}</textarea><br>
            <input type="submit" name="submit" value="Save">
          </form>

        </div>
      </div>

    </div>
  </div>
</div>