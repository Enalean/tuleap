<?php

require_once('Widget.class.php');

/**
* Widget_ProjectPublicAreas
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_ProjectPublicAreas extends Widget {
    function Widget_ProjectPublicAreas() {
        $this->Widget('projectpublicareas');
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('include_project_home','public_areas');
    }
    function getContent() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $project =& project_get_object($group_id);
        if ($project->usesHomePage()) {
            print "<A ";
            if (substr($project->getHomePage(), 0, 1)!="/") {
                // Absolute link -> open new window on click
                print "target=_blank ";
            }
            print 'href="' . $project->getHomePage() . '">';
            html_image("ic/home16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$GLOBALS['Language']->getText('include_project_home','homepage')));
            print '&nbsp;'.$GLOBALS['Language']->getText('include_project_home','proj_home').'</A>';
        }
        
        // ################## forums
        
        if ($project->usesForum()) {
            print '<HR SIZE="1" width="99%" NoShade><A href="/forum/?group_id='.$group_id.'">';
            html_image("ic/notes16.png",array('width'=>'20', 'height'=>'20', 'alt'=>$GLOBALS['Language']->getText('include_project_home','public_forums'))); 
            print '&nbsp;'.$GLOBALS['Language']->getText('include_project_home','public_forums').'</A>';
            $res_count = db_query("SELECT count(forum.msg_id) AS count FROM forum,forum_group_list WHERE "
                . "forum_group_list.group_id=$group_id AND forum.group_forum_id=forum_group_list.group_forum_id "
                . "AND forum_group_list.is_public=1");
            $row_count = db_fetch_array($res_count);
            print ' ( '.$GLOBALS['Language']->getText('include_project_home','msg',$row_count['count']).' ';
        
            $res_count = db_query("SELECT count(*) AS count FROM forum_group_list WHERE group_id=$group_id "
                . "AND is_public=1");
            $row_count = db_fetch_array($res_count);
            print $GLOBALS['Language']->getText('include_project_home','forums',$row_count['count'])." )\n";
        /*
            $sql="SELECT * FROM forum_group_list WHERE group_id='$group_id' AND is_public=1";
            $res2 = db_query ($sql);
            $rows = db_numrows($res2);
            for ($j = 0; $j < $rows; $j++) {
                echo '<BR> &nbsp; - <A HREF="forum.php?forum_id='.db_result($res2, $j, 'group_forum_id').'&et=0">'.
                    db_result($res2, $j, 'forum_name').'</A> ';
                //message count
                echo '('.db_result(db_query("SELECT count(*) FROM forum WHERE group_forum_id='".db_result($res2, $j, 'group_forum_id')."'"),0,0).' msgs)';
            }
        */
        }
        
        // ##################### Bug tracking 
        
        if ($project->usesBugs()) {
            print '<HR SIZE="1" width="99%" NoShade><A href="/bugs/?group_id='.$group_id.'">';
            html_image("ic/bug16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$GLOBALS['Language']->getText('include_project_home','bug_track'))); 
            print '&nbsp;'.$GLOBALS['Language']->getText('include_project_home','bug_track').'</A>';
            $res_count = db_query("SELECT count(*) AS count FROM bug WHERE group_id=$group_id AND status_id != 3");
            $row_count = db_fetch_array($res_count);
            print " ( <B>$row_count[count]</B>";
            $res_count = db_query("SELECT count(*) AS count FROM bug WHERE group_id=$group_id");
            $row_count = db_fetch_array($res_count);
            print ' '.$GLOBALS['Language']->getText('include_project_home','open_bugs').', '.$GLOBALS['Language']->getText('include_project_home','total',$row_count['count']).' )';
        }
        
        // ##################### Support Manager (only for Active)
         
        if ($project->usesSupport()) {
            print '
            <HR SIZE="1" width="99%" NoShade>
            <A href="/support/?group_id='.$group_id.'">';
            html_image("ic/support16b.jpg",array('width'=>'20', 'height'=>'20', 'alt'=>$GLOBALS['Language']->getText('include_project_home','supp_manager')));
            print '&nbsp;'.$GLOBALS['Language']->getText('include_project_home','tech_supp_manager').'</A>';
            $res_count = db_query("SELECT count(*) AS count FROM support WHERE group_id=$group_id");
            $row_count = db_fetch_array($res_count);
            $res_count = db_query("SELECT count(*) AS count FROM support WHERE group_id=$group_id AND support_status_id='1'");
            $row_count2 = db_fetch_array($res_count);
            print ' ( '.$GLOBALS['Language']->getText('include_project_home','open_requ', $row_count2['count']).', '.$GLOBALS['Language']->getText('include_project_home','open_requ', $row_count['count']).' )';
        }
        
        // ##################### Doc Manager (only for Active)
        
        if ($project->usesDocman()) {
            print '
            <HR SIZE="1" width="99%" NoShade>
            <A href="/docman/?group_id='.$group_id.'">';
            html_image("ic/docman16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$GLOBALS['Language']->getText('include_project_home','doc')));
            print '&nbsp;'.$GLOBALS['Language']->getText('include_project_home','doc_man').'</A>';
        /*
            $res_count = db_query("SELECT count(*) AS count FROM support WHERE group_id=$group_id");
            $row_count = db_fetch_array($res_count);
            $res_count = db_query("SELECT count(*) AS count FROM support WHERE group_id=$group_id AND support_status_id='1'");
            $row_count2 = db_fetch_array($res_count);
            print " ( <B>$row_count2[count]</B>";
            print " open requests, <B>$row_count[count]</B> total )";
        */
        }
        
        // ##################### Patch Manager (only for Active)
        
        if ($project->usesPatch()) {
            print '
                <HR SIZE="1" width="99%" NoShade>
                <A href="/patch/?group_id='.$group_id.'">';
            html_image("ic/patch.png",array('width'=>'20', 'height'=>'20', 'alt'=>$GLOBALS['Language']->getText('include_project_home','patch_manager')));
            print '&nbsp;'.$GLOBALS['Language']->getText('include_project_home','patch_manager').'</A>';
            $res_count = db_query("SELECT count(*) AS count FROM patch WHERE group_id=$group_id");
            $row_count = db_fetch_array($res_count);
            $res_count = db_query("SELECT count(*) AS count FROM patch WHERE group_id=$group_id AND patch_status_id='1'");
            $row_count2 = db_fetch_array($res_count);
            print ' ( '.$GLOBALS['Language']->getText('include_project_home','open_patches',$row_count2['count']).', '.$GLOBALS['Language']->getText('include_project_home','total',$row_count['count']).' )';
        }
        
        // ##################### Mailing lists (only for Active)
        
        if ($project->usesMail()) {
            print '<HR SIZE="1" width="99%" NoShade><A href="/mail/?group_id='.$group_id.'">';
            html_image("ic/mail16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$GLOBALS['Language']->getText('include_project_home','mail_lists'))); 
            print '&nbsp;'.$GLOBALS['Language']->getText('include_project_home','mail_lists').'</A>';
            $res_count = db_query("SELECT count(*) AS count FROM mail_group_list WHERE group_id=$group_id AND is_public=1");
            $row_count = db_fetch_array($res_count);
            print ' ( '.$GLOBALS['Language']->getText('include_project_home','public_mail_lists',$row_count['count']).' )';
        }
        
        // ##################### Task Manager (only for Active)
        
        if ($project->usesPm()) {
            print '<HR SIZE="1" width="99%" NoShade><A href="/pm/?group_id='.$group_id.'">';
            html_image("ic/taskman16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$GLOBALS['Language']->getText('include_project_home','task_manager')));
            print '&nbsp;'.$GLOBALS['Language']->getText('include_project_home','proj_task_man').'</A>';
            $sql="SELECT * FROM project_group_list WHERE group_id='$group_id' AND is_public=1";
            $result = db_query ($sql);
            $rows = db_numrows($result);
            if (!$result || $rows < 1) {
                echo '<BR><I>'.$GLOBALS['Language']->getText('include_project_home','no_public_proj').'</I>';
            } else {
                for ($j = 0; $j < $rows; $j++) {
                    echo '
                    <BR> &nbsp; - <A HREF="/pm/task.php?group_project_id='.db_result($result, $j, 'group_project_id').
                    '&group_id='.$group_id.'&func=browse">'.db_result($result, $j, 'project_name').'</A>';
                }
        
            }
        }
        
        // ######################### Wiki (only for Active)
        
        if ($project->usesWiki()) {
            print '<HR SIZE="1" width="99%" NoShade><A href="/wiki/?group_id='.$group_id.'">';
            html_image("ic/wiki.png",array('width'=>'18', 'height'=>'12', 'alt'=>$GLOBALS['Language']->getText('include_project_home','wiki')));
            print ' '.$GLOBALS['Language']->getText('include_project_home','wiki').'</A>';
                $wiki=new Wiki($group_id);
            echo ' ( '.$GLOBALS['Language']->getText('include_project_home','nb_wiki_pages',$wiki->getProjectPageCount()).' )';
        }
        
        // ######################### Surveys (only for Active)
        
        if ($project->usesSurvey()) {
            print '<HR SIZE="1" width="99%" NoShade><A href="/survey/?group_id='.$group_id.'">';
            html_image("ic/survey16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$GLOBALS['Language']->getText('include_project_home','surveys')));
            print ' '.$GLOBALS['Language']->getText('include_project_home','surveys').'</A>';
            $sql="SELECT count(*) from surveys where group_id='$group_id' AND is_active='1'";
            $result=db_query($sql);
            echo ' ( '.$GLOBALS['Language']->getText('include_project_home','nb_surveys',db_result($result,0,0)).' )';
        }
        
        // ######################### CVS (only for Active)
        
        if ($project->usesCVS()) {
            print '<HR SIZE="1" width="99%" NoShade><A href="/cvs/?group_id='.$group_id.'">';
            html_image("ic/cvs16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>'CVS'));
            print ' '.$GLOBALS['Language']->getText('include_project_home','cvs_repo').'</A>';
        // LJ Cvs checkouts added 
            $sql = "SELECT SUM(cvs_commits) AS commits, SUM(cvs_adds) AS adds, SUM(cvs_checkouts) AS checkouts from stats_project where group_id='$group_id'";
            $result = db_query($sql);
                $cvs_commit_num=db_result($result,0,0);
                $cvs_add_num=db_result($result,0,1);
                $cvs_co_num=db_result($result,0,2);
                if (!$cvs_commit_num) $cvs_commit_num=0;
                if (!$cvs_add_num) $cvs_add_num=0;
                if (!$cvs_co_num) $cvs_co_num=0;
            $uri = session_make_url('/cvs/viewvc.php/?root='.$project->getUnixName(false).'&roottype=cvs');
        
                echo ' ( '.$GLOBALS['Language']->getText('include_project_home','commits',$cvs_commit_num).', '.$GLOBALS['Language']->getText('include_project_home','adds',$cvs_add_num).', '.$GLOBALS['Language']->getText('include_project_home','co',$cvs_co_num).' )';
                if ($cvs_commit_num || $cvs_add_num || $cvs_co_num) {
        
                    echo '<br> &nbsp; - <a href="'.$uri.'">'.$GLOBALS['Language']->getText('include_project_home','browse_cvs').'</a>';
                }
        }
        
        // ######################### Subversion (only for Active)
        
        if ($project->usesService('svn')) {
            print '<HR SIZE="1" width="99%" NoShade><A href="/svn/?group_id='.$group_id.'">';
            html_image("ic/svn16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Subversion'));
            print ' '.$GLOBALS['Language']->getText('include_project_home','svn_repo').'</A>';
            $sql = "SELECT SUM(svn_access_count) AS accesses from group_svn_full_history where group_id='$group_id'";
            $result = db_query($sql);
                $svn_accesses = db_result($result,0,0);
                if (!$svn_accesses) $svn_accesses=0;
        
                echo ' ( '.$GLOBALS['Language']->getText('include_project_home','accesses',$svn_accesses).' )';
                if ($svn_accesses) {
                $uri = session_make_url('/svn/viewvc.php/?root='.$project->getUnixName(false).'&roottype=svn');
                    echo '<br> &nbsp; - <a href="'.$uri.'">'.$GLOBALS['Language']->getText('include_project_home','browse_svn').'</a>';
                }
        }
        
        // ######################### File Releases (only for Active)
        
        if ($project->usesFile()) {
            echo $project->services['file']->getPublicArea();
        }
        
        // ######################### Trackers (only for Active)
        if ( $project->usesTracker() ) {
            print '<HR SIZE="1" width="99%" NoShade><A href="/tracker/?group_id='.$group_id.'">';
            html_image("ic/tracker20w.png",array('width'=>'20', 'height'=>'20', 'alt'=>$GLOBALS['Language']->getText('include_project_home','trackers')));
            print ' '.$GLOBALS['Language']->getText('include_project_home','trackers').'</A>';
            //	  
            //  get the Group object
            //	  
            $group = group_get_object($group_id);
            if (!$group || !is_object($group) || $group->isError()) {
                exit_no_group();
            }		   
            $atf = new ArtifactTypeFactory($group);
            if (!$group || !is_object($group) || $group->isError()) {
                exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('include_project_home','no_arttypefact'));
            }
            
            // Get the artfact type list
            $at_arr = $atf->getArtifactTypes();
            
            if (!$at_arr || count($at_arr) < 1) {
                echo '<br><i>'.$GLOBALS['Language']->getText('include_project_home','no_trackers_accessible').'</i>';
            } else {
                for ($j = 0; $j < count($at_arr); $j++) {
                            if ($at_arr[$j]->userCanView()) {
                    echo '<br><i>-&nbsp;
                    <a href="/tracker/?atid='. $at_arr[$j]->getID() .
                                    '&group_id='.$group_id.'&func=browse">' .
                                    $at_arr[$j]->getName() .'</a></i>';
                            }
                }
            }
        }
        
        
        // ######################## AnonFTP (only for Active)
        
        if ($project->isActive()) {
            print '<HR SIZE="1" width="99%" NoShade>';
        
                list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
            print "<A href=\"ftp://" . $project->getUnixName() . "." . $host ."/pub/". $project->getUnixName(false) ."/\">";    // keep the first occurence in lower case
            print html_image("ic/ftp16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$GLOBALS['Language']->getText('include_project_home','anon_ftp_space')));
            print $GLOBALS['Language']->getText('include_project_home','anon_ftp_space').'</A>';
        }
        
        // ######################## Plugins
        
        $areas = array();
        $params = array('project' => &$project, 'areas' => &$areas);
        
        $em =& EventManager::instance();
        $em->processEvent('service_public_areas', $params);
        
        foreach($areas as $area) {
            print '<HR SIZE="1" width="99%" NoShade>';
            print $area;
        }
    }
}
?>
