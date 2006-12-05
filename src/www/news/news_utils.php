<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*
	News System
	By Tim Perdue, Sourceforge, 12/99
*/

require_once('www/project/admin/permissions.php');
require_once('www/project/admin/ugroup_utils.php');

$Language->loadLanguageMsg('news/news');

function news_header($params) {
  global $HTML,$group_id,$news_name,$news_id,$Language;

	$params['toptab']='news';
	$params['group']=$group_id;

	/*
		Show horizontal links
	*/
	if ($group_id && ($group_id != $GLOBALS['sys_news_group'])) {
		site_project_header($params);
	} else {
		$HTML->header($params);
		echo '
			<H2>'.$GLOBALS['sys_name'].' <A HREF="/news/">'.$Language->getText('news_index','news').'</A></H2>';
	}
        if (!isset($params['pv']) || !$params['pv']){
            echo '<P><B>';
            // submit link and admin link are only displayed if the user is a project administrator.
            if (user_ismember($group_id, 'A')) {
                echo '<A HREF="/news/submit.php?group_id='.$group_id.'">'.$Language->getText('news_utils','submit_news').'</A> | <A HREF="/news/admin/?group_id='.$group_id.'">'.$Language->getText('news_utils','admin').'</A>';
                if (isset($params['help'])) {
                    echo ' | ';
                }
            }
            if (isset($params['help'])) {
                echo help_button($params['help'],false,$Language->getText('global','help'));
            }
            echo '</b><P>';
        }
}

function news_footer($params) {
    site_project_footer($params);
}

function news_show_latest($group_id='',$limit=10,$show_summaries=true,$allow_submit=true,$flat=false,$tail_headlines=0) {
    global $sys_datefmt, $sys_news_group,$Language;
    $return  = "";
    if (!$group_id) {
	$group_id=$sys_news_group;
    }

    /*
       Show a simple list of the latest news items with a link to the forum
       */

    if ($group_id != $sys_news_group) {
	$wclause="news_bytes.group_id='$group_id' AND news_bytes.is_approved <> '4'";
    } else {
	$wclause='news_bytes.is_approved=1';
    }

    $sql="SELECT groups.group_name,groups.unix_group_name,user.user_name,news_bytes.forum_id,news_bytes.summary,news_bytes.date,news_bytes.details ".
	"FROM user,news_bytes,groups ".
	"WHERE $wclause ".
	"AND user.user_id=news_bytes.submitted_by ".
	"AND news_bytes.group_id=groups.group_id ".
	'ORDER BY date DESC LIMIT '.($limit+$tail_headlines);

    $result=db_query($sql);
    $rows=db_numrows($result);

    if (!$result || $rows < 1) {
	$return .= '<H3>'.$Language->getText('news_utils','no_news_item_found').'</H3>';
	$return .= db_error();
    } else {
	echo '
			<DL COMPACT>';
	for ($i=0; $i<$rows; $i++) {
	    //check if the news is private (project members) or public (registered users)
	    $forum_id=db_result($result,$i,'forum_id');
	    if (news_check_permission($forum_id,$group_id)) {
	
	        if ($show_summaries && $limit) {
		    //get the first paragraph of the story
		    $arr=explode("\n",db_result($result,$i,'details'));
                
                    //if the first paragraph is short, and so are following paragraphs, add the next paragraph on
		    if ((strlen($arr[0]) < 200) && isset($arr[1]) && isset($arr[2]) && (strlen($arr[1].$arr[2]) < 300) && (strlen($arr[2]) > 5)) {
		        $summ_txt='<BR>'. util_make_links( $arr[0].'<BR>'.$arr[1].'<BR>'.$arr[2], $group_id );
		    } else {
		        $summ_txt='<BR>'. util_make_links( $arr[0], $group_id );
		    }
		    //show the project name 
		    if (db_result($result,$i,'type')==2) {
		        $group_type='/foundry/';
		    } else {
		        $group_type='/projects/';
		    }
		    $proj_name=' &nbsp; - &nbsp; <A HREF="'.$group_type. strtolower(db_result($result,$i,'unix_group_name')) .'/">'. db_result($result,$i,'group_name') .'</A>';
	        } else {
		    $proj_name='';
		    $summ_txt='';
	        }

			
	        if (!$limit) {

		    $return .= '<li><A HREF="/forum/forum.php?forum_id='. db_result($result,$i,'forum_id') .'"><B>'. db_result($result,$i,'summary') . '</B></A>';
		    $return .= ' &nbsp; <I>'. format_date($sys_datefmt,db_result($result,$i,'date')).'</I><br>';
	        } else {
		    $return .= '
		    		<A HREF="/forum/forum.php?forum_id='. db_result($result,$i,'forum_id') .'"><B>'. db_result($result,$i,'summary') . '</B></A>';

		    if (!$flat) {
		        $return .= '
                                               <BR>&nbsp;';
		    }
		    $return .= '&nbsp;&nbsp;&nbsp;<I>'.db_result($result,$i,'user_name') .' - '.
		        format_date($sys_datefmt,db_result($result,$i,'date')) .' </I>'.
		        $proj_name . $summ_txt;

		    $sql='SELECT count(*) FROM forum WHERE group_forum_id='.db_result($result,$i,'forum_id');
		    $res2 = db_query($sql);
		    $num_comments = db_result($res2,0,0);

		    if (!$num_comments) {
		        $num_comments = '0';
		    }

		    if ($num_comments == 1) {
		        $comments_txt = ' '.$Language->getText('news_utils','comment');
		    } else {
		        $comments_txt = ' '.$Language->getText('news_utils','comments');
		    }

		    $return .= '<div align="center">(' . $num_comments . $comments_txt . ') <A HREF="/forum/forum.php?forum_id='. db_result($result,$i,'forum_id') .'">['.$Language->getText('news_utils','read_more_comments').']</a></div><HR width="100%" size="1" noshade>';
                                      
	        }

	        if ($limit==1 && $tail_headlines) {
		    $return .= "<ul>";
	        }
	        if ($limit) {
		    $limit--;
	        }
	    }    
	}
    }
    if ($group_id != $sys_news_group) {
	$archive_url='/news/?group_id='.$group_id;
    } else {
	$archive_url='/news/';
    }
    
    if ($tail_headlines) {
	$return .= '</ul><HR width="100%" size="1" noshade>'."\n";
    }
    
    $return .= '<div align="center">'
	.'<a href="'.$archive_url.'">['.$Language->getText('news_utils','news_archive').']</a></div>';

    if ($allow_submit && $group_id != $sys_news_group) {
	//you can only submit news from a project now
	//you used to be able to submit general news
	$return .= '<div align="center"><A HREF="/news/submit.php?group_id='.$group_id.'"><FONT SIZE="-1">['.$Language->getText('news_utils','submit_news').']</FONT></A></center>';
    }

    return $return;
}

function news_foundry_latest($group_id=0,$limit=5,$show_summaries=true) {
    global $sys_datefmt,$Language;
    $return = "";
	/*
		Show a the latest news for a portal 
	*/

	$sql="SELECT groups.group_name,groups.unix_group_name,user.user_name,news_bytes.forum_id,news_bytes.summary,news_bytes.date,news_bytes.details ".
		"FROM user,news_bytes,groups,foundry_news ".
		"WHERE foundry_news.foundry_id='$group_id' ".
		"AND user.user_id=news_bytes.submitted_by ".
		"AND foundry_news.news_id=news_bytes.id ".
		"AND news_bytes.group_id=groups.group_id ".
		"AND foundry_news.is_approved=1 ".
		"ORDER BY news_bytes.date DESC LIMIT $limit";

	$result=db_query($sql);
	$rows=db_numrows($result);

	if (!$result || $rows < 1) {
		$return .= '<H3>'.$Language->getText('news_utils','no_news_item_found').'</H3>';
		$return .= db_error();
	} else {
		for ($i=0; $i<$rows; $i++) {
			if ($show_summaries) {
				//get the first paragraph of the story
				$arr=explode("\n",db_result($result,$i,'details'));
				if ((strlen($arr[0]) < 200) && (strlen($arr[1].$arr[2]) < 300) && (strlen($arr[2]) > 5)) {
					$summ_txt=util_make_links( $arr[0].'<BR>'.$arr[1].'<BR>'.$arr[2] , $group_id );
				} else {
					$summ_txt=util_make_links( $arr[0], $group_id );
				}

				//show the project name
				$proj_name=' &nbsp; - &nbsp; <A HREF="/projects/'. strtolower(db_result($result,$i,'unix_group_name')) .'/">'. db_result($result,$i,'group_name') .'</A>';
			} else {
				$proj_name='';
				$summ_txt='';
			}
			$return .= '
				<A HREF="/forum/forum.php?forum_id='. db_result($result,$i,'forum_id') .'"><B>'. db_result($result,$i,'summary') . '</B></A>
				<BR><I>'. db_result($result,$i,'user_name') .' - '.
					format_date($sys_datefmt,db_result($result,$i,'date')) . $proj_name . '</I>
				'. $summ_txt .'<HR WIDTH="100%" SIZE="1">';
		}
	}
	return $return;
}

function get_news_name($id) {
	/*
		Takes an ID and returns the corresponding forum name
	*/
	$sql="SELECT summary FROM news_bytes WHERE id='$id'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		return "Not Found";
	} else {
		return db_result($result, 0, 'summary');
	}
}

function get_news_name_from_forum_id($id) {
	/*
		Takes an ID and returns the corresponding forum name
	*/
	$sql="SELECT summary FROM news_bytes WHERE forum_id='$id'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		return "Not Found";
	} else {
		return db_result($result, 0, 'summary');
	}
}

function news_check_permission($forum_id,$group_id) {
	/*
		Takes a forum_id and checks if user is authorized to read the piece of news associated to this forum_id
	*/
	
	//cast  input
	$_forum_id = (int) $forum_id;
	$_group_id = (int) $group_id;
	
	if (((permission_exist('NEWS_READ', $_forum_id)) && (permission_is_authorized('NEWS_READ',$_forum_id,user_getid(),$_group_id))) || (!permission_exist('NEWS_READ', $_forum_id))) {
	    return true;
        } else {
	    return false;
	}    
}

/**
 * insert for this forum_id a news_read permission for project members only 
 */
function news_insert_permissions($forum_id,$group_id) {
	
  global $Language,$UGROUP_PROJECT_MEMBERS;
	
	
	// cast  inputs
	$_forum_id = (int) $forum_id;
	
	if (permission_add_ugroup($group_id,'NEWS_READ',$_forum_id,$UGROUP_PROJECT_MEMBERS)) {
	    $feedback .= ' '.$Language->getText('news_submit','news_perm_create_success').' ';
	} else {
	    $feedback .= ' '.$Language->getText('news_submit','insert_err').' ';
	}
}

function news_update_permissions($forum_id,$is_private,$group_id) {
	
	global $Language,$UGROUP_PROJECT_MEMBERS;
	
	/*
		Takes forum_id and permission, and updates the permission of the corresponding entry in 'permissions' table
	*/
	
	// cast inputs
	$_forum_id = (int) $forum_id;
	$_is_private = (int) $is_private;
	
	if ($_is_private) {
	  permission_clear_all($group_id, 'NEWS_READ', $_forum_id, false);
	  if (permission_add_ugroup($group_id,'NEWS_READ',$_forum_id,$UGROUP_PROJECT_MEMBERS)) {
	    $feedback .= ' '.$Language->getText('news_submit','news_perm_update_success').' ';
	  } else {
	    $feedback .= ' '.$Language->getText('news_admin_index','update_err').' ';
	  }
	} else {
	  if (permission_clear_all($group_id, 'NEWS_READ', $_forum_id, false)) {
	    $feedback .= ' '.$Language->getText('news_submit','news_perm_update_success').' ';
	  } else {
	    $feedback .= ' '.$Language->getText('news_admin_index','update_err').' ';
	  }	
	}
	
}

function news_read_permissions($forum_id) {
	
	/*
		Takes forum_id and reads the permission of the corresponding news. Returns a result set.
	*/
	
	// cast inputs
	$_forum_id = (int) $forum_id;

	return permission_db_authorized_ugroups('NEWS_READ',$_forum_id);
}

?>
