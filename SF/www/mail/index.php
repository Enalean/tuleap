<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require('../mail/mail_utils.php');

if ($group_id) {

    if (session_issecure()) 
	$list_server = 'https://'.$GLOBALS['sys_lists_host'];
    else
	$list_server = 'http://'.$GLOBALS['sys_lists_host'];

    $params=array('title'=>'Mailing Lists for '.group_getname($group_id),
              'help'=>'CommunicationServices.html#MailingLists',
              'pv'   => $pv);
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
			<H1>No Lists found for '.group_getname($group_id).'</H1>';
		echo '
			<P>Project administrators use the admin link to request mailing lists.';
                mail_footer(array('pv'   => $pv)); 
		exit;
	}

	echo "<P>Mailing lists provided via a "
		. "<A href=\"http://www.list.org\">GNU Mailman</A>. "
		. "Thanks to the Mailman and <A href=\"http://www.python.org\">Python</A> "
		. "crews for excellent software.";

        if ($pv) {
            echo "<P>Choose a list to browse, search, and post messages.<P>\n";
        } else {
            echo "<TABLE width='100%'><TR><TD>";
            echo "<P>Choose a list to browse, search, and post messages.<P>\n";
            echo "</TD>";
            echo "<TD align='left'> ( <A HREF='".$PHP_SELF."?group_id=$group_id&pv=1'><img src='".util_get_image_theme("msg.png")."' border='0'>&nbsp;Printer version</A> ) </TD>";
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
                    echo ' <A HREF="'.$list_server.'/pipermail/'.$list_name.'">Archives</A>';
                } else {
                    echo ' Archives: <A HREF="http://'.$GLOBALS['sys_lists_host'].'/pipermail/'.$list_name.'">public</A>/<A HREF="http://'.$GLOBALS['sys_lists_host'].'/mailman/private/'.$list_name.'">private</A>';
                }
	  
		echo ' | <A HREF="'.$list_server.'/mailman/listinfo/'.$list_name.'">(Un)Subscribe/Preferences</A>)';
		echo ' | <A HREF="'.$list_server.'/mailman/admin/'.$list_name.'">ML Administration</A> ]';
		
		echo '<br>&nbsp;'.  db_result($result, $j, 'description') .'<P>';
	}
	echo '</TD></TR></TABLE>';

} else {
    $params=array('title'=>'Choose a Group First',
                  'help'=>'CommunicationServices.html#MailingLists',
                  'pv'   => $pv);
    mail_header($params);
    require('../mail/mail_nav.php');
    echo '
		<H1>Error - choose a group first</H1>';
}
mail_footer(array('pv'   => $pv)); 

?>
