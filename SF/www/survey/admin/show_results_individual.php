<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require($DOCUMENT_ROOT.'/survey/survey_utils.php');
$is_admin_page='y';
survey_header(array('title'=>'Results'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
        echo "<H1>Permission Denied</H1>";
        survey_footer(array());
	exit;
}

?>

<FORM ACTION="NONE">
<?php

/*
	Select this survey from the database
*/

$sql="SELECT * FROM surveys WHERE survey_id='$survey_id' AND group_id='$group_id'";
$result=db_query($sql);

echo "\n<H2>".db_result($result, 0, "survey_title")."</H2><P>";

/*
	Select the questions for this survey
*/

$questions=db_result($result, 0, "survey_questions");

$quest_array=explode(',', $questions);

$count=count($quest_array);

/*
	Display info for this customer
*/

/*
$sql="select * from people where cust_id='$customer_id'";

$result=db_query($sql);

echo "\n<B>Name: </B>".db_result($result, 0, "first_name")." ".db_result($result, 0, "last_name")."<BR>";
echo "\n<B>Email: </B>".db_result($result, 0, "email")." / ".db_result($result, 0, "email2")."<BR>";
echo "\n<B>Phone: </B>".db_result($result, 0, "phone")."<BR>";
echo "\n<B>Beeper: </B>".db_result($result, 0, "beeper")."<BR>";
echo "\n<B>Cell: </B>".db_result($result, 0, "cell")."<P>";
*/

echo "\n\n<TABLE>";

$q_num=1;

for ($i=0; $i<$count; $i++) {

	/*
		Build the questions on the HTML form
	*/

	$sql="select questions.question_type,questions.question,questions.question_id,responses.response ".
		"from questions,responses where questions.question_id='".$quest_array[$i]."' and ".
		"questions.question_id=responses.question_id and responses.customer_id=$customer_id AND responses.survey_id=$survey_id";

	$result=db_query($sql);

/*
	See if there was a result. If not a result, join might have failed because of "open ended question".
	In that case, requery, and test again. If still no response, then this is a "comment only" question
*/
	if (!$result || db_numrows($result) < 1) {

		#$result=db_query("select * from responses where question_id='".$quest_array[$i]."' and survey_id=$survey_id AND customer_id=$customer_id");

		#echo "\n\n<!-- falling back 1 -->";
	
		#if (!$result || db_numrows($result) < 1) {
		#	echo "\n\n<!-- falling back 2 -->";
			$result=db_query("select * from survey_questions where question_id='".$quest_array[$i]."'");
			$not_found=1;
		#} else {
                #	$not_found=0;
		#}

	} else {
		$not_found=0;
	}

		#echo "\n\nnotfound: '$not_found'";

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


		# Show the 1-5 markers only if this is the first in a series

		if ($question_type != $last_question_type) {
			echo "\n<B>1 &nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; 5</B>\n";
                        echo "\n<BR>";

		}

		for ($j=1; $j<=5; $j++) {
			echo "\n<INPUT TYPE=\"RADIO\" NAME=\"_".$quest_array[$i]."\" VALUE=\"$j\"";
			/*
				add the checked statement if this was the response
			*/
			if (($not_found==0) && db_result($result, 0, "response")=="$j") { echo " checked"; }
			echo ">\n";
		}

		echo "&nbsp; ".db_result($result, 0, "question")."\n";

	} else if ($question_type == "2") {

		/*
			This is a text-area question.
		*/

		echo db_result($result, 0, "question")."<BR>\n";
		echo "\n<textarea name=\"_".$quest_array[$i]."\" rows=5 cols=60 wrap=\"soft\">";

		/*
			Show the person's response if there was one
		*/

		if ($not_found==0) {
			echo db_result($result, 0, "response");
		}
		echo "</textarea>\n";	

	} else if ($question_type == "3") {

                /*
                        This is a Yes/No question.
                */

		/*
			Show the Yes/No only if this is the first in a series
		*/

		if ($question_type != $last_question_type) {
	                echo "<B>Yes / No</B><BR>\n";
		}

		echo "\n<INPUT TYPE=\"RADIO\" NAME=\"_".$quest_array[$i]."\" VALUE=\"1\"";

                /*
                	add the checked statement if this was the response
                */

		if (($not_found==0) && db_result($result, 0, "response")=="1") { echo " checked"; }
		echo ">";
                echo "\n<INPUT TYPE=\"RADIO\" NAME=\"_".$quest_array[$i]."\" VALUE=\"5\"";

                /*
                        add the checked statement if this was the response
                */
                if (($not_found==0) && db_result($result, 0, "response")=="5") { echo " checked"; }

                echo ">";
 
		echo "&nbsp; ".db_result($result, 0, "question")."\n";

        } else if ($question_type == "4") {

		/*
			This is a comment only.
		*/

		echo "\n&nbsp;<P><B>".db_result($result, 0, "question")."</B>\n";
		echo "\n<INPUT TYPE=\"HIDDEN\" NAME=\"_".$quest_array[$i]."\" VALUE=\"-666\">";

        } else if ($question_type == "5") {

                /*
                        This is a text-field question.
                */

		echo db_result($result, 0, "question")."<BR>\n";
                echo "\n<INPUT TYPE=\"TEXT\" name=\"_".$quest_array[$i]."\" SIZE=20 MAXLENGTH=70i VALUE=\"";

		/*
			Show the person's response if there was one
		*/
		if ($not_found==0) {
		 	echo db_result($result, 0, "response");
		}
		echo "\">";

        }

	echo "</TD></TR>";

	$last_question_type=$question_type;

}

echo "\n\n</TABLE>";

?>
</FORM>

<?php

survey_footer(array());

?>
