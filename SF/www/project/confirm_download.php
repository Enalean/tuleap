<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
require ('pre.php');


if (user_isloggedin()) {

  // Must have a group_id and file_id otherwise
  // we cannot do much
  if (!$file_id || !$group_id) {
    exit_missing_param();
  }
?>
<html>
<head>
<title>CodeX download agreement</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" href="/sourceforge.css" type="text/css">
</head>

<body>
<div align="center">
<h2>*** CodeX Download Agreement ***</h2></center>
</div>

<p>You are about to download a piece of Xerox software from the CodeX
Site. Software re-use and sharing <b>inside</b> Xerox is subject to the <a
href="/docman/display_doc.php?docid=16&group_id=1"
target="_blank"><b>Code eXchange Corporate Policy</b></a>.</p>

<p>By downloading this software you implicitely recognize that you have <a
href="/docman/display_doc.php?docid=16&group_id=1"
target="_blank"><b>read the CodeX Policy</b></a> and agree with the terms and
conditions.

<p>To proceed with the file download, click on '<b>I AGREE</b>'. I you do not
want to download the file click on '<b>I DECLINE</b>' and <a
href="mailto:<?php print $GLOBALS['sys_email_contact']; ?>"><b>contact
us</b></a> if you need clarification or want to explain why you declined
the agreement.<br>

<br><br>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr> 
    <td> 
      <div align="center"><a href="javascript:opener.download(<? echo $group_id ?>,<? echo $file_id ?>);"><b>I AGREE</b></a></div>
    </td>
    <td> 
      <div align="center"><a href="javascript:window.close();"><b>I DECLINE</b></a></div>
    </td>
  </tr>
</table>
</body>
</html>
<?

} else {
  /*
    Not logged in
  */
  exit_not_logged_in();
}


