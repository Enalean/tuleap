<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('account.php');
require_once('proj_email.php');
require_once('www/admin/admin_utils.php');

$Language->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flags'=>'A'));

$action = '';
$action_select = '';
$status= '';
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}
if (isset($_REQUEST['action_select'])) {
    $action_select = $_REQUEST['action_select'];
}
if (isset($_REQUEST['status'])) {
    $status = $_REQUEST['status'];
}
$page = '';
if (isset($_REQUEST['page'])) {
    $page = $_REQUEST['page'];
}

if (($action_select=='activate')) {

    $shell="";
    if ($action=='restricted_pending' && $status=='restricted') {
        $newstatus='R';
        $shell=",shell='".$GLOBALS['codex_bin_prefix'] ."/cvssh-restricted'";
    } else $newstatus='A';

    // update the user status flag to active
    db_query("UPDATE user SET status='".$newstatus."'".$shell.
	     " WHERE user_id IN ($list_of_users)");

    /*// Now send the user verification emails
    $res_user = db_query("SELECT email, confirm_hash FROM user "
			 . " WHERE user_id IN ($list_of_users)");
	
    while ($row_user = db_fetch_array($res_user)) {
        if (!send_new_user_email($row_user['email'],$row_user['confirm_hash'])) {
                $GLOBALS['feedback'] .= "<p>".$row_user['email']." - ".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']))."</p>";
        }
        usleep(250000);
    }*/

} else if($action_select=='validate'){
    if($status=='restricted'){
        $newstatus='W';
    }else{
        $newstatus='V';
    }
    

    // update the user status flag to active
    db_query("UPDATE user SET status='".$newstatus."' 
          WHERE user_id IN ($list_of_users)");

    // Now send the user verification emails
    $res_user = db_query("SELECT email, confirm_hash FROM user "
             . " WHERE user_id IN ($list_of_users)");
    
    while ($row_user = db_fetch_array($res_user)) {
        if (!send_new_user_email($row_user['email'],$row_user['confirm_hash'])) {
                $GLOBALS['feedback'] .= "<p>".$row_user['email']." - ".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']))."</p>";
        }
        usleep(250000);
    }
    
} else if ($action_select=='delete') {
    db_query("UPDATE user SET status='D' WHERE user_id IN ($list_of_users)");
}

//
// No action - First time in this script 
// Show the list of pending user waiting for approval
//
if($page=='pending'){
    $res = db_query("SELECT * FROM user WHERE status='P'");
    $msg = $Language->getText('admin_approve_pending_users','no_pending_validated');
    if($GLOBALS['sys_user_approval'] == 0) {
        $res = db_query("SELECT * FROM user WHERE status='P' OR status='V' OR status='W'");
        $msg = $Language->getText('admin_approve_pending_users','no_pending');
    }
}else if($page=='validated'){
    $res = db_query("SELECT * FROM user WHERE status='V' OR status='W'");
    $msg = $Language->getText('admin_approve_pending_users','no_validated');
}

if (db_numrows($res) < 1) {
    site_admin_header(array('title'=>$msg));
    echo $msg;
} else {

    site_admin_header(array('title'=>$Language->getText('admin_approve_pending_users','title')));
    
    ?>
    <p><?php echo $Language->getText('admin_approve_pending_users','validate_notice'); ?>
    
    <p><?php echo $Language->getText('admin_approve_pending_users','activate_notice'); ?>
    <?php
    
    while ($row = db_fetch_array($res)) {
    
        ?>
        <H2><?php echo $row['realname'].' ('.$row['user_name'].')'; ?></H2>
    
        <p>
                                            <A href="/users/<?php echo $row['user_name']; ?>"><H3>[<?php echo $Language->getText('admin_approve_pending_users','user_info'); ?>]</H3></A>
    
        <p>
        <A href="/admin/usergroup.php?user_id=<?php echo $row['user_id']; ?>"><H3>[<?php echo $Language->getText('admin_approve_pending_users','user_edit'); ?>]</H3></A>
    
        <p>
            <TABLE WIDTH="70%">
            <TR>
        <?php 
        if($GLOBALS['sys_user_approval'] != 1 || $page!='pending'){
            echo '<TD>
            <FORM action="'.$PHP_SELF.'?page='.$page.'" method="POST">
                <select name="action_select" size="1">
                <option value="activate" selected>'.$Language->getText('admin_approve_pending_users','activate').'
                <option value="delete">'.$Language->getText('admin_approve_pending_users','delete').'        
                </select>
            '.$Language->getText('admin_approve_pending_users','account');
            if($GLOBALS['sys_allow_restricted_users']) {
                echo ' '.$Language->getText('admin_approve_pending_users','status').'
            <select name="status" size="1">
                <option value="standard" ';
                if($row['status']=='V') echo 'selected';
                echo '>'.$Language->getText('admin_approve_pending_users','status_standard').'
                <option value="restricted" ';
                if($row['status']=='W') echo 'selected';
                echo '>'.$Language->getText('admin_approve_pending_users','status_restricted').'        
            </select>';
            }     
            echo '<INPUT TYPE="HIDDEN" NAME="action" VALUE="activate_account">
            <INPUT TYPE="HIDDEN" NAME="list_of_users" VALUE="'.$row['user_id'].'">
            <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending_users','ok').'">            
            </FORM>
            </TD>';
        }
        ?>
        
    <?php
    if ($GLOBALS['sys_allow_restricted_users'] && $page=='pending') {

        echo '<TD>
            <FORM action="'.$PHP_SELF.'?page='.$page.'" method="POST">
                <select name="action_select" size="1">
                <option value="validate" selected>'.$Language->getText('admin_approve_pending_users','validate').'
                <option value="activate" >'.$Language->getText('admin_approve_pending_users','activate').'
                <option value="delete">'.$Language->getText('admin_approve_pending_users','delete').'        
                </select>
            '.$Language->getText('admin_approve_pending_users','account').' '.'          
            '.$Language->getText('admin_approve_pending_users','status').'
            <select name="status" size="1">
                <option value="standard">'.$Language->getText('admin_approve_pending_users','status_standard').'
                <option value="restricted">'.$Language->getText('admin_approve_pending_users','status_restricted').'        
            </select>
            <INPUT TYPE="HIDDEN" NAME="action" VALUE="restricted_pending">
            <INPUT TYPE="HIDDEN" NAME="list_of_users" VALUE="'.$row['user_id'].'">
            <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending_users','ok').'">            
            </FORM>
            </TD>';        
    }
    ?>
    <?php 
        if($GLOBALS['sys_user_approval'] == 1 && $page=='pending' && !$GLOBALS['sys_allow_restricted_users']){
            echo '<TD>
            <FORM action="'.$PHP_SELF.'?page='.$page.'" method="POST">
                <select name="action_select" size="1">
                <option value="validate" selected>'.$Language->getText('admin_approve_pending_users','validate').'
                <option value="activate">'.$Language->getText('admin_approve_pending_users','activate').'
                <option value="delete">'.$Language->getText('admin_approve_pending_users','delete').'        
                </select>
            '.$Language->getText('admin_approve_pending_users','account').'          
            <INPUT TYPE="HIDDEN" NAME="action" VALUE="user_approval_pending">
            <INPUT TYPE="HIDDEN" NAME="list_of_users" VALUE="'.$row['user_id'].'">
            <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending_users','ok').'">            
            </FORM>
            </TD>';

        }
        ?>

            </TR>
            </TABLE>
        <P>
        <B><?php echo $Language->getText('admin_approve_pending_users','purpose'); ?>:</B><br> <?php echo $row['register_purpose']; ?>
    
        <br>
        &nbsp;
        <?php
    
        // ########################## OTHER INFO
    
        print "<P><B>".$Language->getText('admin_approve_pending_users','other_info')."</B>";
        print "<br>&nbsp;&nbsp;".$Language->getText('admin_approve_pending_users','name').": $row[user_name]";
    
        print "<br>&nbsp;&nbsp;".$Language->getText('admin_approve_pending_users','id').":  $row[user_id]";
    
        print "<br>&nbsp;&nbsp;".$Language->getText('admin_approve_pending_users','email').":  <a href=\"mailto:$row[email]\">$row[email]</a>";
        print "<br>&nbsp;&nbsp;".$Language->getText('admin_approve_pending_users','reg_date').":  ".format_date($sys_datefmt,$row['add_date']);
        echo "<P><HR><P>";
    
    }
    
    //list of user_id's of pending users
    $arr=result_column_to_array($res,0);
    $user_list=implode($arr,',');
    
      echo '
        <CENTER>
            <TABLE WIDTH="70%">
            <TR>';
            
            
        if($GLOBALS['sys_user_approval'] != 1 || $page!='pending'){
            echo '<TD>
            <FORM action="'.$PHP_SELF.'?page='.$page.'" method="POST">
            '.$Language->getText('admin_approve_pending_users','activate').'
            '.$Language->getText('admin_approve_pending_users','all_accounts').' ';
            if($GLOBALS['sys_allow_restricted_users']) {
                echo $Language->getText('admin_approve_pending_users','status').'
            <select name="status" size="1">
                <option value="standard" selected>'.$Language->getText('admin_approve_pending_users','status_standard').'
                <option value="restricted" >'.$Language->getText('admin_approve_pending_users','status_restricted').'        
            </select>';
            }    
                     
            echo '<INPUT TYPE="HIDDEN" NAME="action_select" VALUE="activate">
            <INPUT TYPE="HIDDEN" NAME="list_of_users" VALUE="'.$user_list.'">
            <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending_users','ok').'">            
            </FORM>
            </TD>';
        }

    if ($GLOBALS['sys_allow_restricted_users'] && $page=='pending') {

        echo '<TD>
            <FORM action="'.$PHP_SELF.'?page='.$page.'" method="POST">
                <select name="action_select" size="1">
                <option value="validate" selected>'.$Language->getText('admin_approve_pending_users','validate').'
                <option value="activate">'.$Language->getText('admin_approve_pending_users','activate').'        
                </select>
            '.$Language->getText('admin_approve_pending_users','all_accounts').' '.'         
            '.$Language->getText('admin_approve_pending_users','status').'
            <select name="status" size="1">
                <option value="standard">'.$Language->getText('admin_approve_pending_users','status_standard').'
                <option value="restricted">'.$Language->getText('admin_approve_pending_users','status_restricted').'        
            </select>
            <INPUT TYPE="HIDDEN" NAME="action" VALUE="restricted_pending_all">
            <INPUT TYPE="HIDDEN" NAME="list_of_users" VALUE="'.$user_list.'">
            <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending_users','ok').'">            
            </FORM>
            </TD>';        
    }
 
        if($GLOBALS['sys_user_approval'] == 1 && $page=='pending' && !$GLOBALS['sys_allow_restricted_users']){
            echo '<TD>
            <FORM action="'.$PHP_SELF.'?page='.$page.'" method="POST">
                <select name="action_select" size="1">
                <option value="validate" selected>'.$Language->getText('admin_approve_pending_users','validate').'
                <option value="activate">'.$Language->getText('admin_approve_pending_users','activate').'
                </select>
            '.$Language->getText('admin_approve_pending_users','all_accounts').'          
            <INPUT TYPE="HIDDEN" NAME="action" VALUE="user_approval_pending_all">
            <INPUT TYPE="HIDDEN" NAME="list_of_users" VALUE="'.$user_list.'">
            <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending_users','ok').'">            
            </FORM>
            </TD>';

        }
        
    
    
    echo '
            </TR>
            </TABLE>
        </CENTER>
        ';
}
site_admin_footer(array());

?>
