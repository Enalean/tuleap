<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    // Initial db and session library, opens session
$HTML->header(array(title=>"New Project Registration"));
?>

<p>
<h1>New Project Registration Form</h1>
</p>



<table border=0 cellspacing=0><tr>
                        <td width=15></td>
                        <td>
                        <hr size=2><p>
                        <font face="helvetica, arial">
                                Simply fill out this form to request an account on <?php print $GLOBALS['sys_name']; ?>. Fields with an asterisk (*) are required.
                                <p>
                        <form method="POST" action="">
                        <table cellpadding=2 cellspacing=0 border=0>
                        <tr>
                                <td align="right">* First Name:</td>
                                <td><input size=30 type="text" name="firstname"></td>
                        </tr>
                        <tr>
                                <td align="right">* Last Name:</td>
                                <td><input size=30 type="text" name="lastname"></td>

                        <tr>
                                <td align="right">* Email:</td>
                                <td><input size=35 type="text" name="email"></td>
                        </tr>
                        <tr>
                                <td align="right">* Project Name:</td>
                                <td><input size=35 type="text" name="projectname"></td>
                        </tr>
                        <tr>
                                <td align="right">* Brief Description<br>of Project:</td>
                                <td><textarea wrap="physical" name="project_description" cols=40 rows=4></textarea></td>
                        </tr>
                        <tr>
                                <td align="right">* Desired Subdomain:<br>(e.g. <i>http://<font color="green">myproject</font>.<?php echo $GLOBALS['sys_default_domain']; ?> )</td>
                                <td><input size=35 type="text" name="subdomain"></td>
                        </tr>
                        <tr>
                                <td align="right">* Desired Password:<br>(enter twice)</td>
                                <td><input size=12 type="text" name="requested_password">&nbsp;&nbsp;<input size=12 type="text" name="requested_password_confirm"></td>
                                
                        <tr>
                                <td align="right">Comments/Questions:</td>
                                <td><textarea wrap="physical" name="comments" cols=35 rows=5></textarea></td>
                        </tr>
                        <tr>
                        <td></td>
                        <td>
                        <input type="submit" value="Submit">
                        <input type="reset" value="Clear Form">
                                </td>   </tr> </form>
                        </table>                                      </table>
                        <input type="hidden" name="required" value="firstname,lastname,email,projectname,project_description,subdomain,requested_password">

  


<?php
$HTML->footer(array());

?>

