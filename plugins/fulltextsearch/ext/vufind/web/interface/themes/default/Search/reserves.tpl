<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
    	<b class="btop"><b></b></b>


  <div class="resulthead"><h3>{translate text='Search For Items on Reserve'}</h3></div>
  <div class="page">
  <table class="citation">
    <tr>
      <th align="right"><form method="GET" action="{$url}/Search/Reserves" name="searchForm" class="search">{translate text='By Course'}: </th>
      <td>
        <select name="course">
          <option></option>
          {foreach from=$courseList item=courseName key=courseId}
            <option value="{$courseId|escape}">{$courseName|escape}</option>
          {/foreach}
        </select>
         &nbsp;&nbsp;<input type="submit" name="submit" value="{translate text='Find'}"></form>
      </td>
    </tr>
    <tr>
      <th align="right"><form method="GET" action="{$url}/Search/Reserves" name="searchForm" class="search">{translate text='By Instructor'}: </th>
      <td>
        <select name="inst">
          <option></option>
          {foreach from=$instList item=instName key=instId}
            <option value="{$instId|escape}">{$instName|escape}</option>
          {/foreach}
        </select>
          &nbsp;&nbsp;<input type="submit" name="submit" value="{translate text='Find}"></form>
      </td>
    </tr>
    <tr>
      <th align="right"><form method="GET" action="{$url}/Search/Reserves" name="searchForm" class="search">{translate text='By Department'}: </th>
      <td>
        <select name="dept">
          <option></option>
          {foreach from=$deptList item=deptName key=deptId}
            <option value="{$deptId|escape}">{$deptName|escape}</option>
          {/foreach}
        </select>
          &nbsp;&nbsp;<input type="submit" name="submit" value="{translate text='Find'}"></form>
      </td>
    </tr>
  </table>

</div><b class="bbot"><b></b></b></div></div></div>