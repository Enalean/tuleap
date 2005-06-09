<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');

//common forum tools which are used during the creation/editing of news items
require_once('www/forum/forum_utils.php');


$Language->loadLanguageMsg('news/news');


if ($group_id && $group_id != $GLOBALS['sys_news_group'] && user_ismember($group_id,'A')) {
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
				$feedback .= ' '.$Language->getText('news_admin_index','group_update_err').' ';
			} else {
				$feedback .= ' '.$Language->getText('news_admin_index','project_newsbyte_updated').' ';
			}
			/*
				Show the list_queue
			*/
			$approve='';
			$list_queue='y';
		}
	}

	news_header(array('title'=>$Language->getText('news_admin_index','title'),
			  'help'=>'NewsService.html'));

	if ($approve) {
		/*
			Show the submit form
		*/

		$sql="SELECT * FROM news_bytes WHERE id='$id' AND group_id='$group_id'";
		$result=db_query($sql);
		if (db_numrows($result) < 1) {
			exit_error($Language->getText('global','error'),$Language->getText('news_admin_index','not_found_err'));
		}
                $username=user_getname(db_result($result,0,'submitted_by'));

		echo '
		<H3>'.$Language->getText('news_admin_index','approve_for',group_getname($group_id)).'</H3>
		<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.db_result($result,0,'group_id').'">
		<INPUT TYPE="HIDDEN" NAME="id" VALUE="'.db_result($result,0,'id').'">

		<B>'.$Language->getText('news_admin_index','submitted_by').':</B> <a href="/users/'.$username.'">'.$username.'</a><BR>
		<INPUT TYPE="HIDDEN" NAME="approve" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">

 		<B>'.$Language->getText('global','status').':</B><BR>
                <INPUT TYPE="RADIO" NAME="status" VALUE="0" CHECKED> '.$Language->getText('news_admin_index','displayed').'<BR>
                <INPUT TYPE="RADIO" NAME="status" VALUE="4"> '.$Language->getText('news_admin_index','delete').'<BR>
 
		<B>'.$Language->getText('news_admin_index','subject').':</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" VALUE="'.db_result($result,0,'summary').'" SIZE="30" MAXLENGTH="60"><BR>
		<B>'.$Language->getText('news_admin_index','details').':</B><BR>
		<TEXTAREA NAME="details" ROWS="5" COLS="50" WRAP="SOFT">'.db_result($result,0,'details').'</TEXTAREA><P>
		<B>'.$Language->getText('news_admin_index','if_edit_delete',$GLOBALS['sys_name']).'</B><BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
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
				<H4>'.$Language->getText('news_admin_index','no_queued_item_found_for',group_getname($group_id)).'</H1>';
		} else {
			echo '
				<H4>'.$Language->getText('news_admin_index','new_items',group_getname($group_id)).'</H4>
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

} else if (user_ismember($GLOBALS['sys_news_group'],'A')) {
	/*

		News uber-user admin pages
		Show all waiting news items except those already rejected.
		Admin members of project #$sys_news_group (news project)
                can edit/change/approve news items

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
					$feedback .= ' '.$Language->getText('news_admin_index','update_err').' ';
				} else {
					$feedback .= ' '.$Language->getText('news_admin_index','newsbyte_updated').' ';
				}
			} else if ($status==2) {
				/*
					Move msg to deleted status
				*/
				$sql="UPDATE news_bytes SET is_approved='2' WHERE id='$id'";
				$result=db_query($sql);
				if (!$result || db_affected_rows($result) < 1) {
					$feedback .= ' '.$Language->getText('news_admin_index','update_err').' ';
					$feedback .= db_error();
				} else {
					$feedback .= ' '.$Language->getText('news_admin_index','newsbyte_deleted').' ';
				}
			}

			/*
				Show the list_queue
			*/
			$approve='';
			$list_queue='y';
		}
	}

	news_header(array('title'=>$Language->getText('news_admin_index','title')));

	if ($approve) {
		/*
			Show the submit form
		*/

		$sql="SELECT groups.unix_group_name,news_bytes.* ".
			"FROM news_bytes,groups WHERE id='$id' ".
			"AND news_bytes.group_id=groups.group_id ";
		$result=db_query($sql);
		if (db_numrows($result) < 1) {
			exit_error($Language->getText('global','error'),$Language->getText('news_admin_index','not_found_err'));
		}

                $username=user_getname(db_result($result,0,'submitted_by'));

		echo '
		<H3>'.$Language->getText('news_admin_index','approve').'</H3>
		<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="for_group" VALUE="'.db_result($result,0,'group_id').'">
		<INPUT TYPE="HIDDEN" NAME="id" VALUE="'.db_result($result,0,'id').'">
		<B>'.$Language->getText('news_admin_index','submitted_for_group').':</B> <a href="/projects/'.strtolower(db_result($result,0,'unix_group_name')).'/">'.group_getname(db_result($result,0,'group_id')).'</a><BR>
		<B>'.$Language->getText('news_admin_index','submitted_by').':</B> <a href="/users/'.$username.'">'.$username.'</a><BR>
		<INPUT TYPE="HIDDEN" NAME="approve" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<INPUT TYPE="RADIO" NAME="status" VALUE="1"> '.$Language->getText('news_admin_index','approve_for_front').'<BR>
		<INPUT TYPE="RADIO" NAME="status" VALUE="0"> '.$Language->getText('news_admin_index','do_nothing').'<BR>
		<INPUT TYPE="RADIO" NAME="status" VALUE="2" CHECKED> '.$Language->getText('news_admin_index','delete').'<BR>
		<B>'.$Language->getText('news_admin_index','subject').':</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" VALUE="'.db_result($result,0,'summary').'" SIZE="30" MAXLENGTH="60"><BR>
		<B>'.$Language->getText('news_admin_index','details').':</B><BR>
		<TEXTAREA NAME="details" ROWS="5" COLS="50" WRAP="SOFT">'.db_result($result,0,'details').'</TEXTAREA><BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
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
				<H4>'.$Language->getText('news_admin_index','no_queued_item_found').'</H1>';
		} else {
			echo '
				<H4>'.$Language->getText('news_admin_index','need_approve').'</H4>
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
				<H4>'.$Language->getText('news_admin_index','no_deleted_items_this_week').'</H4>';
		} else {
			echo '
				<H4>'.$Language->getText('news_admin_index','items_deleted_last_week').'</H4>
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
				<H4>'.$Language->getText('news_admin_index','no_approved_items_this_week').'</H4>';
		} else {
			echo '
				<H4>'.$Language->getText('news_admin_index','items_approved_last_week').'</H4>
				<P>';
			for ($i=0; $i<$rows; $i++) {
				echo '
				<A HREF="/news/admin/?approve=1&id='.db_result($result,$i,'id').'">'.db_result($result,$i,'summary').'</A><BR>';
			}
		}

	}
	news_footer(array());

} else {

  exit_error($Language->getText('news_admin_index','permission_denied'),$Language->getText('news_admin_index','need_to_be_admin',$GLOBALS['sys_name']));

}
?>
