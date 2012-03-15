<form onSubmit='SaveTag(&quot;{$id|escape}&quot;, this,
    {literal}{{/literal}success: &quot;{translate text='add_tag_success'}&quot;, load_error: &quot;{translate text='load_tag_error'}&quot;, save_error: &quot;{translate text='add_tag_error'}&quot;{literal}}{/literal}
    ); return false;' method="POST">
<input type="hidden" name="submit" value="1" />
<table>
  <tr><td>{translate text="Tags"}: </td><td><input type="text" name="tag" value="" size="50"></td></tr>
  <tr><td colspan="2">{translate text="add_tag_note"}</td></tr>
  <tr><td></td><td><input type="submit" value="{translate text='Save'}"></td></tr>
</table>
</form>