<form method="post" action="{$url}{$formTargetPath|escape}" name="popupForm"
      onSubmit='SendSMS(&quot;{$id|escape}&quot;, this.elements[&quot;to&quot;].value, 
                this.elements[&quot;provider&quot;][this.elements[&quot;provider&quot;].selectedIndex].value,
                {* Pass translated strings to Javascript -- ugly but necessary: *}
                {literal}{{/literal}sending: &quot;{translate text='sms_sending'}&quot;, 
                 success: &quot;{translate text='sms_success'}&quot;,
                 failure: &quot;{translate text='sms_failure'}&quot;{literal}}{/literal}
                ); return false;'>
  <table>
  <tr>
    <td>{translate text="Number"}: </td>
    <td>
      <input type="text" name="to" value="{translate text="sms_phone_number"}" 
        onfocus="if (this.value=='{translate text="sms_phone_number"}') this.value=''" 
        onblur="if (this.value=='') this.value='{translate text="sms_phone_number"}'">
    </td>
  </tr>
  <tr>
    <td>{translate text="Provider"}: </td>
    <td>
      <select name="provider">
        <option selected=true value="">{translate text="Select your carrier"}</option>
        {foreach from=$carriers key=val item=details}
        <option value="{$val}">{$details.name|escape}</option>
        {/foreach}
      </select>
    </td>
  </tr>
  <tr>
    <td></td>
    <td><input type="submit" name="submit" value="{translate text="Send"}"></td>
  </tr>
  </table>
</form>