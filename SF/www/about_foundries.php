<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    

$LANG->loadLanguageMsg('homepage/homepage');

$HTML->header(array(title=>$LANG->getText('about_foundries', 'title')));

// List of foudnries
$html = "";
$query = "SELECT group_name,unix_group_name ".
    "FROM groups WHERE status='A' AND is_public='1' ".
    " AND type='2' ORDER BY group_name ";
$result = db_query($query);
$rows = db_numrows($result);
if (!$result || $rows < 1) {
    $html .= "<H2>".$LANG->getText('about_foundries', 'no_foundries')."</H2><p>".db_error();
 } else {
    $html .=  "<UL>\n";
    for ($i=0; $i<$rows; $i++) {
	$html .=  "\n<li><A HREF=\"/foundry/".db_result($result, $i, 'unix_group_name')."/\">".
	    db_result($result, $i, 'group_name')."</A></li>";
    }
    $html .=  "\n</UL>\n";
 }
?>

<P>
<h2><?php echo $LANG->getText('about_foundries', 'title'); ?></h2>

<p><?php echo $LANG->getText('about_foundries', 'message', array($html,$GLOBALS['sys_email_contact'])); ?>

<?php
$HTML->footer(array());

?>
