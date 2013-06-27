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


$HTML->header(array('title'=>$Language->getText('include_user_home','devel_profile')));
$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tiny_mce/tiny_mce.js');

if (!$user) {
	exit_error($Language->getText('include_user_home','no_such_user'),$Language->getText('include_user_home','no_such_user'));
}


echo '
<H3>'.$Language->getText('include_user_home','devel_profile').'</H3>
<P>
<TABLE width=100% cellpadding=2 cellspacing=2 border=0><TR valign=top>
<TD width=50%>';

$HTML->box1_top($Language->getText('include_user_home','perso_info'));
$hp = Codendi_HTMLPurifier::instance();
echo '
&nbsp;
<BR>
<TABLE width=100% cellpadding=0 cellspacing=0 border=0>
<TR valign=top>
	<TD>'.$Language->getText('include_user_home','user_id').': </TD>
	<TD><B>'.$user->getId().'</B></TD>
</TR>
<TR valign=top>
	<TD>'.$Language->getText('include_user_home','login_name').': </TD>
	<TD><B>'.$user->getUserName().'</B></TD>
</TR>
<TR valign=top>
	<TD>'.$Language->getText('include_user_home','real_name').': </TD>
	<TD><B>'. $hp->purify($user->getRealName(), CODENDI_PURIFIER_CONVERT_HTML) .'</B></TD>
</TR>
<TR valign=top>
	<TD>'.$Language->getText('include_user_home','email_addr').': </TD>
	<TD>
	<B>
	<A HREF="mailto:'.$user->getEmail().'">
	'.$user->getEmail().'	
	</A></B>
	</TD>
</TR>';

if(array_key_exists('sys_enable_user_skills', $GLOBALS) && $GLOBALS['sys_enable_user_skills']) {
    echo '
<TR valign=top>
	<TD>'.$Language->getText('include_user_home','user_prof').': </TD>
        <TD>
        <A HREF="/people/viewprofile.php?user_id='.$user->getId().'"><B>'.$Language->getText('include_user_home','see_skills').'</B></A></TD>
</TR>';
}

echo '
<TR>
	<TD>
	'.$Language->getText('include_user_home','member_since').': 
	</TD>
	<TD><B>'.date("M d, Y",$user->getAddDate()).'</B></TD>

<TR>
	<TD>
	'.$Language->getText('include_user_home','user_status').': 
	</TD>
	<TD><B>';
        switch($user->getStatus()) {
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
$eParams['user_id']     =  $user->getId();
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
$eParams['user_name'] =  $user->getUnixName();
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
	. "user_group.admin_flags, "
	. "user_group.bug_flags FROM "
	. "groups,user_group WHERE user_group.user_id='".$user->getId()."' AND "
	. "groups.group_id=user_group.group_id AND groups.is_public='1' AND groups.status='A' AND groups.type='1'");

// see if there were any groups
if (db_numrows($res_cat) < 1) {
	echo '
	<p>'.$Language->getText('include_user_home','not_member');
} else { // endif no groups
	print '<p>'.$Language->getText('include_user_home','is_member').":<BR>&nbsp;";
	while ($row_cat = db_fetch_array($res_cat)) {
        print ('<BR><A href="/projects/'.$row_cat['unix_group_name'].'/">'.$row_cat['group_name']."</A>\n");
    }
	print "</ul>";
} // end if groups

$HTML->box1_bottom(); ?>
</TD></TR>

<TR>

<TD>

<?php 

if (user_isloggedin()) {

    $HTML->box1_top($Language->getText('include_user_home','send_message_to').' '. $hp->purify($user->getRealName(), CODENDI_PURIFIER_CONVERT_HTML));

    echo '
	<FORM ACTION="/sendmessage.php" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="touser" VALUE="'.$user->getId().'">';

	$my_name=user_getrealname(user_getid());
    $cc = (isset($_REQUEST['cc'])?htmlspecialchars(trim($_REQUEST['cc'])):"");
	echo  '
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
            <P><B>'.$Language->getText('include_user_home','cc').':</B><BR/>
            <INPUT TYPE="TEXT" id="cc" NAME="cc" VALUE="'.$cc.'"STYLE="width: 99%;"><BR/>
            '.$Language->getText('include_user_home','fill_cc_list_msg').'</P>
        </div>
    </div>

	<P>
	<B>'.$Language->getText('include_user_home','subject').':</B><BR>
	<INPUT TYPE="TEXT" NAME="subject" VALUE="" STYLE="width: 99%;">
    </P>

    <P>
	<B>'.$Language->getText('include_user_home','message').':</B><BR>
	<div id="body_label"></div>
	<TEXTAREA ID="body" NAME="body" ROWS="15" WRAP="HARD" STYLE="width: 99%;"></TEXTAREA>
	</P>

	<CENTER>
	<INPUT TYPE="SUBMIT" NAME="send_mail" VALUE="'.$Language->getText('include_user_home','send_message').'">
	</CENTER>
	</FORM>';

    $HTML->box1_bottom();

} else {

	echo '<H3>'.$Language->getText('include_user_home','send_message_if_logged').'</H3>';

}

?>

</TD></TR>
</TABLE>

<?php
$js = "new UserAutoCompleter('cc','".util_get_dir_image_theme()."', true);";
$GLOBALS['Response']->includeFooterJavascriptSnippet($js);

$rte = "
var useLanguage = '". substr(UserManager::instance()->getCurrentUser()->getLocale(), 0, 2) ."';
document.observe('dom:loaded', function() {
            new Codendi_RTE_Send_HTML_MAIL('body');
        });";

$GLOBALS['HTML']->includeFooterJavascriptSnippet($rte);
$HTML->footer(array());

?>
