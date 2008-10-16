<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require('../news/news_utils.php');

$request =& HTTPRequest::instance();

$validGroupId = new Valid_GroupId();
$validGroupId->required();
if($request->valid($validGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

if (user_isloggedin()) {

    if (user_ismember($group_id,'A') || user_ismember($group_id,'N1') || user_ismember($group_id,'N2')) {
        
        if ($request->get('post_changes')) {
            
            $validSummary = new Valid_String('summary');
            $validSummary->setErrorMessage('Summary is required');
            $validSummary->required();
            
            $validDetails = new Valid_Text('details');
            
            $validPrivateNews = new Valid_WhiteList('private_news', array('0', '1'));
            $validSummary->required();
            
            $validPromoteNews = new Valid_WhiteList('promote_news', array('0', '3'));
            $validSummary->required();
            
            if ($request->valid($validSummary)
                && $request->valid($validDetails)
                && $request->valid($validPrivateNews)
                && $request->valid($validPromoteNews)
                ) 
            {
                
                /*
                 Insert the row into the db if it's a generic message
                 OR this person is an admin for the group involved
                */
                /*
                 create a new discussion forum without a default msg
                 if one isn't already there
                */
                
                //if news is declared as private, force the $promote_news to '0' value (not to be promoted)
                $promote_news = $request->get('promote_news');
                if ($promote_news == '3' && $request->get('private_news')) {
                    $promote_news = "0";
                }
                
                news_submit($group_id, $request->get('summary'), $request->get('details'), $request->get('private_news'), $promote_news);
            }
        }
        
        /*
             Show the submit form
        */
        news_header(array('title'=>$Language->getText('news_index','news'),
              'help'=>'NewsService.html'));

        /*
         create a new discussion forum without a default msg
         if one isn't already there
        */
        echo '
        <H3>'.$Language->getText('news_submit','submit_news_for',group_getname($group_id)).'</H3>
        <P>
        '.$Language->getText('news_submit','post_explain',$GLOBALS['sys_name']).'
        <P>
        <FORM ACTION="" METHOD="POST">
        <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
        <B>'.$Language->getText('news_submit','for_project',group_getname($group_id)) .'</B>
        <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="1">
        <P>
        <B>'.$Language->getText('news_admin_index','subject').':</B><BR>
        <INPUT TYPE="TEXT" NAME="summary" VALUE="" CLASS="textfield_medium">
        <P>
        <B>'.$Language->getText('news_admin_index','details').':</B><BR>
        <TEXTAREA NAME="details" ROWS="8" COLS="50" WRAP="SOFT"></TEXTAREA>
        <P><TABLE BORDER=0>
        <TR><TD><B>'.$Language->getText('news_submit','news_privacy').':</B></TD>
        <TD><INPUT TYPE="RADIO" NAME="private_news" VALUE="0" CHECKED>'. $Language->getText('news_submit','public_news').'</TD></TR> 
        <TR><TD></TD><TD><INPUT TYPE="RADIO" NAME="private_news" VALUE="1">'. $Language->getText('news_submit','private_news').'</TD></TR> 
        </TABLE><P>
        <TABLE BORDER=0>
        <TR><TD><B>'.$Language->getText('news_submit','news_promote',$GLOBALS['sys_name']).' : </B></TD>
        <TD><INPUT TYPE="RADIO" NAME="promote_news" VALUE="3">'.$Language->getText('global','yes').'
        <INPUT TYPE="RADIO" NAME="promote_news" VALUE="0" CHECKED>'.$Language->getText('global','no').'</TD>
        </TR></TABLE>'. $Language->getText('news_submit','promote_warn',$GLOBALS['sys_name']) .'<p>
        <INPUT TYPE="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
        </FORM>';

        news_footer(array());

    } else {
        exit_error($Language->getText('news_admin_index','permission_denied'),$Language->getText('news_submit','only_writer_submits'));
    }
} else {
    exit_not_logged_in();
}
?>
