<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');

//common forum tools which are used during the creation/editing of news items
require($DOCUMENT_ROOT.'/forum/forum_utils.php');

if ($group_id && $group_id != 714 && user_ismember($group_id,'A')) {
	/*

		Per-project admin pages.

		Shows their own news items so they can edit/update.

		If their news is on the homepage, and they edit, it is removed from 
			sf.net homepage.

	*/
	if ($post_changes) {
		if ($approve) {
			/*
				Update the db so the item shows on the home page
			*/
			if ($status != 0 && $status != 4) {
				//may have tampered with HTML to get their item on the home page
				$status=0;
			}

			//foundry stuff - remove this news from the foundry so it has to be re-approved by the admin
			db_query("DELETE FROM foundry_news WHERE news_id='$id'");

			$sql="UPDATE news_bytes SET is_approved='$status', summary='".htmlspecialchars($summary)."', ".
				"details='".htmlspecialchars($details)."' WHERE id='$id' AND group_id='$group_id'";
			$result=db_query($sql);

			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' ERROR doing group update ';
			} else {
				$feedback .= ' Project NewsByte Updated. ';
			}
			/*
				Show the list_queue
			*/
			$approve='';
			$list_queue='y';
		}
	}

	news_header(array('title'=>'NewsBytes'));

	if ($approve) {
		/*
			Show the submit form
		*/

		$sql="SELECT * FROM news_bytes WHERE id='$id' AND group_id='$group_id'";
		$result=db_query($sql);
		if (db_numrows($result) < 1) {
			exit_error('Error','Error - none found');
		}

		echo '
		<H3>Approve a NewsByte For Project: '.group_getname($group_id).'</H3>
		<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.db_result($result,0,'group_id').'">
		<INPUT TYPE="HIDDEN" NAME="id" VALUE="'.db_result($result,0,'id').'">

		<B>Submitted by:</B> '.user_getname(db_result($result,0,'submitted_by')).'<BR>
		<INPUT TYPE="HIDDEN" NAME="approve" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">

 		<B>Status:</B><BR>
                <INPUT TYPE="RADIO" NAME="status" VALUE="0" CHECKED> Displayed<BR>
                <INPUT TYPE="RADIO" NAME="status" VALUE="4"> Delete<BR>
 
		<B>Subject:</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" VALUE="'.db_result($result,0,'summary').'" SIZE="30" MAXLENGTH="60"><BR>
		<B>Details:</B><BR>
		<TEXTAREA NAME="details" ROWS="5" COLS="50" WRAP="SOFT">'.db_result($result,0,'details').'</TEXTAREA><P>
		<B>If this item is on the SourceForge home page and you edit it, it will be removed from the home page.</B><BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>';

	} else {
		/*
			Show list of waiting news items
		*/

		$sql="SELECT * FROM news_bytes WHERE is_approved <> 4 AND group_id='$group_id'";
		$result=db_query($sql);
		$rows=db_numrows($result);
		if ($rows < 1) {
			echo '
				<H4>No Queued Items Found For Project: '.group_getname($group_id).'</H1>';
		} else {
			echo '
				<H4>These News Items Were Submitted For Project: '.group_getname($group_id).'</H4>
				<P>';
			for ($i=0; $i<$rows; $i++) {
				echo '
				<A HREF="/news/admin/?approve=1&id='.db_result($result,$i,'id').'&group_id='.
					db_result($result,$i,'group_id').'">'.
					db_result($result,$i,'summary').'</A><BR>';
			}
		}

	}
	news_footer(array());

} else if (user_ismember(714,'A')) {
	/*

		News uber-user admin pages

		Show all waiting news items except those already rejected.

		Admin members of project #714 (news project) can edit/change/approve news items

	*/
	if ($post_changes) {
		if ($approve) {
			if ($status==1) {
				/*
					Update the db so the item shows on the home page
				*/
				$sql="UPDATE news_bytes SET is_approved='1', date='".time()."', ".
					"summary='".htmlspecialchars($summary)."', details='".htmlspecialchars($details)."' WHERE id='$id'";
				$result=db_query($sql);
				if (!$result || db_affected_rows($result) < 1) {
					$feedback .= ' ERROR doing update ';
				} else {
					$feedback .= ' NewsByte Updated. ';
				}
			} else if ($status==2) {
				/*
					Move msg to deleted status
				*/
				$sql="UPDATE news_bytes SET is_approved='2' WHERE id='$id'";
				$result=db_query($sql);
				if (!$result || db_affected_rows($result) < 1) {
					$feedback .= ' ERROR doing update ';
					$feedback .= db_error();
				} else {
					$feedback .= ' NewsByte Deleted. ';
				}
			}

			/*
				Show the list_queue
			*/
			$approve='';
			$list_queue='y';
		}
	}

	news_header(array('title'=>'NewsBytes'));

	if ($approve) {
		/*
			Show the submit form
		*/

		$sql="SELECT groups.unix_group_name,news_bytes.* ".
			"FROM news_bytes,groups WHERE id='$id' ".
			"AND news_bytes.group_id=groups.group_id ";
		$result=db_query($sql);
		if (db_numrows($result) < 1) {
			exit_error('Error','Error - not found');
		}

		echo '
		<H3>Approve a NewsByte</H3>
		<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="for_group" VALUE="'.db_result($result,0,'group_id').'">
		<INPUT TYPE="HIDDEN" NAME="id" VALUE="'.db_result($result,0,'id').'">
		<B>Submitted for group:</B> <a href="/projects/'.strtolower(db_result($result,0,'unix_group_name')).'/">'.group_getname(db_result($result,0,'group_id')).'</a><BR>
		<B>Submitted by:</B> '.user_getname(db_result($result,0,'submitted_by')).'<BR>
		<INPUT TYPE="HIDDEN" NAME="approve" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<INPUT TYPE="RADIO" NAME="status" VALUE="1"> Approve For Front Page<BR>
		<INPUT TYPE="RADIO" NAME="status" VALUE="0"> Do Nothing<BR>
		<INPUT TYPE="RADIO" NAME="status" VALUE="2" CHECKED> Delete<BR>
		<B>Subject:</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" VALUE="'.db_result($result,0,'summary').'" SIZE="30" MAXLENGTH="60"><BR>
		<B>Details:</B><BR>
		<TEXTAREA NAME="details" ROWS="5" COLS="50" WRAP="SOFT">'.db_result($result,0,'details').'</TEXTAREA><BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>';

	} else {
		/*
			Show list of waiting news items
		*/

		$sql="SELECT * FROM news_bytes WHERE is_approved=0";
		$result=db_query($sql);
		$rows=db_numrows($result);
		if ($rows < 1) {
			echo '
				<H4>No Queued Items Found</H1>';
		} else {
			echo '
				<H4>These items need to be approved</H4>
				<P>';
			for ($i=0; $i<$rows; $i++) {
				echo '
				<A HREF="/news/admin/?approve=1&id='.db_result($result,$i,'id').'">'.db_result($result,$i,'summary').'</A><BR>';
			}
		}

		/*
			Show list of deleted news items for this week
		*/
		$old_date=(time()-(86400*7));

		$sql="SELECT * FROM news_bytes WHERE is_approved=2 AND date > '$old_date'";
		$result=db_query($sql);
		$rows=db_numrows($result);
		if ($rows < 1) {
			echo '
				<H4>No deleted items found for this week</H4>';
		} else {
			echo '
				<H4>These items were deleted this past week</H4>
				<P>';
			for ($i=0; $i<$rows; $i++) {
				echo '
				<A HREF="/news/admin/?approve=1&id='.db_result($result,$i,'id').'">'.db_result($result,$i,'summary').'</A><BR>';
			}
		}

		/*
			Show list of approved news items for this week
		*/

		$sql="SELECT * FROM news_bytes WHERE is_approved=1 AND date > '$old_date'";
		$result=db_query($sql);
		$rows=db_numrows($result);
		if ($rows < 1) {
			echo '
				<H4>No approved items found for this week</H4>';
		} else {
			echo '
				<H4>These items were approved this past week</H4>
				<P>';
			for ($i=0; $i<$rows; $i++) {
				echo '
				<A HREF="/news/admin/?approve=1&id='.db_result($result,$i,'id').'">'.db_result($result,$i,'summary').'</A><BR>';
			}
		}

	}
	news_footer(array());

} else {

	exit_error('Permission Denied.','Permission Denied. You have to be an admin on the project you are editing or a member of the SourceForge News team.');

}
?>
