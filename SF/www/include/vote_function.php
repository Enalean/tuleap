<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

function vote_number_to_stars($raw) {
	$raw=intval($raw*2);
	//	echo "\n\n<!-- $raw -->\n\n";
	if ($raw % 2 == 0) {
		$show_half=0;
	} else {
		$show_half=1;
	}
	$count=intval($raw/2);
	for ($i=0; $i<$count; $i++) {
		$return .= '
				<IMG SRC="/images/ic/check.png" HEIGHT=15 WIDTH=16>';
	}
	if ($show_half==1) {
		$return .= '
				<IMG SRC="/images/ic/halfcheck.png" HEIGHT=15 WIDTH=16>';
	}
	return $return;
}

function vote_show_thumbs ($id,$flag) {
	/*
		$flag
		project - 1
		release - 2
		forum_message - 3
		user - 4
	*/
	$rating=vote_get_rating ($id,$flag);
	if ($rating==0) {
		return "<B>(unrated)</B>";
	} else {
		return vote_number_to_stars($rating).'('.$rating.')';
	}
}

function vote_get_rating ($id,$flag) {
	$sql="SELECT response FROM survey_rating_aggregate WHERE type='$flag' AND id='$id'";
	$result=db_query($sql);
	if (!$result || (db_numrows($result) < 1) || (db_result($result,0,0)==0)) {
		return '0';
	} else {
		return db_result($result,0,0);
	}
}

function vote_show_release_radios ($vote_on_id,$flag) {
	/*
		$flag
		project - 1
		release - 2
		forum_message - 3
		user - 4
	*/

//html_blankimage($height,$width)
	$rating=vote_get_rating ($vote_on_id,$flag);
	if ($rating==0) {
		$rating='2.5';
	}
	$rating=((16*vote_get_rating ($vote_on_id,$flag))-15);
	
	global $REQUEST_URI;
	?>
	<FONT SIZE="-2">
	<FORM ACTION="/survey/rating_resp.php" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="vote_on_id" VALUE="<?php echo $vote_on_id; ?>">
	<INPUT TYPE="HIDDEN" NAME="redirect_to" VALUE="<?php echo urlencode($REQUEST_URI); ?>">
	<INPUT TYPE="HIDDEN" NAME="flag" VALUE="<?php echo $flag; ?>">
	<CENTER>
	<IMG SRC="/images/rateit.png" HEIGHT=9 WIDTH=100>
	<BR>
	<?php html_blankimage(1,$rating); ?>
	<IMG SRC="/images/ic/caret.png" HEIGHT=6 WIDTH=9>
	<BR>
	<INPUT TYPE="RADIO" NAME="response" VALUE=1>
	<INPUT TYPE="RADIO" NAME="response" VALUE=2>
	<INPUT TYPE="RADIO" NAME="response" VALUE=3>
	<INPUT TYPE="RADIO" NAME="response" VALUE=4>
	<INPUT TYPE="RADIO" NAME="response" VALUE=5>
	<BR>
	<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Rate">
	</CENTER>
	</FORM>
	</FONT>
	<?php

}

/*

	Select and show a specific survey from the database

*/

function show_survey ($group_id,$survey_id) {

?>
<FORM ACTION="/survey/survey_resp.php" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="survey_id" VALUE="<?php echo $survey_id; ?>">
<?php

/*
	Select this survey from the database
*/

$sql="SELECT * FROM surveys WHERE survey_id='$survey_id'";

$result=db_query($sql);

if (db_numrows($result) > 0) {
	echo '
		<H3>'.db_result($result, 0, 'survey_title').'</H3>';
	/*
		Select the questions for this survey
	*/

	$questions=db_result($result, 0, 'survey_questions');
	$quest_array=explode(',', $questions);
	$count=count($quest_array);
	echo '
		<TABLE BORDER=0>';
	$q_num=1;

	for ($i=0; $i<$count; $i++) {
		/*
			Build the questions on the HTML form
		*/

		$sql="SELECT * FROM survey_questions WHERE question_id='".$quest_array[$i]."'";
		$result=db_query($sql);
		$question_type=db_result($result, 0, 'question_type');

		if ($question_type == '4') {
			/*
				Don't show question number if it's just a comment
			*/

			echo '
				<TR><TD VALIGN=TOP>&nbsp;</TD><TD>';

		} else {
			echo '
				<TR><TD VALIGN=TOP><B>';
			/*
				If it's a 1-5 question box and first in series, move Quest
				number down a bit
			*/
			if (($question_type != $last_question_type) && (($question_type == '1') || ($question_type == '3'))) {
				echo '&nbsp;<BR>';
			}

			echo $q_num.'&nbsp;&nbsp;&nbsp;&nbsp;<BR></TD><TD>';
			$q_num++;
		}

		if ($question_type == "1") {
			/*
				This is a rædio-button question. Values 1-5.
			*/
			// Show the 1-5 markers only if this is the first in a series

			if ($question_type != $last_question_type) {
				echo '
					<B>1 &nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; 5</B>';
				echo '<BR>';

			}

			for ($j=1; $j<=5; $j++) {
				echo '
					<INPUT TYPE="RADIO" NAME="_'.$quest_array[$i].'" VALUE="'.$j.'">';
			}

			echo '&nbsp; '.stripslashes(db_result($result, 0, 'question'));

		} else if ($question_type == '2') {
			/*
				This is a text-area question.
			*/

			echo stripslashes(db_result($result, 0, 'question')).'<BR>';
			echo '
				<textarea name="_'.$quest_array[$i].'" rows=5 cols=60 wrap="soft"></textarea>';

		} else if ($question_type == '3') {
			/*
				This is a Yes/No question.
			*/

			//Show the Yes/No only if this is the first in a series

			if ($question_type != $last_question_type) {
				echo '<B>Yes / No</B><BR>';
			}

			echo '
				<INPUT TYPE="RADIO" NAME="_'.$quest_array[$i].'" VALUE="1">';
			echo '
				<INPUT TYPE="RADIO" NAME="_'.$quest_array[$i].'" VALUE="5">';

			echo '&nbsp; '.stripslashes(db_result($result, 0, 'question'));

		} else if ($question_type == '4') {
			/*
				This is a comment only.
			*/

			echo '&nbsp;<BR><B>'.stripslashes(db_result($result, 0, 'question')).'</B>';
			echo '
				<INPUT TYPE="HIDDEN" NAME="_'.$quest_array[$i].'" VALUE="-666">';

		} else if ($question_type == '5') {
			/*
				This is a text-field question.
			*/

			echo stripslashes(db_result($result, 0, 'question')).'<BR>';
			echo '
				<INPUT TYPE="TEXT" name="_'.$quest_array[$i].'" SIZE=20 MAXLENGTH=70>';

		}
		echo '</TD></TR>';

		$last_question_type=$question_type;
	}

	?>
	<TR><TD ALIGN="MIDDLE" COLSPAN="2">

	<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
	<BR>
	<A HREF="/survey/privacy.php">Survey Privacy</A>
	</TD></TR>
	</FORM>
	</TABLE>
	<?php

} else {
	echo "<H3>Survey Not Found</H3>";
}

}

function vote_show_a_question ($question,$element_name) {
	/*
		Show a single question for the new user rating
		system
	*/

	echo '
	<TR><TD><B>-3</B></TD><TD ALIGN="RIGHT"><B>+3</B></TD></TR>

	<TR><TD COLSPAN="2">
	<INPUT TYPE="RADIO" NAME="Q_'. $element_name .'" VALUE="-3">
	<INPUT TYPE="RADIO" NAME="Q_'. $element_name .'" VALUE="-2">
	<INPUT TYPE="RADIO" NAME="Q_'. $element_name .'" VALUE="-1">
	<INPUT TYPE="RADIO" NAME="Q_'. $element_name .'" VALUE="0.1">
	<INPUT TYPE="RADIO" NAME="Q_'. $element_name .'" VALUE="1">
	<INPUT TYPE="RADIO" NAME="Q_'. $element_name .'" VALUE="2">
	<INPUT TYPE="RADIO" NAME="Q_'. $element_name .'" VALUE="3">
	</TD></TR>

	<TR><TD>'.$question.'</TD></TR>';

}

/*

	The ratings system is actually flexible enough
	to let you do N number of questions, but we are just going with 5
	that apply to everyone

*/

$USER_RATING_QUESTIONS=array();
//sorry - array starts at 1 so we can test for the questions on the receiving page
$USER_RATING_QUESTIONS[1]='Planning/Managing/Organizing';
$USER_RATING_QUESTIONS[2]='Coding';
$USER_RATING_QUESTIONS[3]='Teamwork';
$USER_RATING_QUESTIONS[4]='Breadth of Skills';
$USER_RATING_QUESTIONS[5]='Efficiency';

function vote_show_user_rate_box ($user_id) {
	global $USER_RATING_QUESTIONS;
	echo '

	<!-- User ratings form  -->

	<TABLE BORDER=0>
		<FORM ACTION="/developer/rate.php" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="rated_user" VALUE="'.$user_id.'">';

	for ($i=1; $i<=count($USER_RATING_QUESTIONS); $i++) {
		echo vote_show_a_question ($USER_RATING_QUESTIONS[$i],$i);
	}

	echo '
		<TR><TD COLSPAN="2"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Rate This User"></TD></TR>
		</TABLE>
	</FORM>';
}

?>
