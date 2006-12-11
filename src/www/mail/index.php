<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../mail/mail_utils.php');

$Language->loadLanguageMsg('mail/mail');

$pv=isset($pv)?$pv:false;

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

	    $list_name = db_result($result, $j, 'list_name');
	    $list_is_public = db_result($result, $j, 'is_public');

	    echo '<IMG SRC="'.util_get_image_theme("ic/cfolder15.png").'" HEIGHT="13" WIDTH="15" BORDER="0">&nbsp;<b>'.$list_name.'</b> [';
                if ($list_is_public) {
                    echo ' <A HREF="'.$list_server.'/pipermail/'.$list_name.'">'.$Language->getText('mail_index','archive').'</A>';
                } else {
                    echo ' '.$Language->getText('mail_index','archive').': <A HREF="'.$list_server.'/pipermail/'.$list_name.'/">'.$Language->getText('mail_index','public').'</A>/<A HREF="'.$list_server.'/mailman/private/'.$list_name.'/">'.$Language->getText('mail_index','private').'</A>';
                }
	  
		echo ' | <A HREF="'.$list_server.'/mailman/listinfo/'.$list_name.'">'.$Language->getText('mail_index','unsubscribe').'</A>)';
		echo ' | <A HREF="'.$list_server.'/mailman/admin/'.$list_name.'">'.$Language->getText('mail_index','ml_admin').'</A> ]';
		
		echo '<br>&nbsp;'.  db_result($result, $j, 'description') .'<P>';
	}
	echo '</TD></TR></TABLE>';

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
