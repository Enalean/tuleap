<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

//exit;

/*

	Nightly cron script to calculate the peer ratings

	The process starts with a seed group of users who are "trusted"
		to rate others

	After you are rated N times highly by other users, you can become trusted 
		and your ratings of others will begin to count

	Your rating is affected by how many times you are rated by others and 
		how highly they rate you and how highly rated they are

	How highly rated they are is affected by how many times they're rated 
		and how highly rated they are and so on up the chain

	For now, this process will run 8 times to get the calculations refined
		As more users are added, it may have to be run more

	Because of this circular dependency, the numbers are never "right", but 
		after a few runs, they should be refined "enough" to give us 
		what we want - a list of the top users on the site.

*/

require ('squal_pre.php');

$threshhold='1.6';

/*

if (!strstr($REMOTE_ADDR,'192.168.1.')) {
	exit_permission_denied();
}

*/

echo '<BR>Starting... ';

for ($i=1; $i<9; $i++) {
	echo '<BR>Starting round: '.$i;

	$j=($i-1);

	/*
		Set up an interim table to grab and average all trusted result
	*/
	$sql="DROP TABLE IF EXISTS user_metric_tmp1_$i;";
	$res=db_query($sql);
	echo db_error();
	
	$sql="CREATE TABLE user_metric_tmp1_$i (
		user_id int not null default 0, 
		times_ranked int not null default 0,
		avg_raters_importance float(8,8) not null default 0,
		avg_rating float(8,8) not null default 0,
		metric float(8,8) not null default 0);";
	$res=db_query($sql);
	echo db_error();

	/*
		Now grab/average trusted ratings into this table
	*/

flush();

	$sql="INSERT INTO user_metric_tmp1_$i
		SELECT user_ratings.user_id,count(*) AS count,
		avg(user_metric$j.importance_factor),
		avg(user_ratings.rating),0
		FROM user_ratings,user_metric$j
		WHERE user_ratings.rated_by=user_metric$j.user_id
		GROUP BY user_ratings.user_id";

	$res=db_query($sql);
	if (!$res || db_affected_rows($res) < 1) {
		echo "Error in round $i inserting average ratings: ".db_error();
		exit;
		
	}

	/*
		Now calculate the metric on the temp table

		This metric will be used in the next step to calculate ranking and importance
	*/

	$sql="UPDATE user_metric_tmp1_$i SET metric=(log(times_ranked)*avg_raters_importance*avg_rating);";
	$res=db_query($sql);
	if (!$res || db_affected_rows($res) < 1) {
		echo "Error in round $i calculating metric: ".db_error();
		exit;
		
	}

	$res=db_query("DELETE FROM user_metric_tmp1_$i WHERE metric < $threshhold");
	if (!$res || db_affected_rows($res) < 1) {
                echo "Error in round $i deleting < threshhold ids: ".db_error();
                exit;
                
        }

	$sql="SELECT DISTINCT user_id FROM user_metric_tmp1_$i";
	$res=db_query($sql);
	if (!$res || db_numrows($res) < 1) {
		echo "Error in round $i getting unique user_ids: ".db_error();
		exit;
		
	}

	//hack to get around lack of subselects in CheeSeQL (MySQL)
	$trusted_ids=implode(',',util_result_column_to_array($res));

	/*
		Now we need to carry forward trusted IDs from the last round into this 
		Round, as prior round people may not have been ranked enough times by 
		new people in this round to stay in
	*/

	$sql="INSERT INTO user_metric_tmp1_$i 
		SELECT user_id,times_ranked,avg_raters_importance,avg_rating,metric
		FROM user_metric$j 
		WHERE user_id NOT IN ($trusted_ids);";

	$res=db_query($sql);
	if (!$res || db_affected_rows($res) < 1) {
		echo "Error in round $i carrying forward IDs: ".db_error();
		exit;
		
	}

	/*
		Now calculate the metric for this round

		Create the final table, then insert the data
	*/

	echo '<BR>Starting Final Metric';

	$sql="DROP TABLE IF EXISTS user_metric$i;";
	$res=db_query($sql);
	echo db_error();

	$sql="CREATE TABLE user_metric$i (
		ranking int not null default 0 auto_increment primary key,
		user_id int not null default 0,
		times_ranked int not null default 0,
		avg_raters_importance float(8,8) not null default 0,
		avg_rating float(8,8) not null default 0,
		metric float(8,8) not null default 0,
		percentile float(8,8) not null default 0,
		importance_factor float(8,8) not null default 0);";

	$res=db_query($sql);
	echo db_error();

	/*
		Insert the data in ranked order
	*/

	$sql="INSERT INTO user_metric$i
		SELECT '',user_id,times_ranked,avg_raters_importance,avg_rating,metric,0,0
		FROM user_metric_tmp1_$i
		ORDER BY metric DESC;";
	$res=db_query($sql);
	if (!$res || db_affected_rows($res) < 1) {
		echo "Error in round $i inserting final data: ".db_error();
		exit;
	}

	/*
		Get the row count so we can calc the percentile below
	*/
	$res=db_query("SELECT COUNT(*) FROM user_metric$i");
	if (!$res || db_numrows($res) < 1) {
		echo "Error in round $i getting row count: ".db_error();
		exit;
	}

	echo '<BR>Issuing Final Update';

	/*
		Update with final percentile and importance
	*/
	$sql="UPDATE user_metric$i SET
		percentile=(100-(100*((ranking-1)/". db_result($res,0,0) ."))),
		importance_factor=(1+((percentile/100)*.5));";
	$res=db_query($sql);
	if (!$res || db_affected_rows($res) < 1) {
		echo "Error in round $i inserting final data: ".db_error();
		exit;
	}
}

echo '<BR>DONE';

?>
