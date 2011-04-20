<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//

require_once('pre.php');
$title = $Language->getText('include_layout','Help');
site_header(array('title' => $title));

include($Language->getContent('help/site'));
if( isset($_POST['submit']) ){
	
	echo "request sent";
}
?>



<form  name="request" action="index.php" method="post" enctype="multipart/form-data">
    
     <fieldset ><legend>Submit Help Request:</legend>
     Type: 
     <select name="type"> 
	     <option value"support">Support request</option>
	     <option value"enhancement">Enhancement request</option>
     </select><br />
     Severity: <select name="severity"> 
     	<option value"minor">Minor</option>
     	<option value"serious">Serious</option>
     	<option value"critical">Critical</option>
     	</select><br />
     Summary: <input type="text" name="request_summary" /><br />
     Description:  <textarea name="request_description" cols="50" rows="7"> </textarea> <br />
     
 	 <input enabled = false name="submit" type="submit" value="Submit" />
	 
	 </fieldset>
</form>
<?php 
site_footer(array());

?>