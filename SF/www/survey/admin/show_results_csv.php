<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$


require('pre.php');
require('HTML_Graphs.php');
require($DOCUMENT_ROOT.'/survey/survey_utils.php');

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
        echo "<H1>Permission Denied</H1>";
	exit;
}

function strip_commas($string) {
	return ereg_replace(",","",$string);
}

/*
	Select this survey from the database
*/

$sql="select * from surveys where survey_id='$survey_id'";

$result=db_query($sql);

/*
	Select the questions for this survey and show as top row
*/

$questions=db_result($result, 0, "survey_questions");
$quest_array=explode(',', $questions);
$count=count($quest_array);

echo "<HTML><PRE>";

#
#
#
#
#                  clean up later
#
#
#
#


echo "cust_id,first_name,field_1,email,field2,phone,field3,field4,field5,year,month,day,";

for ($i=0; $i<$count; $i++) {
	$result=db_query("select question from questions where question_id='$quest_array[$i]' AND question_type <> '4'");
	if ($result && db_numrows($result) > 0) {
		echo strip_commas(db_result($result, 0, 0)).",";
	}
}

echo "\n";

/*
	Now show the customer rows
*/

$sql="SELECT DISTINCT customer_id FROM responses WHERE survey_id='$survey_id'";

$result=db_query($sql);

$rows=db_numrows($result);

for ($i=0; $i<$rows; $i++) {

	/*
		Get this customer's info
	*/
	$sql="SELECT DISTINCT cust_id,first_name,people.last_name,people.email,people.email2,people.phone,".
		"people.beeper,people.cell,people.yes_interested,responses.response_year,".
		"responses.response_month,responses.response_day FROM people,responses ".
		"WHERE cust_id='".db_result($result, $i, "customer_id")."' AND cust_id=responses.customer_id";

	$result2=db_query($sql);

	if (db_numrows($result2) > 0) {

		$cols=db_numfields($result2);

		for ($i2=0; $i2<$cols; $i2++) {
			echo strip_commas(db_result($result2, 0, $i2)).",";
		}

		/*
			Get this customer's responses. may have to be ordered by original question order
		*/
		$sql="SELECT response FROM responses WHERE customer_id='".db_result($result, $i, "customer_id")."' AND survey_id='$survey_id'";

		$result3=db_query($sql);

		$rows3=db_numrows($result3);

		for ($i3=0; $i3<$rows3; $i3++) {
			echo strip_commas(db_result($result3, $i3, "response")).",";
		}

		/*
			End of this customer
		*/
		echo "\n";

	}

}

?>
