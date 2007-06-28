<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require('../mail/mail_utils.php');
require_once('common/include/HTTPRequest.class.php');

$Language->loadLanguageMsg('mail/mail');

$pv=isset($pv)?$pv:false;

function display_ml_details($group_id, $list_server, $result, $i) {
    $list_name = db_result($result, $i, 'list_name');
    $list_is_public = db_result($result, $i, 'is_public');
    
    echo '<IMG SRC="'.util_get_image_theme("ic/cfolder15.png").'" HEIGHT="13" WIDTH="15" BORDER="0">&nbsp;<b>'.$list_name.'</b> [';
            if ($list_is_public) {
                echo ' <A HREF="?group_id='. $group_id .'&amp;action=pipermail&amp;id='. db_result($result, $i, 'group_list_id') .'">'.$GLOBALS['Language']->getText('mail_index','archive').'</A>';
            } else {
                echo ' '.$GLOBALS['Language']->getText('mail_index','archive').': <A HREF="?group_id='. $group_id .'&amp;action=pipermail&amp;id='. db_result($result, $i, 'group_list_id') .'">'.$GLOBALS['Language']->getText('mail_index','public').'</A>/<A HREF="?group_id='. $group_id .'&amp;action=private&amp;id='. db_result($result, $i, 'group_list_id') .'">'.$GLOBALS['Language']->getText('mail_index','private').'</A>';
            }
  
    echo ' | <A HREF="?group_id='. $group_id .'&amp;action=listinfo&amp;id='. db_result($result, $i, 'group_list_id') .'">'.$GLOBALS['Language']->getText('mail_index','unsubscribe').'</A>)';
    echo ' | <A HREF="?group_id='. $group_id .'&amp;action=admin&amp;id='. db_result($result, $i, 'group_list_id') .'">'.$GLOBALS['Language']->getText('mail_index','ml_admin').'</A> ]';
    
    echo '<br>&nbsp;'.  db_result($result, $i, 'description') .'<p>';
}

if ($group_id) {

    $list_server = get_list_server_url();

    $params=array('title'=>$Language->getText('mail_index','mail_list_for').group_getname($group_id),
              'help'=>'CommunicationServices.html#MailingLists',
                  'pv'   => isset($pv)?$pv:false);
    mail_header($params);
	
	if (user_isloggedin() && user_ismember($group_id)) {
		$public_flag='0,1';
	} else {
		$public_flag='1';
	}
    $request =& HTTPRequest::instance();
    if ($request->exist('action')) {
        if ($request->exist('id')) {
            $sql="SELECT * FROM mail_group_list WHERE group_id='$group_id' AND is_public IN ($public_flag) AND group_list_id = ". (int)$request->get('id');
            $result = db_query($sql);
            if (db_numrows($result)) {
                display_ml_details($group_id, $list_server, $result, 0);
                echo '<a href="?group_id='. $group_id .'">Go back to mailing lists</a>';
                switch($request->get('action')) {
                    case 'admin':
                    case 'listinfo':
                    case 'private':
                        $iframe_url = $list_server .'/mailman/'. $request->get('action') .'/'. db_result($result, 0, 'list_name');
                        break;
                    case 'pipermail':
                        $iframe_url = $list_server .'/pipermail/'. db_result($result, 0, 'list_name');
                        break;
                    default:
                        break;
                }
                if ($iframe_url) {
                    $GLOBALS['HTML']->iframe($iframe_url, array('class' => 'iframe_service'));
                }
            }
        }
    } else {
        $sql="SELECT * FROM mail_group_list WHERE group_id='$group_id' AND is_public IN ($public_flag)";
    
        $result = db_query ($sql);
    
        $rows = db_numrows($result); 
    
    
        if (!$result || $rows < 1) {
            echo '
                <H1>'.$Language->getText('mail_index','no_list_found_for').group_getname($group_id).'</H1>';
            echo '
                <P>'.$Language->getText('mail_index','proj_admin_use_admin_link');
                    mail_footer(array('pv'   => isset($pv)?$pv:false)); 
            exit;
        }
    
        echo '<P>'.$Language->getText('mail_index','mail_list_via_gnu');
    
            if ($pv) {
                echo "<P>".$Language->getText('mail_index','choose_and_browse')."<P>\n";
            } else {
                echo "<TABLE width='100%'><TR><TD>";
                echo "<P>".$Language->getText('mail_index','choose_and_browse')."<P>\n";
                echo "</TD>";
                echo "<TD align='left'> ( <A HREF='".$PHP_SELF."?group_id=$group_id&pv=1'><img src='".util_get_image_theme("msg.png")."' border='0'>&nbsp;".$Language->getText('global','printer_version')."</A> ) </TD>";
                echo "</TR></TABLE>";
            }
    
        /*
            Put the result set (list of mailing lists for this group) into a column with folders
        */
    
        echo "<table WIDTH=\"100%\" border=0>\n".
            "<TR><TD VALIGN=\"TOP\">\n"; 
    
        for ($j = 0; $j < $rows; $j++) {
            display_ml_details($group_id, $list_server, $result, $j);
        }
        echo '</TD></TR></TABLE>';
    }

} else {
    $params=array('title'=>$Language->getText('mail_index','choose_group_first'),
                  'help'=>'CommunicationServices.html#MailingLists',
                  'pv'   => $pv);
    mail_header($params);
    require('../mail/mail_nav.php');
    echo '
		<H1>'.$Language->getText('mail_index','group_err').'</H1>';
}
mail_footer(array('pv'   => $pv)); 

?>
