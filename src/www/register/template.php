<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: basicinfo.php 1491 2005-05-09 07:18:09Z ljulliar $

require_once('pre.php');    // Initial db and session library, opens session
require_once('common/include/TemplateSingleton.class');

session_require(array('isloggedin'=>'1'));
$Language->loadLanguageMsg('register/register');
$Language->loadLanguageMsg('new/new');


$HTML->header(array('title'=>$Language->getText('register_template','choose')));

$template =& TemplateSingleton::instance();
$db_templates = $template->getTemplates();

include($Language->getContent('register/template'));
?>


<FONT size=-1>
<FORM action="basicinfo.php" method="post">
<?php

$rows=db_numrows($db_templates);
if ($rows > 0) {

  $HTML->box1_top($Language->getText('register_template','choose'));
  print '
  <TABLE width="100%">';

  for ($i=0; $i<$rows; $i++) {
    
        print '
      <TR>';
    
    $group_id = db_result($db_templates,$i,'group_id');
    $check = "";
    $title = '<B>'.db_result($db_templates,$i,'group_name').
	'</B> (' . date($GLOBALS['sys_datefmt_short'],db_result($db_templates,$i,'register_time')) . ')';
    if ($group_id == '100') {
      $check = "checked";
    } else {
      $title = '<A href="/projects/'.db_result($db_templates,$i,'unix_group_name').'" > '.$title.' </A>';
    }

    print '
        <TD><input type="radio" name="built_from_template" value="'.$group_id.'" '.$check.'></TD>
        <TD>'.$title.'
        <TD rowspan="2" align="left" valign="top"><I>'.db_result($db_templates,$i,'short_description').'</I></TD>
      </TR>
';

    // Get Project admin as contacts
    if ($group_id == '100') {
      $res_admin = db_query("SELECT user_name AS user_name "
			  . "FROM user "
			  . "WHERE user_id='101'");
    } else {
    $res_admin = db_query("SELECT user.user_name AS user_name "
			  . "FROM user,user_group "
			  . "WHERE user_group.user_id=user.user_id AND user_group.group_id=$group_id AND "
			  . "user_group.admin_flags = 'A'");
    }
    $admins = array();
    while ($row_admin = db_fetch_array($res_admin)) {
      $admins[] = '<A href="/users/'.$row_admin['user_name'].'/">'.$row_admin['user_name'].'</A>';
    }
    print '
      <TR>
         <TD> &nbsp</TD>
         <TD><I>'.$Language->getText('new_index','contact').': '.join(',',$admins).'</I></TD>
      </TR>
      <TR><TD colspan="3"><HR></TD></TR>';
  }

  print '
  </TABLE>';
  $HTML->box1_bottom();

 }
?>

<p>
<INPUT type=submit name="Submit" value="<?php print $Language->getText('register_template','next_step');?>">
</FORM>
</FONT>

<?php

$HTML->footer(array());

?>