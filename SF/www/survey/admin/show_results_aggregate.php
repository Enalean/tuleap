<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require_once('HTML_Graphs.php');
require_once('www/survey/survey_utils.php');

$Language->loadLanguageMsg('survey/survey');

$is_admin_page='y';
survey_header(array('title'=>$Language->getText('survey_admin_show_r_aggregate','agg_res'),
		    'help'=>'AdministeringSurveys.html#ReviewingSurveyResults'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

//$result=db_query($sql);

/*
	Select this survey from the database
*/

$sql="SELECT * FROM surveys WHERE survey_id='$survey_id' AND group_id='$group_id'";
$result=db_query($sql);

echo "<H2>".db_result($result, 0, "survey_title")."</H2><P>";

/*
echo "<H3><A HREF=\"show_results_csv.php?survey_id=$survey_id&group_id=$group_id\">.CSV File</A></H3><P>";
*/

/*
	Select the questions for this survey
*/

$questions=db_result($result, 0, "survey_questions");
$quest_array=explode(',', $questions);
$quest_count=count($quest_array);

echo "\n\n<TABLE>";

$q_num=1;

for ($i=0; $i<$quest_count; $i++) {

	/*
		Build the questions on the HTML form
	*/

	$sql="SELECT question_type,question,question_id FROM survey_questions WHERE question_id=$quest_array[$i]";

	$result=db_query($sql);

	$question_type=db_result($result, 0, "question_type");

	if ($question_type == "4") {
		/*
			Don't show question number if it's just a comment
		*/

		echo "\n<TR><TD VALIGN=TOP>&nbsp;</TD>\n<TD>"; 

	} else {

		echo "\n<TR><TD VALIGN=TOP><B>";

		/*
			If it's a 1-5 question box and first in series, move Quest
			number down a bit
		*/

		if (($question_type != $last_question_type) && (($question_type == "1") || ($question_type == "3"))) {
			echo "&nbsp;<P>";
		}

		echo $q_num."&nbsp;&nbsp;&nbsp;&nbsp;<BR></TD>\n<TD>";
		$q_num++;

	}
	
	if ($question_type == "1") {

		/*
			This is a rædio-button question. Values 1-5.	
		*/


		/*
			Select the number of responses to this question
		*/
		$sql="SELECT count(*) AS count FROM survey_responses WHERE survey_id='$survey_id' ".
		    "AND question_id='$quest_array[$i]' AND response IN (1,2,3,4,5) AND group_id='$group_id'";

		$result2=db_query($sql);

		$sql="SELECT count(*) AS count FROM survey_responses WHERE survey_id='$survey_id' ".
		    "AND question_id='$quest_array[$i]' AND response='' AND group_id='$group_id'";
		$result3=db_query($sql);

		if (!$result2 || db_numrows($result2) < 1) {
		    echo $Language->getText('global','error');
		    echo db_error();
		} else {		    
		    $answers_cnt=db_result($result2, 0, 'count');
		    $blank_cnt=db_result($result3, 0, 'count');
		    echo "<B>$answers_cnt</B> ".$Language->getText('survey_admin_show_r_aggregate','resp');
		    if ($blank_cnt) { echo "  (+ $blank_cnt blank)"; }
		}

		/*
			compute average only if we have answers available
		*/

		if ($answers_cnt > 0) {
		    $sql='SELECT avg(response) AS avg FROM survey_responses '.
			"WHERE survey_id='$survey_id' AND question_id='$quest_array[$i]' ".
			"AND response IN (1,2,3,4,5)  AND group_id='$group_id'";

		    $result2=db_query($sql);
		    if (!$result2 || db_numrows($result2) < 1) {
			echo $Language->getText('global','error');
			echo db_error();
		    } else {
			$avg = db_result($result2, 0, 'avg');
			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			printf($Language->getText('survey_admin_show_r_aggregate','avg')." <B>%.2f</B>",$avg);
		    }
		}

		// Show the 1-5 markers only if this is the first in a series

		if ($question_type != $last_question_type) {
			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.
			    $Language->getText('survey_admin_show_r_aggregate','type')." <B>1 &lt;--- - - - ---&gt; 5</B>\n";
		}

		$sql="SELECT response,count(*) AS count FROM survey_responses WHERE survey_id='$survey_id' AND question_id='$quest_array[$i]' AND response IN (1,2,3,4,5) AND group_id='$group_id' GROUP BY response";

		$result2=db_query($sql);
		// Graph it even if there is zero row because GraphResult
		// is in charge of displaying the question itself
		if (!$result2) {
			echo $Language->getText('global','error');
			echo db_error();
		} else {
			GraphResult($result2,util_unconvert_htmlspecialchars(db_result($result, 0, "question")));
		}

	} else if ($question_type == "2") {
		/*
			This is a text-area question.
		*/
		$sql="SELECT count(*) AS count FROM survey_responses WHERE survey_id='$survey_id' ".
		    "AND question_id='$quest_array[$i]' AND group_id='$group_id' AND response<>'' ";

		$result2=db_query($sql);

		$sql="SELECT count(*) AS count FROM survey_responses WHERE survey_id='$survey_id' ".
		    "AND question_id='$quest_array[$i]' AND group_id='$group_id' AND response='' ";
		$result3=db_query($sql);

		$answers_cnt=db_result($result2, 0, 'count');
		$blank_cnt=db_result($result3, 0, 'count');

		echo util_unconvert_htmlspecialchars(db_result($result, 0, "question"))."<BR>\n";

		echo "<A HREF=\"show_results_comments.php?survey_id=$survey_id&question_id=$quest_array[$i]&question_num=$q_num&group_id=$group_id\">".$Language->getText('survey_admin_show_r_aggregate','view_cmmts')."</A>".
		    '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		echo ($answers_cnt ? "$answers_cnt ".$Language->getText('survey_admin_show_r_aggregate','cmmts') : $Language->getText('global','none'));
		echo ($blank_cnt ? " (+ $blank_cnt blank)" : '');
		echo '<br>';

	} else if ($question_type == "3") {
		/*
			This is a Yes/No question.
		*/


		/*
			Select the count and average of responses to this question
		*/
		$sql="SELECT count(*) AS count FROM survey_responses WHERE survey_id='$survey_id' ".
		    "AND question_id='$quest_array[$i]' AND group_id='$group_id' AND response IN (1,5)";

		$result2=db_query($sql);

		$sql="SELECT count(*) AS count FROM survey_responses WHERE survey_id='$survey_id' ".
		    "AND question_id='$quest_array[$i]' AND group_id='$group_id' AND response='' ";
		$result3=db_query($sql);

		if (!$result2 || db_numrows($result2) < 1) {
		    echo $Language->getText('global','error');
		    echo db_error();
		} else {
		    $answers_cnt=db_result($result2, 0, 'count');
		    $blank_cnt=db_result($result3, 0, 'count');
		    echo "<B>$answers_cnt</B> ".$Language->getText('survey_admin_show_r_aggregate','resp');
		    if ($blank_cnt) { echo "  (+ $blank_cnt blank)"; }
		}

		/*
			compute average only if we have answers available
		*/
		if ($answers_cnt > 0) {

		    $sql='SELECT avg(response) AS avg FROM survey_responses '.
			"WHERE survey_id='$survey_id' AND question_id='$quest_array[$i]' ".
			"AND response IN (1,5) AND group_id='$group_id'";

		    $result2=db_query($sql);
		    if (!$result2 || db_numrows($result2) < 1) {
			echo $Language->getText('global','error');
		    } else {
			$avg = db_result($result2, 0, 'avg');
			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			printf($Language->getText('survey_admin_show_r_aggregate','avg')." <B>%.2f</B>",$avg);
		    }
		}

		/*
			Show the Yes/No only if this is the first in a series
		*/

		if ($question_type != $last_question_type) {
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$Language->getText('survey_admin_show_r_aggregate','type')." <B>".$Language->getText('global','yes')." / ".$Language->getText('global','no')."</B><BR>\n";
		}

		/*
			Get the YES responses
		*/
		$sql="SELECT count(*) AS count FROM survey_responses WHERE survey_id='$survey_id' AND question_id='$quest_array[$i]' AND group_id='$group_id' AND response='1'";

		$result2=db_query($sql);

		$name_array[0]=$Language->getText('global','yes');

		if (!$result2 || db_numrows($result2) < 1) {
			$value_array[0]=0;
		} else {
			$value_array[0]=db_result($result2, 0, "count");
		}

		/*
			Get the NO responses
		*/
		$sql="SELECT count(*) AS count FROM survey_responses WHERE survey_id='$survey_id' AND question_id='$quest_array[$i]' AND group_id='$group_id' AND response='5'";

		$result2=db_query($sql);

		$name_array[1]=$Language->getText('global','no');

		if (!$result2 || db_numrows($result2) < 1) {
			$value_array[1]=0;
		} else {
			$value_array[1]=db_result($result2, 0, "count");
		}

		GraphIt($name_array,$value_array,util_unconvert_htmlspecialchars(db_result($result, 0, "question")));

	} else if ($question_type == "4") {

		/*
			This is a comment only.
		*/

		echo "&nbsp;<P><B>".util_unconvert_htmlspecialchars(db_result($result, 0, "question"))."</B>\n";
		echo "<INPUT TYPE=\"HIDDEN\" NAME=\"_".$quest_array[$i]."\" VALUE=\"-666\">";

	} else if ($question_type == "5") {

		/*
			This is a text-field question.
		*/
		$sql="SELECT count(*) AS count FROM survey_responses WHERE survey_id='$survey_id' ".
		    "AND question_id='$quest_array[$i]' AND group_id='$group_id' AND response<>'' ";

		$result2=db_query($sql);

		$sql="SELECT count(*) AS count FROM survey_responses WHERE survey_id='$survey_id' ".
		    "AND question_id='$quest_array[$i]' AND group_id='$group_id' AND response='' ";
		$result3=db_query($sql);

		$answers_cnt=db_result($result2, 0, 'count');
		$blank_cnt=db_result($result3, 0, 'count');

		echo util_unconvert_htmlspecialchars(db_result($result, 0, "question"))."<BR>\n";

		echo "<A HREF=\"show_results_comments.php?survey_id=$survey_id&question_id=$quest_array[$i]&question_num=$q_num&group_id=$group_id\">".$Language->getText('survey_admin_show_r_aggregate','view_cmmts')."</A>".
		    '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		echo ($answers_cnt ? "$answers_cnt ".$Language->getText('survey_admin_show_r_aggregate','cmmts') : $Language->getText('global','none'));
		echo ($blank_cnt ? " (+ $blank_cnt blank)" : '');
		echo '<br>';


	}

	echo "<br></TD></TR>";

	$last_question_type=$question_type;

}

echo "\n\n</TABLE>";

survey_footer(array());

?>
