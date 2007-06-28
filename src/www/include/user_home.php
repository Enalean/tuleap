<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

/*
	Developer Info Page
	Written by dtype Oct 1999
*/


/*


	Assumes $res_user result handle is present


*/

$Language->loadLanguageMsg('include/include');

$HTML->header(array('title'=>$Language->getText('include_user_home','devel_profile')));

if (!$res_user || db_numrows($res_user) < 1) {
	exit_error($Language->getText('include_user_home','no_such_user'),$Language->getText('include_user_home','no_such_user'));
}


echo '
<H3>'.$Language->getText('include_user_home','devel_profile').'</H3>
<P>
<TABLE width=100% cellpadding=2 cellspacing=2 border=0><TR valign=top>
<TD width=50%>';

$HTML->box1_top($Language->getText('include_user_home','perso_info'));

echo '
&nbsp;
<BR>
<TABLE width=100% cellpadding=0 cellspacing=0 border=0>
<TR valign=top>
	<TD>'.$Language->getText('include_user_home','user_id').': </TD>
	<TD><B>'.db_result($res_user,0,'user_id').'</B></TD>
</TR>
<TR valign=top>
	<TD>'.$Language->getText('include_user_home','login_name').': </TD>
	<TD><B>'.db_result($res_user,0,'user_name').'</B></TD>
</TR>
<TR valign=top>
	<TD>'.$Language->getText('include_user_home','real_name').': </TD>
	<TD><B>'.db_result($res_user,0,'realname').'</B></TD>
</TR>
<TR valign=top>
	<TD>'.$Language->getText('include_user_home','email_addr').': </TD>
	<TD>
	<B>
	<A HREF="mailto:'.db_result($res_user,0,'email').'">
	'.db_result($res_user,0,'email').'	
	</A></B>
	</TD>
</TR>
<TR valign=top>
	<TD>'.$Language->getText('include_user_home','user_prof').': </TD>
        <TD>
        <A HREF="/people/viewprofile.php?user_id='.db_result($res_user,0,'user_id').'"><B>'.$Language->getText('include_user_home','see_skills').'</B></A></TD>
</TR>

<TR>
	<TD>
	'.$Language->getText('include_user_home','member_since').': 
	</TD>
	<TD><B>'.date("M d, Y",db_result($res_user,0,'add_date')).'</B></TD>

<TR>
	<TD>
	'.$Language->getText('include_user_home','user_status').': 
	</TD>
	<TD><B>';
        switch(db_result($res_user,0,'status')) {
        case 'A':
            echo $Language->getText('include_user_home','active');
            break;
        case 'R':
            echo $Language->getText('include_user_home','restricted');
            break;
        case 'P':
            echo $Language->getText('include_user_home','pending');
            break;
        case 'D':
            echo $Language->getText('include_user_home','deleted');
            break;
        case 'S':
            echo $Language->getText('include_user_home','suspended');
            break;
        default:
            echo $Language->getText('include_user_home','unkown');
        }


echo '</B></TD>

</TR>';

$entry_label = array();
$entry_value = array();

$em =& EventManager::instance();
$eParams = array();
$eParams['user_id']     =  db_result($res_user,0,'user_id');
$eParams['entry_label'] =& $entry_label;
$eParams['entry_value'] =& $entry_value;
$em->processEvent('user_home_pi_entry', $eParams);

foreach($entry_label as $key => $label) {
    $value = $entry_value[$key];
    print '
<TR valign=top>
	<TD>'.$label.'</TD>
	<TD><B>'.$value.'</B></TD>
</TR>
';
}

$hooks_output = "";

$em =& EventManager::instance();
$eParams = array();
$eParams['showdir']   =  isset($_REQUEST['showdir'])?$_REQUEST['showdir']:"";
$eParams['user_name'] =  db_result($res_user,0,'user_name');
$eParams['ouput']     =& $hooks_output;
$em->processEvent('user_home_pi_tail', $eParams);

echo $hooks_output;
?>

</TR>

</TABLE>
<?php $HTML->box1_bottom(); ?>

</TD>
<TD>&nbsp;</TD>
<TD width=50%>
<?php $HTML->box1_top($Language->getText('include_user_home','proj_info')); 
// now get listing of groups for that user
$res_cat = db_query("SELECT groups.group_name, "
	. "groups.unix_group_name, "
	. "groups.group_id, "
	. "groups.hide_members, "
	. "user_group.admin_flags, "
	. "user_group.bug_flags FROM "
	. "groups,user_group WHERE user_group.user_id='$user_id' AND "
	. "groups.group_id=user_group.group_id AND groups.is_public='1' AND groups.status='A' AND groups.type='1'");

// see if there were any groups
if (db_numrows($res_cat) < 1) {
	echo '
	<p>'.$Language->getText('include_user_home','not_member');
} else { // endif no groups
	print '<p>'.$Language->getText('include_user_home','is_member').":<BR>&nbsp;";
	while ($row_cat = db_fetch_array($res_cat)) {
            if (($row_cat['hide_members']==0)||(user_is_super_user())) {
		print ('<BR><A href="/projects/'.$row_cat['unix_group_name'].'/">'.$row_cat['group_name']."</A>\n");
            }
        }
	print "</ul>";
} // end if groups

$HTML->box1_bottom(); ?>
</TD></TR>

<TR><TD COLSPAN="3">

<?php 

if (user_isloggedin()) {

	echo '
	&nbsp;
	<P>
	<H3>'.$Language->getText('include_user_home','send_message_to').' '.db_result($res_user,0,'realname').'</H3>
	<P>
	<FORM ACTION="/sendmessage.php" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="touser" VALUE="'.$user_id.'">



	<B>'.$Language->getText('include_user_home','your_address').':</B><!-- LJ<BR> -->
	<B>';
	$my_email=user_getemail(user_getid());

	echo $my_email.'</B>

        </B>
	

        <INPUT TYPE="HIDDEN" NAME="email" VALUE="'.$my_email.'">	<P>
	<B>'.$Language->getText('include_user_home','your_name').':</B><!-- <BR> -->
	<B>';

	$my_name=user_getrealname(user_getid());
    $cc = (isset($_REQUEST['cc'])?htmlspecialchars(trim($_REQUEST['cc'])):"");
	echo $my_name.'</B>
	<INPUT TYPE="HIDDEN" NAME="name" VALUE="'.$my_name.'">
    <div>
        <script type="text/javascript" src="/scripts/blocks.js"></script>
        <script type="text/javascript">
        function addCCField() {
            hideBlock("cc_link");
            showBlock("cc_field");
        }
        </script>
        <div id="cc_link"  style="display:'.($cc !== ""?'none':'block').';"><a href="" onclick="addCCField(); return false;" title="'.$Language->getText('include_user_home','add_cc').'">'.$Language->getText('include_user_home','add_cc').'</a></div>
        <div id="cc_field" style="display:'.($cc === ""?'none':'block').';">
            <table cellspacing="0" cellpadding="0"><tr><td><B>'.$Language->getText('include_user_home','cc').':</B><BR/>
            <INPUT TYPE="TEXT" NAME="cc" SIZE="30" VALUE="'.$cc.'"></td><td style="padding-left:10px;">
            '.$Language->getText('include_user_home','fill_cc_list_msg').'</td></tr></table>
        </div>
    </div>
    
	<P>
	<B>'.$Language->getText('include_user_home','subject').':</B><BR>
	<INPUT TYPE="TEXT" NAME="subject" SIZE="30" MAXLENGTH="40" VALUE="">
	<P>
	<B>'.$Language->getText('include_user_home','message').':</B><BR>
	<TEXTAREA NAME="body" ROWS="15" COLS="60" WRAP="HARD"></TEXTAREA>
	<P>
	<CENTER>
	<INPUT TYPE="SUBMIT" NAME="send_mail" VALUE="'.$Language->getText('include_user_home','send_message').'">
	</CENTER>
	</FORM>';
	

} else {

	echo '<H3>'.$Language->getText('include_user_home','send_message_if_logged').'</H3>';

}

?>

</TD></TR>
</TABLE>

<?php
$HTML->footer(array());

?>
