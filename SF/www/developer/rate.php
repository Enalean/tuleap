<?php

require('pre.php');
require('vote_function.php');

if (user_isloggedin()) {

	$user=user_getid();
	if ($rated_user != $user) {
		//how many questions can they be rated on?
		$count=count($USER_RATING_QUESTIONS);

		//now iterate and insert each response
		for ($i=1; $i<=$count; $i++) {
			$resp="Q_$i";
			$rating=$$resp;

			//ratings can only be between +3 and -3
			if ($rating > 3 || $rating < '-3') {
				$feedback .= ' ERROR - invalid rating value ';
			} else {
				if ($rating) {
					//user did answer this question, so insert into db
					$res=db_query("SELECT * FROM user_ratings ".
						"WHERE rated_by='$user' AND user_id='$rated_user' rate_field='$i'");
					if ($res && db_numrows($res) > 0) {
						$res=db_query("DELETE FROM user_ratings ".
							"WHERE rated_by='$user' AND user_id='$rated_user' rate_field='$i'");
					}
					$res=db_query("INSERT INTO user_ratings (rated_by,user_id,rate_field,rating) ".
						"VALUES ('$user','$rated_user','$i','$rating')");
					echo db_error();
				}
			}
		}
	} else {
		exit_error('ERROR','You can\'t rate yourself');
	}

	echo $HTML->header(array('title'=>'User Ratings Page'));

	echo '
	<H3>Ratings Recorded</H3>
	<P>
	You can re-rate this person by simply returning to their ratings page and re-submitting the info.
	<P>';

	echo $HTML->footer(array());

} else {
	exit_not_logged_in();
}

?>
