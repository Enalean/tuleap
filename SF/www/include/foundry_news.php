<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

//we know $foundry is already set up from the root /foundry/ page


if (user_ismember($group_id,'A')) {
	/*
		This is a simple page that foundry admins
			can access. It shows all news for all projects in this foundry

		The admin can then check a box and add the news item to their foundry.

		The admin cannot edit the news item unfortunately - only the project
			admin can edit their news
	*/

	//comma separated list of project_id's in this foundry
	$list=$foundry->getProjectsCommaSep();
	
	if ($post_changes) {

		//echo $post_changes.'-'.$status.'-'.$news_id;

		if ($approve) {
			db_query("DELETE FROM foundry_news WHERE foundry_id='$group_id' AND news_id='$news_id'");
			/*
				Update the db so the item shows on the home page
			*/
			if ($status) {
				$sql="INSERT INTO foundry_news (foundry_id,news_id,is_approved,approve_date) ".
					"VALUES ('$group_id','$news_id','$status','". time() ."')";
				$result=db_query($sql);
				if (!$result || db_affected_rows($result) < 1) {
					echo db_error();
					$feedback .= ' ERROR doing update ';
				} else {
					$feedback .= ' NewsByte Updated. ';
				}
			}

			/*
				Show the list_queue
			*/
			$approve='';
			$list_queue='y';
		}
	}

	project_admin_header (array('title'=>'NewsBytes','group'=>$group_id));

	if ($approve) {
		/*
			Show the submit form
		*/

		$sql="SELECT * FROM news_bytes WHERE id='$id'";
		$result=db_query($sql);
		if (db_numrows($result) < 1) {
			exit_error('Error','Error - not found');
		}

		echo '
		<H3>Approve a NewsByte</H3>
		<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="news_id" VALUE="'.db_result($result,0,'id').'">
		<B>Submitted for Project:</B> '.group_getname(db_result($result,0,'group_id')).'<BR>
		<B>Submitted by:</B> '.user_getname(db_result($result,0,'submitted_by')).'<BR>
		<INPUT TYPE="HIDDEN" NAME="approve" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<INPUT TYPE="RADIO" NAME="status" VALUE="1"> Approve For Foundry Page<BR>
		<INPUT TYPE="RADIO" NAME="status" VALUE="0"> Unapprove<BR>
		<INPUT TYPE="RADIO" NAME="status" VALUE="2" CHECKED> Delete<BR>
		<B>Subject:</B><BR>
		'.db_result($result,0,'summary').'<BR>
		<B>Details:</B><BR>
		'. nl2br( db_result($result,0,'details') ) .'
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>';

	} else {
		/*

			Show list of waiting news items

			SURE WISH I HAD SUBSELECTS........... tim ;-)

		*/

		$old_date=(time()-(86400*7));

		//get a list of IDs that have been approved
		//hack to get around lack of subselects....
		$sql="SELECT news_id ".
			"FROM foundry_news ".
			"WHERE is_approved=1 ".
			"AND foundry_id='$group_id' ".
			"AND approve_date > '$old_date'";
		$result=db_query($sql);
		//echo 'APPROVED IDS:'.db_error();
		$approved_ids=implode(',',util_result_column_to_array($result));
		//echo '|'.$approved_ids;

		//get a list of IDs that have been deleted
		//hack to get around lack of subselects....
		$sql="SELECT news_id ".
			"FROM foundry_news ".
			"WHERE is_approved=2 ".
			"AND foundry_id='$group_id' ".
			"AND approve_date > '$old_date'";
		$result=db_query($sql);
		//echo 'DELETED IDS:'.db_error();
		$deleted_ids=implode(',',util_result_column_to_array($result));
		//echo '|'.$deleted_ids;

		//get all news for these projects for this week 
		//if they haven't already been deleted or approved
		//hack to get around lack of subselects

		if ($approved_ids && $deleted_ids) {
			$query="AND id NOT IN ($deleted_ids,$approved_ids)";
		} else if ($approved_ids) {
			$query="AND id NOT IN ($approved_ids)";
		} else if ($deleted_ids) {
			$query="AND id NOT IN ($deleted_ids)";
		} else {
			$query='';
		}
		$sql="SELECT * FROM news_bytes ".
			"WHERE group_id IN ($list) ".
			"AND date > '$old_date' ".
			"$query";

		//echo $sql;
		$result=db_query($sql);
		$rows=db_numrows($result);
		if ($rows < 1) {
			echo db_error();
			echo '
			<H4>No Queued Items Found</H1>';
		} else {
			echo '
			<H4>These items need to be approved</H4>
			<P>';
			for ($i=0; $i<$rows; $i++) {
				echo '
				<A HREF="'.$PHP_SELF.'?approve=1&id='.db_result($result,$i,'id').'">'.db_result($result,$i,'summary').'</A><BR>';
			}
		}


		/*
			Show list of deleted news items for this week
		*/
		if ($deleted_ids) {
			$sql="SELECT * FROM news_bytes WHERE id IN ($deleted_ids)";
			$result=db_query($sql);
			$rows=db_numrows($result);
			if ($rows < 1) {
				echo db_error();
				echo '
				<H4>No deleted items found for this week</H4>';
			} else {
				echo '
				<H4>These items were deleted this past week</H4>
				<P>';
				for ($i=0; $i<$rows; $i++) {
					echo '
					<A HREF="'.$PHP_SELF.'?approve=1&id='.db_result($result,$i,'id').'">'.db_result($result,$i,'summary').'</A><BR>';
				}
			}
		} else {
			echo '
			<H4>No deleted items found for this week</H4>';
		}


		/*
			Show list of approved news items for this week
		*/
		if ($approved_ids) {
			$sql="SELECT * FROM news_bytes WHERE id IN ($approved_ids)";
			$result=db_query($sql);
			$rows=db_numrows($result);
			if ($rows < 1) {
				echo db_error();
				echo '
				<H4>No approved items found for this week</H4>';
			} else {
				echo '
				<H4>These items were approved this past week</H4>
				<P>';
				for ($i=0; $i<$rows; $i++) {
					echo '
					<A HREF="'.$PHP_SELF.'?approve=1&id='.db_result($result,$i,'id').'">'.db_result($result,$i,'summary').'</A><BR>';
				}
			}
		} else {
			echo '
			<H4>No approved items found for this week</H4>';
		}

	}
	project_admin_footer(array());

} else {

	exit_error('Permission Denied.','Permission Denied. You have to be an admin on the project you are editing or a member of the SourceForge News team.');

}

?>
