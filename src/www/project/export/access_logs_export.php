<?php

/* 
* Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
*
* Originally written by Mohamed CHAARI, 2007. STMicroelectronics.
*
* This file is a part of CodeX.
*
* CodeX is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* CodeX is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with CodeX; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require_once('www/project/stats/source_code_access_utils.php');
require_once('www/project/export/project_export_utils.php');

$Language->loadLanguageMsg('project/project');

// Export files access logs for this group
function export_file_logs($project, $span, $who) {

    $eol = "\n";
    
    $sql_file = filedownload_logs_extract($project,$span,$who);
    $col_list_file = array('time','user','email','title','local_time');
    $file_title = array ('time'      => 'Files',
			'user'       => '',
			'email'      => '',
			'title'      => '',
			'local_time' => '');    
    $lbl_list_file = array( 'time'      => $GLOBALS['Language']->getText('project_export_access_logs_export','time'),
			   'user'       => $GLOBALS['Language']->getText('project_export_access_logs_export','user'),
			   'email'      => $GLOBALS['Language']->getText('project_export_access_logs_export','email'),
			   'title'      => $GLOBALS['Language']->getText('project_export_access_logs_export','file'),
			   'local_time' => $GLOBALS['Language']->getText('project_export_access_logs_export','local_time'));
    $result_file=db_query($sql_file);
    $rows_file = db_numrows($result_file);
    if ($result_file && $rows_file > 0) {
        // Build csv for files access logs
	echo build_csv_header($col_list_file, $file_title).$eol;
	echo build_csv_header($col_list_file, $lbl_list_file).$eol;
	while ($arr_file = db_fetch_array($result_file)) {    
	    prepare_access_logs_record($project->getGroupId(),$arr_file);
	    echo build_csv_record($col_list_file, $arr_file).$eol;
	}
	echo build_csv_header($col_list_file, array()).$eol;
    }	
    
}

// Export cvs access logs for this group
function export_cvs_logs($project, $span, $who) {

    $eol = "\n";
    
    $sql_cvs = cvsaccess_logs_extract($project,$span,$who);			
    $col_list_cvs = array('day','user','email','cvs_checkouts','cvs_browse');
    $cvs_title = array ('day'            => 'CVS',
			'user'           => '',
			'email'          => '',
			'cvs_checkouts'  => '',
			'cvs_browse'     => '');
    $lbl_list_cvs = array( 'day'            => $GLOBALS['Language']->getText('project_export_access_logs_export','time'),
			   'user'           => $GLOBALS['Language']->getText('project_export_access_logs_export','user'),
			   'email'          => $GLOBALS['Language']->getText('project_export_access_logs_export','email'),
			   'cvs_checkouts'  => $GLOBALS['Language']->getText('project_export_access_logs_export','chk_upd'),
			   'cvs_browse'     => $GLOBALS['Language']->getText('project_export_access_logs_export','file_brows'));
    $result_cvs=db_query($sql_cvs);
    $rows_cvs = db_numrows($result_cvs);
    	
    if ($result_cvs && $rows_cvs > 0) {
        // Build csv for cvs access logs
	echo build_csv_header($col_list_cvs, $cvs_title).$eol;
	echo build_csv_header($col_list_cvs, $lbl_list_cvs).$eol;
	while ($arr_cvs = db_fetch_array($result_cvs)) {    
	    prepare_access_logs_record($project->getGroupId(),$arr_cvs);
	    echo build_csv_record($col_list_cvs, $arr_cvs).$eol;
	}
	echo build_csv_header($col_list_cvs, array()).$eol;
    }

}

// Export svn access logs for this group
function export_svn_logs($project,$span,$who) {

    $eol = "\n";
    
    $sql_svn = svnaccess_logs_extract($project,$span,$who);
    $col_list_svn = array('day','user','email','svn_access_count','svn_browse');
    $svn_title = array ('day'              => 'Subversion',
			'user'             => '',
			'email'            => '',
			'svn_access_count' => '',
			'svn_browse'       => '');
    $lbl_list_svn = array( 'day'              => $GLOBALS['Language']->getText('project_export_access_logs_export','time'),
			   'user'             => $GLOBALS['Language']->getText('project_export_access_logs_export','user'),
			   'email'            => $GLOBALS['Language']->getText('project_export_access_logs_export','email'),
			   'svn_access_count' => $GLOBALS['Language']->getText('project_export_access_logs_export','access'),
			   'svn_browse'       => $GLOBALS['Language']->getText('project_export_access_logs_export','file_brows'));
    $result_svn=db_query($sql_svn);
    $rows_svn = db_numrows($result_svn);
    
    if ($result_svn && $rows_svn > 0) {
	// Build csv for subversion access logs
	echo build_csv_header($col_list_svn, $svn_title).$eol;
	echo build_csv_header($col_list_svn, $lbl_list_svn).$eol;
	while ($arr_svn = db_fetch_array($result_svn)) { 
	    prepare_access_logs_record($project->getGroupId(),$arr_svn);
	    echo build_csv_record($col_list_svn, $arr_svn).$eol;
	}
	echo build_csv_header($col_list_svn, array()).$eol;
    }
    
}

// Export docs access logs for this group
function export_doc_logs($project, $span, $who) {

    $eol = "\n";
    
    $sql_doc = 	doc_logs_extract($project, $span, $who);		
    $col_list_doc = array('time','user','email','title','local_time');
    $docs_title = array ('time'      => 'Docs',
			'user'       => '',
			'email'      => '',
			'title'      => '',
			'local_time' => '');
    $lbl_list_doc = array( 'time'       => $GLOBALS['Language']->getText('project_export_access_logs_export','time'),
			   'user'       => $GLOBALS['Language']->getText('project_export_access_logs_export','user'),
			   'email'      => $GLOBALS['Language']->getText('project_export_access_logs_export','email'),
			   'title'      => $GLOBALS['Language']->getText('project_export_access_logs_export','doc'),
			   'local_time' => $GLOBALS['Language']->getText('project_export_access_logs_export','local_time'));
    $result_doc=db_query($sql_doc);
    $rows_doc = db_numrows($result_doc);
    
    if ($result_doc && $rows_doc > 0) {
	// Build csv for docs access logs
	echo build_csv_header($col_list_doc, $docs_title).$eol;
	echo build_csv_header($col_list_doc, $lbl_list_doc).$eol;
	while ($arr_doc = db_fetch_array($result_doc)) {    
	    prepare_access_logs_record($project->getGroupId(),$arr_doc);
	    echo build_csv_record($col_list_doc, $arr_doc).$eol;
	}
	echo build_csv_header($col_list_doc, array()).$eol;
    }
}

// Export wiki pages access logs for this group
function export_wiki_pg_logs($project, $span, $who, $sf) {

    $eol = "\n";

    $sql_wiki_pg = wiki_logs_extract($project, $span, $who);			
    $col_list_wiki_pg = array('time','user','email','title','local_time');
    $wiki_pg_title = array ('time'       => 'Wiki Pages',
			    'user'       => '',
			    'email'      => '',
			    'title'      => '',
			    'local_time' => '');
    $lbl_list_wiki_pg = array( 'time'        => $GLOBALS['Language']->getText('project_export_access_logs_export','time'),
				'user'       => $GLOBALS['Language']->getText('project_export_access_logs_export','user'),
				'email'      => $GLOBALS['Language']->getText('project_export_access_logs_export','email'),
				'title'      => $GLOBALS['Language']->getText('project_export_access_logs_export','page'),
				'local_time' => $GLOBALS['Language']->getText('project_export_access_logs_export','local_time'));
    $result_wiki_pg=db_query($sql_wiki_pg);
    $rows_wiki_pg = db_numrows($result_wiki_pg);
    
    if (!$sf) {
        if ($result_wiki_pg && $rows_wiki_pg > 0) {
	    // Build csv for wiki pages access logs
	    echo build_csv_header($col_list_wiki_pg, $wiki_pg_title).$eol;
	    echo build_csv_header($col_list_wiki_pg, $lbl_list_wiki_pg).$eol;
	    while ($arr_wiki_pg = db_fetch_array($result_wiki_pg)) {    
	        prepare_access_logs_record($project->getGroupId(),$arr_wiki_pg);
	        echo build_csv_record($col_list_wiki_pg, $arr_wiki_pg).$eol;
	    }
	    echo build_csv_header($col_list_wiki_pg, array()).$eol;
        }
    } else {
        //to be used in 'Show Format' link
        $dsc_list = array( 'time'       => $GLOBALS['Language']->getText('project_export_access_logs_export','date_desc'),
		           'user'       => $GLOBALS['Language']->getText('project_export_access_logs_export','user_desc'),
		           'email'      => $GLOBALS['Language']->getText('project_export_access_logs_export','email_desc'),
		           'title'      => $GLOBALS['Language']->getText('project_export_access_logs_export','page_desc'),
		           'local_time' => $GLOBALS['Language']->getText('project_export_access_logs_export','local_time_desc'));	
	$record = pick_a_record_at_random($result_wiki_pg, $rows_wiki_pg, $col_list_wiki_pg);
        prepare_access_logs_record($project->getGroupId(),$record);   
        display_exported_fields($col_list_wiki_pg,$lbl_list_wiki_pg,$dsc_list,$record);
    }
}

// Export wiki pages attachments access logs for this group
function export_wiki_att_logs($project, $span, $who) {

    $eol = "\n";

    $sql_wiki_att = wiki_attachments_logs_extract($project, $span, $who);			
    $col_list_wiki_att = array('time','user','email','title','local_time');
    $wiki_att_title = array ('time'       => 'Wiki Attachments',
			     'user'       => '',
			     'email'      => '',
			     'title'      => '',
			     'local_time' => '');
    $lbl_list_wiki_att = array( 'time'       => $GLOBALS['Language']->getText('project_export_access_logs_export','time'),
				'user'       => $GLOBALS['Language']->getText('project_export_access_logs_export','user'),
				'email'      => $GLOBALS['Language']->getText('project_export_access_logs_export','email'),
				'title'      => $GLOBALS['Language']->getText('project_export_access_logs_export','attachment'),
				'local_time' => $GLOBALS['Language']->getText('project_export_access_logs_export','local_time'));
    $result_wiki_att=db_query($sql_wiki_att);
    $rows_wiki_att = db_numrows($result_wiki_att);

    if ($result_wiki_att && $rows_wiki_att > 0) {
        // Build csv for wiki attachments access logs
	echo build_csv_header($col_list_wiki_att, $wiki_att_title).$eol;
	echo build_csv_header($col_list_wiki_att, $lbl_list_wiki_att).$eol;
	while ($arr_wiki_att = db_fetch_array($result_wiki_att)) {    
	    prepare_access_logs_record($project->getGroupId(),$arr_wiki_att);
	    echo build_csv_record($col_list_wiki_att, $arr_wiki_att).$eol;
	}
	    echo build_csv_header($col_list_wiki_att, array()).$eol;
    }
}

// Export documents access logs for this group
function export_document_logs($project, $span, $who) {

    $eol = "\n";
    
    $logs = plugins_log_extract($project, $span, $who);
    $sql_document = $logs[0]['sql'];
    $col_list_document = array('time','user','email','title','local_time');
    $documents_title = array ('time'         => 'Documents',
				'user'       => '',
				'email'      => '',
				'title'      => '',
				'local_time' => '');
    $lbl_list_document = array( 'time'       => $GLOBALS['Language']->getText('project_export_access_logs_export','time'),
				'user'       => $GLOBALS['Language']->getText('project_export_access_logs_export','user'),
				'email'      => $GLOBALS['Language']->getText('project_export_access_logs_export','email'),
				'title'      => $GLOBALS['Language']->getText('project_export_access_logs_export','document'),
				'local_time' => $GLOBALS['Language']->getText('project_export_access_logs_export','local_time'));
    $result_document=db_query($sql_document);
    $rows_document = db_numrows($result_document);

    if ($result_document && $rows_document > 0) {
        // Build csv for wiki attachments access logs	    	    
	echo build_csv_header($col_list_document, $documents_title).$eol;
	echo build_csv_header($col_list_document, $lbl_list_document).$eol;
	while ($arr_document = db_fetch_array($result_document)) {    
	    prepare_access_logs_record($project->getGroupId(),$arr_document);
	    echo build_csv_record($col_list_document, $arr_document).$eol;
	}
	echo build_csv_header($col_list_document, array()).$eol;
    }
}


$project = new Project($group_id);
    
if ($export == 'access_logs') {

    $span = 52*30.5;
    $who = "allusers";
    
    // Send the result in CSV format	
    header ('Content-Type: text/csv');
    header ('Content-Disposition: filename=access_logs.csv');
    
    export_file_logs($project, $span, $who);
    export_cvs_logs($project, $span, $who);	
    export_svn_logs($project, $span, $who);
    export_doc_logs($project, $span, $who);
    export_wiki_pg_logs($project, $span, $who,0);
    export_wiki_att_logs($project, $span, $who);
    export_document_logs($project, $span, $who);

} else if ($export == "access_logs_format") {
		       
    $span = 52*30.5;
    $who = "allusers";
    echo $GLOBALS['Language']->getText('project_export_bug_deps_export','bug_deps_export_format',array($GLOBALS['Language']->getText('project_admin_utils', 'access_logs')));
    export_wiki_pg_logs($project,$span,$who,1);
    
}


?>