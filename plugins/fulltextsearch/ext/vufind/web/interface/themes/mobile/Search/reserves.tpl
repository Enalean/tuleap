<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
    	<div class="record">

<form method="GET" action="{$url}/Search/Reserves" name="searchForm" class="search">
  <h2>{translate text='Search For'}</h2>
  <table class="citation">
    <tr>
      <th align="right">{translate text='Course'}: </th>
      <td>
        <select name="course">
          <option></option>
          {foreach from=$courseList item=courseName key=courseId}
            <option value="{$courseId|escape}">{$courseName|escape}</option>
          {/foreach}
        </select>
      </td>
    </tr>
    <tr>
      <th align="right">{translate text='Instructor'}: </th>
      <td>
        <select name="inst">
          <option></option>
          {foreach from=$instList item=instName key=instId}
            <option value="{$instId|escape}">{$instName|escape}</option>
          {/foreach}
        </select>
      </td>
    </tr>
    <tr>
      <th align="right">{translate text='Department'}: </th>
      <td>
        <select name="dept">
          <option></option>
          {foreach from=$deptList item=deptName key=deptId}
            <option value="{$deptId|escape}">{$deptName|escape}</option>
          {/foreach}
        </select>
      </td>
    </tr>
  </table>
  <input type="submit" name="submit" value="{translate text='Find'}"><br>
</form>

</div></div></div></div>