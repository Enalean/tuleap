<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');

//common forum tools which are used during the creation/editing of news items
require_once('www/forum/forum_utils.php');
require_once('www/project/admin/ugroup_utils.php');

$Language->loadLanguageMsg('news/news');

// admin pages can be reached by news admin (N2) or project admin (A) 
if (isset($group_id) && $group_id && $group_id != $GLOBALS['sys_news_group'] && (user_ismember($group_id, 'A') || user_ismember($group_id,'N2'))) {
	/*

		Per-project admin pages.

		Shows their own news items so they can edit/update.

		If their news is on the homepage, and they edit, it is removed from 
			sf.net homepage.

	*/
	if (isset($post_changes) && $post_changes) {
		if (isset($approve) && $approve) {
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
			
			if (!$result) {
				$GLOBALS['Response']->addFeedback('error', $Language->getText('news_admin_index','group_update_err'));
				
			} else {
				$GLOBALS['Response']->addFeedback('info', $Language->getText('news_admin_index','project_newsbyte_updated'));
				
				// update/create  news permissions
				$qry1="SELECT * FROM news_bytes WHERE id='$id'";
				$res1=db_query($qry1);
				$forum_id=db_result($res1,0,'forum_id');
				$res2 = news_read_permissions($forum_id);
				if (db_numrows($res2) > 0) {
				    //permission on this news is already defined, have to be updated
				    news_update_permissions($forum_id,$is_private,$group_id);
				} else {
				    //permission of this news not yet defined
				    if ($is_private) {
				      news_insert_permissions($forum_id,$group_id);
				    }
				}
							
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
    
    echo '<H3>'.$Language->getText('news_admin_index','news_admin').'</H3>';
    
	if (isset($approve) && $approve) {
		/*
			Show the submit form
		*/

		$sql="SELECT * FROM news_bytes WHERE id='$id' AND group_id='$group_id'";
		$result=db_query($sql);
		if (db_numrows($result) < 1) {
			exit_error($Language->getText('global','error'),$Language->getText('news_admin_index','not_found_err'));
		}
                $username=user_getname(db_result($result,0,'submitted_by'));
		$forum_id=db_result($result,0,'forum_id');
		$res = news_read_permissions($forum_id);
		// check on db_result($res,0,'ugroup_id') == $UGROUP_ANONYMOUS only to be consistent
		// with ST DB state
		if (db_numrows($res) < 1 || (db_result($res,0,'ugroup_id') == $UGROUP_ANONYMOUS)) {
		    $check_private="";
		    $check_public="CHECKED";
		} else {
		    $check_private="CHECKED";
		    $check_public="";
		}    

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
	        
		<B>'.$Language->getText('news_submit','news_privacy').':</B><BR> 
		<INPUT TYPE="RADIO" NAME="is_private" VALUE="0" '.$check_public.'> '.$Language->getText('news_submit','public_news').'<BR>
		<INPUT TYPE="RADIO" NAME="is_private" VALUE="1" '.$check_private.'> '.$Language->getText('news_submit','private_news').'<BR>
		
		<B>'.$Language->getText('news_admin_index','subject').':</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" VALUE="'.db_result($result,0,'summary').'" SIZE="44" MAXLENGTH="60"><BR>
		<B>'.$Language->getText('news_admin_index','details').':</B><BR>
		<TEXTAREA NAME="details" ROWS="8" COLS="50" WRAP="SOFT">'.db_result($result,0,'details').'</TEXTAREA><P>
		<B>'.$Language->getText('news_admin_index','if_edit_delete',$GLOBALS['sys_name']).'</B><BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
		</FORM>';

	} else {
		/*
			Show list of waiting news items
		*/

		$sql="SELECT * FROM news_bytes WHERE is_approved <> 4 AND group_id='$group_id' ORDER BY date DESC";
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
	if (isset($post_changes) && $post_changes) {
		if (isset($approve) && $approve) {
			if ($status==1) {
				/*
					Update the db so the item shows on the home page
				*/
				$sql="UPDATE news_bytes SET is_approved='1', date='".time()."', ".
					"summary='".htmlspecialchars($summary)."', details='".htmlspecialchars($details)."' WHERE id='$id'";
				$result=db_query($sql);
				if (!$result || db_affected_rows($result) < 1) {
					$GLOBALS['Response']->addFeedback('error', $Language->getText('news_admin_index','update_err'));
				} else {
					$GLOBALS['Response']->addFeedback('info', $Language->getText('news_admin_index','newsbyte_updated'));
				}
			} else if ($status==2) {
				/*
					Move msg to deleted status
				*/
				$sql="UPDATE news_bytes SET is_approved='2' WHERE id='$id'";
				$result=db_query($sql);
				if (!$result || db_affected_rows($result) < 1) {
					$GLOBALS['Response']->addFeedback('error', $Language->getText('news_admin_index','update_err').' '.db_error());
				} else {
					$GLOBALS['Response']->addFeedback('info', $Language->getText('news_admin_index','newsbyte_deleted'));
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

	if (isset($approve) && $approve) {
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
        $news_date = util_timestamp_to_userdateformat(db_result($result,0,'date'), true);

		echo '
		<H3>'.$Language->getText('news_admin_index','approve').'</H3>
		<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="for_group" VALUE="'.db_result($result,0,'group_id').'">
		<INPUT TYPE="HIDDEN" NAME="id" VALUE="'.db_result($result,0,'id').'">
		<B>'.$Language->getText('news_admin_index','submitted_for_group').':</B> <a href="/projects/'.strtolower(db_result($result,0,'unix_group_name')).'/">'.group_getname(db_result($result,0,'group_id')).'</a><BR>
		<B>'.$Language->getText('news_admin_index','submitted_by').':</B> <a href="/users/'.$username.'">'.$username.'</a><BR>
        <B>'.$Language->getText('news_admin_index','submitted_on').':</B> '.$news_date.'<BR>        
		<INPUT TYPE="HIDDEN" NAME="approve" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<INPUT TYPE="RADIO" NAME="status" VALUE="1"> '.$Language->getText('news_admin_index','approve_for_front').'<BR>
		<INPUT TYPE="RADIO" NAME="status" VALUE="0"> '.$Language->getText('news_admin_index','do_nothing').'<BR>
		<INPUT TYPE="RADIO" NAME="status" VALUE="2" CHECKED> '.$Language->getText('news_admin_index','reject').'<BR>
		<B>'.$Language->getText('news_admin_index','subject').':</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" VALUE="'.db_result($result,0,'summary').'" SIZE="44" MAXLENGTH="60"><BR>
		<B>'.$Language->getText('news_admin_index','details').':</B><BR>
		<TEXTAREA NAME="details" ROWS="8" COLS="50" WRAP="SOFT">'.db_result($result,0,'details').'</TEXTAREA><BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
		</FORM>';

	} else {
		/*
			Show list of waiting news items
		*/

		if (isset($approve_all) && $approve_all) {
		    $sql="UPDATE news_bytes SET is_approved='1' WHERE is_approved='3'";
		    $res=db_query($sql);
		    if (!$res) {
		        $feedback .= ' '.$Language->getText('news_admin_index','update_err').' ';
		    } else {
		        $feedback .= ' '.$Language->getText('news_admin_index','newsbyte_updated').' ';
		    }
		}
				
		$sql="SELECT * FROM news_bytes WHERE is_approved=0 OR is_approved=3";
		$result=db_query($sql);
		$rows=db_numrows($result);
		if ($rows < 1) {
			echo '
				<H4>'.$Language->getText('news_admin_index','no_queued_item_found').'</H1>';
		} else {
			echo '
				<H4>'.$Language->getText('news_admin_index','need_approve').'</H4>
				<P><ul><li><strong>'.$Language->getText('news_admin_index','approve_legend',$GLOBALS['sys_name']).'
				</strong></ul><P>';
				
			for ($i=0; $i<$rows; $i++) {
			    //if the news is private, not display it in the list of news to be approved
			    $forum_id=db_result($result,$i,'forum_id');
                $res = news_read_permissions($forum_id);
			    // check on db_result($res,0,'ugroup_id') == $UGROUP_ANONYMOUS only to be consistent
			    // with ST DB state
			    if ((db_numrows($res) < 1) || (db_result($res,0,'ugroup_id') == $UGROUP_ANONYMOUS)) {
			        $is_approved=db_result($result,$i,'is_approved');
				    if ($is_approved == '3') {
				        //the submitter of this news asked to promote it ==>  display an icon
				        echo '
				            <IMG SRC="'.util_get_image_theme("ic/p_news.png").'" alt="'.$Language->getText('news_admin_index','approve_alt',$GLOBALS['sys_name']).'" title="'.$Language->getText('news_admin_index','approve_alt',$GLOBALS['sys_name']).'" /> <A HREF="/news/admin/?approve=1&id='.db_result($result,$i,'id').'">'.db_result($result,$i,'summary').'</A><BR>';
				    } else {
				        echo '
				            <A HREF="/news/admin/?approve=1&id='.db_result($result,$i,'id').'">'.db_result($result,$i,'summary').'</A><BR>';
			        }
			    }
			}
		}

		//Display [Approve All] hyper-link when there are news asked for promotion
		$sql="SELECT * FROM news_bytes WHERE is_approved=3";
		$res=db_query($sql);
		if (db_numrows($res) > 0) {
		    echo '<P>
			 <A HREF="/news/admin/?approve_all=1">'.$Language->getText('news_admin_index','approve_all').'</A>';   
		} else {
		    echo '<P>'.$Language->getText('news_admin_index','approved');   
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

  exit_error($Language->getText('news_admin_index','permission_denied'),$Language->getText('news_admin_index','need_to_be_admin'));

}
?>
