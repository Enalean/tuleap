<?php

require_once('www/news/news_utils.php');
require_once('features_boxes.php');

//we already know $foundry is set up from the master page

$HTML->header(array('title'=>$foundry->getUnixName().' - Foundry','group'=>$group_id));

echo'	<TABLE cellspacing="0" cellpadding="10" border="0" width="100%">
	      <TR>
		<TD align="left" valign="top" colspan="2">
';

$sql="SELECT db_images.width,db_images.height,db_images.id ".
	"FROM db_images,foundry_data ".
	"WHERE db_images.id=foundry_data.logo_image_id ".
	"AND foundry_data.foundry_id='$group_id'";
$result=db_query($sql);
$rows=db_numrows($result);

if (!$result || $rows < 1) {
//	echo 'No Projects';
	echo db_error();
} else {
	echo '<IMG SRC="/dbimage.php?id='.db_result($result,$i,'id').'" HEIGHT="'.db_result($result,$i,'height').'" WIDTH="'.db_result($result,$i,'width').'">';
}


echo '
		</td>
	      </tr>
	<TR>
	    <TD valign="top" align="left">
';

echo $foundry->getFreeformHTML1();

echo '
	&nbsp;<BR>
';

/*

	News that was selected for display by the portal
	News items are chosen froma list of news in subprojects

*/

$HTML->box1_top('Foundry News', '#f4f5f7');
echo news_foundry_latest($group_id);
$HTML->box1_bottom();

/*

	Message Forums

*/

echo '<P>
';

$HTML->box1_top('Discussion Forums');

$sql="SELECT * FROM forum_group_list WHERE group_id='$group_id' AND is_public='1';";

$result = db_query ($sql);

$rows = db_numrows($result);

if (!$result || $rows < 1) {

	echo '<H1>No forums found for '. $foundry->getUnixName() .'</H1>';

} else {

	/*
		Put the result set (list of forums for this group) into a column with folders
	*/

	for ($j = 0; $j < $rows; $j++) {
		echo '
			<A HREF="/forum/forum.php?forum_id='. db_result($result, $j, 'group_forum_id') .'">'.
			'<IMG SRC="'.util_get_image_theme("'.util_get_image_theme("ic/cfolder15.png").'" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp;'.
			db_result($result, $j, 'forum_name').'</A> ';
		//message count
		echo '('.db_result(db_query("SELECT count(*) FROM forum WHERE group_forum_id='".db_result($result, $j, 'group_forum_id')."'"),0,0).' msgs)';
		echo "<BR>\n";
		echo db_result($result,$j,'description').'<P>';
	}

}

$HTML->box1_bottom();

echo $foundry->getFreeformHTML2();

echo '</TD><TD VALIGN="TOP" WIDTH="30%">';

echo $foundry->getSponsorHTML1();

echo foundry_features_boxes($group_id);

echo '</TD></TR></TABLE>';

$HTML->footer(array());

?>
