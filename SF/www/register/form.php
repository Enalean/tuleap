<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    // Initial db and session library, opens session

$Language->loadLanguageMsg('register/register');

$HTML->header(array(title=>$Language->getText('register_form','new_project_registration')));

echo '
<p>
<h1>'.$Language->getText('register_form','register_form').'</h1>
</p>



<table border=0 cellspacing=0><tr>
                        <td width=15></td>
                        <td>
                        <hr size=2><p>
                                '.$Language->getText('register_form','fill_form',$GLOBALS['sys_name']).'
                                <p>
                        <form method="POST" action="">
                        <table cellpadding=2 cellspacing=0 border=0>
                        <tr>
                                <td align="right">* '.$Language->getText('register_form','first_name').'</td>
                                <td><input size=30 type="text" name="firstname"></td>
                        </tr>
                        <tr>
                                <td align="right">* '.$Language->getText('register_form','last_name').'</td>
                                <td><input size=30 type="text" name="lastname"></td>

                        <tr>
                                <td align="right">* '.$Language->getText('register_form','email').'</td>
                                <td><input size=35 type="text" name="email"></td>
                        </tr>
                        <tr>
                                <td align="right">* '.$Language->getText('register_form','project_name').'</td>
                                <td><input size=35 type="text" name="projectname"></td>
                        </tr>
                        <tr>
                                <td align="right">* '.$Language->getText('register_form','proj_description').'</td>
                                <td><textarea wrap="physical" name="project_description" cols=40 rows=4></textarea></td>
                        </tr>
                        <tr>
                                <td align="right">* '.$Language->getText('register_form','desired_subdomain').' <i><span class="subdomain">myproject</span>.'.get_server_url().')</td>
                                <td><input size=35 type="text" name="subdomain"></td>
                        </tr>
                        <tr>
                                <td align="right">* '.$Language->getText('register_form','passwd').'</td>
                                <td><input size=12 type="text" name="requested_password">&nbsp;&nbsp;<input size=12 type="text" name="requested_password_confirm"></td>
                                
                        <tr>
                                <td align="right">'.$Language->getText('register_form','comments').'</td>
                                <td><textarea wrap="physical" name="comments" cols=35 rows=5></textarea></td>
                        </tr>
                        <tr>
                        <td></td>
                        <td>
                        <input type="submit" value="'.$Language->getText('global','btn_submit').'">
                        <input type="reset" value="'.$Language->getText('register_form','clear_form').'">
                                </td>   </tr> </form>
                        </table>                                      </table>
                        <input type="hidden" name="required" value="firstname,lastname,email,projectname,project_description,subdomain,requested_password">';


$HTML->footer(array());

?>

