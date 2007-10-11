<?php
  //
  // SourceForge: Breaking Down the Barriers to Open Source Development
  // Copyright 1999-2000 (c) The SourceForge Crew
  // http://sourceforge.net
  //
  // 

$Language->loadLanguageMsg('svn/svn');

if (!isset($group_id) || !$group_id) {
    exit_no_group(); // need a group_id !!!
 }

    svn_header(array ('title'=>$Language->getText('svn_browse_revision','browsing'),
                      'help' => 'SubversionBrowsingInterface.html'));
    if (!isset($offset) || !$offset || $offset < 0) {
        $offset=0;
    }
    
    if (!isset($chunksz) || !$chunksz) { $chunksz = 15; }
    
    if (!isset($msort) || ($msort != 0) && ($msort != 1)) { $msort = 0; }
    if (!$msort) { $msort = 0; }
    if (user_isloggedin() && !isset($morder)) {
        $morder = user_get_preference('svn_commit_browse_order'.$group_id);
    }

    if (isset($order)) {

        if ($order != '') {
            // Add the criteria to the list of existing ones
            $morder = svn_utils_add_sort_criteria($morder, $order, $msort);
        } else {
            // reset list of sort criteria
            $morder = '';
        }
    }

    if (isset($morder)) {

        if (user_isloggedin()) {
            if ($morder != user_get_preference('svn_commit_browse_order'.$group_id))
                user_set_preference('svn_commit_browse_order'.$group_id, $morder);
        }

        if ($morder != '') {
            $order_by = ' ORDER BY '.svn_utils_criteria_list_to_query($morder);
        }
    }


    //
    // Memorize order by field as a user preference if explicitly specified.
    // Automatically discard invalid field names.
    //
    if (isset($order) && $order) {
        if ($order=='id' || $order=='description' || $order=='date' || $order=='submitted_by') {
            if(user_isloggedin() &&
               ($order != user_get_preference('commits_browse_order')) ) {
                user_set_preference('commits_browse_order', $order);
            }
        } else {
            $order = false;
        }
    } else {
        if(user_isloggedin()) {
            $order = user_get_preference('commits_browse_order');
        }
    }


    if (!isset($set) || !$set) {
        /*
         if no set is passed in, see if a preference was set
         if no preference or not logged in, use my set
        */
        if (user_isloggedin()) {
            $custom_pref=user_get_preference('svn_commits_browcust'.$group_id);
            if ($custom_pref) {
                $pref_arr=explode('|',$custom_pref);
                $_rev_id=$pref_arr[0];
                $_commiter=$pref_arr[1];
                $_path=$pref_arr[2];
                $_srch=$pref_arr[3];
                $chunksz=$pref_arr[4];
                $set='custom';
            } else {
                $set='custom';
                $_commiter=0;
            }
        } else {
            $_commiter=0;
            $set='custom';
        }
    }

    if ($set=='my') {
        $_commiter=user_getname();
    } else if ($set=='custom') {
        /*
         if this custom set is different than the stored one, reset preference
        */
        $pref_=$_rev_id.'|'.$_commiter.'|'.$_path.'|'.$_srch.'|'.$chunksz;
        if ($pref_ != user_get_preference('svn_commits_browcust'.$group_id)) {
            //echo 'setting pref';
            user_set_preference('svn_commits_browcust'.$group_id,$pref_);
        }
    } else if ($set=='any') {
        $_commiter=100;
    } 

    /*
     Display commits based on the form post - by user or status or both
    */
    $_path     = isset($_path) ? $_path : '';
    $_rev_id   = isset($_rev_id) ? $_rev_id : ''; 
    $_commiter = isset($_commiter) ? $_commiter : ''; 
    $_srch     = isset($_srch) ? $_srch : '';
    $order_by  = isset($order_by) ? $order_by : ''; 
    $pv        = isset($pv) ? $pv : 0;
    $project = group_get_object($group_id); 
    $root = $project->getUnixName(false);

    list($result, $totalrows) = svn_get_revisions($project, $offset, $chunksz, $_rev_id, $_commiter, $_srch, $order_by, $pv);
    $statement=$Language->getText('svn_browse_revision','view_commit');

    /*
     creating a custom technician box which includes "any"
    */

    $tech_box=svn_utils_technician_box($root, '_commiter', $_commiter, 'Any');



    /*
     Show the new pop-up boxes to select assigned to and/or status
    */
    echo '<H3>'.$Language->getText('svn_browse_revision','browse_commit').'</H3>'; 
    echo '<FORM name="commit_form" ACTION="'. $PHP_SELF .'" METHOD="GET">
        <TABLE BORDER="0">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="browse">
	<INPUT TYPE="HIDDEN" NAME="set" VALUE="custom">
        <TR align="center"><TD><b>'.$Language->getText('svn_browse_revision','rev').'</b></TD><TD><b>'.$Language->getText('svn_browse_revision','commiter').'</b></TD><TD><b>'.$Language->getText('svn_browse_revision','path').'</b></TD><TD><b>'.$Language->getText('svn_browse_revision','search').'</b></TD>'.
        '</TR>'.
        '<TR><TD><INPUT TYPE="TEXT" SIZE=5 NAME=_rev_id VALUE='.(isset($_rev_id)?$_rev_id:'').'></TD>'.
        '<TD><FONT SIZE="-1">'. $tech_box .'</TD>'.
        '<TD><FONT SIZE="-1">'. '<INPUT type=text size=35 name=_path value='.(isset($_path)?$_path:'').'></TD>'.
        '<TD><FONT SIZE="-1">'. '<INPUT type=text size=35 name=_srch value='.(isset($_srch)?$_srch:'').'></TD>'.
        '</TR></TABLE>'.
	
        '<br><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_browse').'">'.
        ' <input TYPE="text" name="chunksz" size="3" MAXLENGTH="5" '.
        'VALUE="'.$chunksz.'">'.$Language->getText('svn_browse_revision','commit_at_once').
        '</FORM>';


    if ($result && db_numrows($result) > 0) {

        //create a new $set string to be used for next/prev button
        if ($set=='custom') {
            $set .= '&_commiter='.$_commiter.'&_srch='.$_srch.'&_path='.$_path.'&chunksz='.$chunksz;
        } else if ($set=='any') {
            $set .= '&_commiter=0&chunksz='.$chunksz;
        }

        svn_utils_show_revision_list($result,$offset,$totalrows,$set,$_commiter,$_path,$chunksz,$morder,$msort);

    } else {
        echo '
		<P>
		<H3>'.$statement.'</H3>
		<P>
		<P>';
        echo '
		<H1>'.$Language->getText('svn_browse_revision','no_match').'</H1>';
        echo db_error();
    }
    svn_footer(array());

?>
