<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
// Copyright (c) Enalean, 2015-2016. All rights reserved
// 

/*
	Developer Info Page
	Written by dtype Oct 1999
*/


/*


	Assumes $res_user result handle is present


*/


$GLOBALS['HTML']->header(array('title'=>$GLOBALS['Language']->getText('include_user_home','devel_profile')));

if (!$user) {
	exit_error($GLOBALS['Language']->getText('include_user_home','no_such_user'),$GLOBALS['Language']->getText('include_user_home','no_such_user'));
}
$purifier     = Codendi_HTMLPurifier::instance();
$current_user = UserManager::instance()->getCurrentUser();
if ($current_user->isLoggedIn()) {
    echo '
    <H3>'.$GLOBALS['Language']->getText('include_user_home','devel_profile').'</H3>
    <P>
    <TABLE width=100% cellpadding=2 cellspacing=2 border=0><TR valign=top>
    <TD width=50%>';

    $GLOBALS['HTML']->box1_top($GLOBALS['Language']->getText('include_user_home','perso_info'));
    echo '
    &nbsp;
    <BR>
    <TABLE width=100% cellpadding=0 cellspacing=0 border=0>
    <TR valign=top>
        <TD>'.$GLOBALS['Language']->getText('include_user_home','user_id').': </TD>
        <TD><B>'.$purifier->purify($user->getId()).'</B></TD>
    </TR>
    <TR valign=top>
        <TD>'.$GLOBALS['Language']->getText('include_user_home','login_name').': </TD>
        <TD><B>'.$purifier->purify($user->getUserName()).'</B></TD>
    </TR>
    <TR valign=top>
        <TD>'.$GLOBALS['Language']->getText('include_user_home','real_name').': </TD>
        <TD><B>'. $purifier->purify($user->getRealName(), CODENDI_PURIFIER_CONVERT_HTML) .'</B></TD>
    </TR>
    <TR valign=top>
        <TD>'.$GLOBALS['Language']->getText('include_user_home','email_addr').': </TD>
        <TD>
        <B>
        <A HREF="mailto:'.urlencode($user->getEmail()).'">
        '.$purifier->purify($user->getEmail()).'
        </A></B>
        </TD>
    </TR>';

    echo '
    <TR>
        <TD>
        '.$GLOBALS['Language']->getText('include_user_home','member_since').':
        </TD>
        <TD><B>'.date("M d, Y",$user->getAddDate()).'</B></TD>

    <TR>
        <TD>
        '.$GLOBALS['Language']->getText('include_user_home','user_status').':
        </TD>
        <TD><B>';
            switch($user->getStatus()) {
            case 'A':
                echo $GLOBALS['Language']->getText('include_user_home','active');
                break;
            case 'R':
                echo $GLOBALS['Language']->getText('include_user_home','restricted');
                break;
            case 'P':
                echo $GLOBALS['Language']->getText('include_user_home','pending');
                break;
            case 'D':
                echo $GLOBALS['Language']->getText('include_user_home','deleted');
                break;
            case 'S':
                echo $GLOBALS['Language']->getText('include_user_home','suspended');
                break;
            default:
                echo $GLOBALS['Language']->getText('include_user_home','unkown');
            }


    echo '</B></TD>
    </TR>';

    $entry_label = array();
    $entry_value = array();

    $em = EventManager::instance();
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

    $eParams = array();
    $eParams['showdir']   =  isset($_REQUEST['showdir'])?$_REQUEST['showdir']:"";
    $eParams['user_name'] =  $user->getUnixName();
    $eParams['ouput']     =& $hooks_output;
    $em->processEvent('user_home_pi_tail', $eParams);

    echo $hooks_output;
    ?>

    </TR>

    </TABLE>
    <?php $GLOBALS['HTML']->box1_bottom(); ?>

    </TD>
    <TD>&nbsp;</TD>
    <TD width=50%>
    <?php $GLOBALS['HTML']->box1_top($GLOBALS['Language']->getText('include_user_home','proj_info'));
    // now get listing of groups for that user
    $res_cat = db_query("SELECT groups.group_name, "
        . "groups.unix_group_name, "
        . "groups.group_id, "
        . "user_group.admin_flags, "
        . "user_group.bug_flags FROM "
        . "groups,user_group WHERE user_group.user_id='".$user->getId()."' AND "
        . "groups.group_id=user_group.group_id AND groups.access != '".db_es(Project::ACCESS_PRIVATE)."' AND groups.status='A' AND groups.type='1'");

    // see if there were any groups
    if (db_numrows($res_cat) < 1) {
        echo '
        <p>'.$GLOBALS['Language']->getText('include_user_home','not_member');
    } else { // endif no groups
        print '<p>'.$GLOBALS['Language']->getText('include_user_home','is_member').":<BR>&nbsp;";
        while ($row_cat = db_fetch_array($res_cat)) {
            print ('<BR><A href="/projects/'.urlencode($row_cat['unix_group_name']).'/">'.$purifier->purify($row_cat['group_name'])."</A>\n");
        }
        print "</ul>";
    } // end if groups

    $GLOBALS['HTML']->box1_bottom(); ?>
    </TD></TR>

    <TR>

    <TD>

    <?php

    $csrf_token = new CSRFSynchronizerToken('sendmessage.php');

    $GLOBALS['HTML']->box1_top($GLOBALS['Language']->getText('include_user_home','send_message_to').' '. $purifier->purify
                        ($user->getRealName()
                    , CODENDI_PURIFIER_CONVERT_HTML));

    echo '
	<FORM ACTION="/sendmessage.php" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="touser" VALUE="'.$user->getId().'">';
    echo $csrf_token->fetchHTMLInput();

	$my_name = $purifier->purify(user_getrealname(user_getid()));
    $cc      = (isset($_REQUEST['cc'])?$purifier->purify(trim($_REQUEST['cc'])):"");
	echo  '
    <div>
        <script type="text/javascript" src="/scripts/blocks.js"></script>
        <script type="text/javascript">
        function addCCField() {
            hideBlock("cc_link");
            showBlock("cc_field");
        }
        </script>
        <div id="cc_link"  style="display:'.($cc !== ""?'none':'block').';"><a href="" onclick="addCCField(); return false;" title="'.$GLOBALS['Language']->getText('include_user_home','add_cc').'">'.$GLOBALS['Language']->getText('include_user_home','add_cc').'</a></div>
        <div id="cc_field" style="display:'.($cc === ""?'none':'block').';">
            <P><B>'.$GLOBALS['Language']->getText('include_user_home','cc').':</B><BR/>
            <INPUT TYPE="TEXT" id="cc" NAME="cc" VALUE="'.$cc.'"STYLE="width: 99%;"><BR/>
            '.$GLOBALS['Language']->getText('include_user_home','fill_cc_list_msg').'</P>
        </div>
    </div>

    <P>
	<B>'.$GLOBALS['Language']->getText('include_user_home','subject').':</B><BR>
	<INPUT TYPE="TEXT" NAME="subject" VALUE="" STYLE="width: 99%;">
    </P>

    <P>
	<B>'.$GLOBALS['Language']->getText('include_user_home','message').':</B><BR>
	<div id="body_label"></div>
        <div id="user-home-message">
            <TEXTAREA ID="body" NAME="body" ROWS="15" WRAP="HARD"></TEXTAREA>
        </div>
    </P>

	<CENTER>
	<INPUT TYPE="SUBMIT" NAME="send_mail" VALUE="'.$GLOBALS['Language']->getText('include_user_home','send_message').'">
	</CENTER>
	</FORM>';

    $GLOBALS['HTML']->box1_bottom();

} else {

	echo '<H3>'.$GLOBALS['Language']->getText('include_user_home','send_message_if_logged').'</H3>';

}

?>

</TD></TR>
</TABLE>

<?php
$js = "new UserAutoCompleter('cc','".util_get_dir_image_theme()."', true);";
$GLOBALS['Response']->includeFooterJavascriptSnippet($js);

$rte = "
var useLanguage = '". $purifier->purify(substr($current_user->getLocale(), 0, 2), CODENDI_PURIFIER_JS_QUOTE) ."';
document.observe('dom:loaded', function() {
    var body_container = $$('#body')[0];
    var options = {
        toggle: true,
        default_in_html: false,
        htmlFormat : false,
        id: ''
    };

    new tuleap.textarea.RTE(body_container, options);

});";

$GLOBALS['HTML']->includeFooterJavascriptSnippet($rte);
$GLOBALS['HTML']->footer(array());