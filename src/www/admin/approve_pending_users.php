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
$GLOBALS['HTML']->includeCalendarScripts();

define('ADMIN_APPROVE_PENDING_PAGE_PENDING', 'pending');
define('ADMIN_APPROVE_PENDING_PAGE_VALIDATED', 'validated');

session_require(array('group'=>'1','admin_flags'=>'A'));
$hp = Codendi_HTMLPurifier::instance();
$request =& HTTPRequest:: instance();
$action_select = '';
$status= '';
$users_array = array();
if ($request->exist('action_select')) {
    $action_select = $request->get('action_select');
}
if ($request->exist('status')) {
    $status = $request->get('status');
}
if ($request->exist('list_of_users')) {
    $users_array = array_filter(array_map('intval', explode(",", $request->get('list_of_users'))));
}

$valid_page = new Valid_WhiteList('page', array(ADMIN_APPROVE_PENDING_PAGE_PENDING, ADMIN_APPROVE_PENDING_PAGE_VALIDATED));
$page = $request->getValidated('page', $valid_page, '');

$expiry_date = 0;
    if ($request->exist('form_expiry') && $request->get('form_expiry')!='' && !ereg("[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}", $request->get('form_expiry'))) {
        $feedback .= ' '.$Language->getText('admin_approve_pending_users', 'data_not_parsed');
    }else{
        $vDate = new Valid_String();
        if ($request->exist('form_expiry') && $request->get('form_expiry')!='' && $vDate->validate($request->get('form_expiry'))) {
            $date_list = split("-", $request->get('form_expiry'), 3);
            $unix_expiry_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
            $expiry_date = $unix_expiry_time; 
            
        }
        
        if (($action_select=='activate')) {
            
        
            $shell="";
            if ($status=='restricted') {
                $newstatus='R';
                $shell=",shell='".$GLOBALS['codendi_bin_prefix'] ."/cvssh-restricted'";
            } else $newstatus='A';
        
            $users_ids = db_ei_implode($users_array);
            // update the user status flag to active
            db_query("UPDATE user SET expiry_date='".$expiry_date."', status='".$newstatus."'".$shell.
                     ", approved_by='".UserManager::instance()->getCurrentUser()->getId()."'".
                 " WHERE user_id IN ($users_ids)");

            $em =& EventManager::instance();
            foreach ($users_array as $user_id) {
                $em->processEvent('project_admin_activate_user', array('user_id' => $user_id));
            }
        
            // Now send the user verification emails
            $res_user = db_query("SELECT email, confirm_hash FROM user "
                     . " WHERE user_id IN ($users_ids)");
            
             // Send a notification message to the user when account is activated by the Site Administrator
             $base_url = get_server_url();
             
             while ($row_user = db_fetch_array($res_user)) {
                $from = $GLOBALS['sys_noreply'];
                    $to = $row_user['email'];
                    $subject = $Language->getText('admin_approve_pending_users', 'email_title', array($GLOBALS['sys_name']));
                    
                    include($Language->getContent('admin/new_account_email'));
        
                    $mail = new Mail();
                    $mail->setSubject($subject);
                    $mail->setFrom($from);
                    $mail->setTo($to,true);
                    $mail->setBody($body);
                    if (!$mail->send()) {
                        $GLOBALS['feedback'] .= "<p>".$row_user['email']." - ".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']))."</p>";
                    }
                usleep(250000);
            }
        
        
        } else if($action_select=='validate'){
            if($status=='restricted'){
                $newstatus='W';
            }else{
                $newstatus='V';
            }
            
        
            // update the user status flag to active
            db_query("UPDATE user SET expiry_date='".$expiry_date."', status='".$newstatus."'".
                     ", approved_by='".UserManager::instance()->getCurrentUser()->getId()."'".
                     " WHERE user_id IN (".implode(',', $users_array).")");
        
            // Now send the user verification emails
            $res_user = db_query("SELECT email, confirm_hash, user_name FROM user "
                     . " WHERE user_id IN (".implode(',', $users_array).")");
            
            while ($row_user = db_fetch_array($res_user)) {
                if (!send_new_user_email($row_user['email'], $row_user['user_name'], '', $row_user['confirm_hash'], 'mail', false)) {
                        $GLOBALS['feedback'] .= "<p>".$row_user['email']." - ".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']))."</p>";
                }
                usleep(250000);
            }
            
        } else if ($action_select=='delete') {
            db_query("UPDATE user SET status='D', approved_by='".UserManager::instance()->getCurrentUser()->getId()."'".
                     " WHERE user_id IN (".implode(',', $users_array).")");
            $em =& EventManager::instance();
            foreach ($users_array as $user_id) {
                $em->processEvent('project_admin_delete_user', array('user_id' => $user_id));
            }

        }
    }
//
// No action - First time in this script 
// Show the list of pending user waiting for approval
//
if ($page == ADMIN_APPROVE_PENDING_PAGE_PENDING){
    $res = db_query("SELECT * FROM user WHERE status='P'");
    $msg = $Language->getText('admin_approve_pending_users','no_pending_validated');
    if($GLOBALS['sys_user_approval'] == 0) {
        $res = db_query("SELECT * FROM user WHERE status='P' OR status='V' OR status='W'");
        $msg = $Language->getText('admin_approve_pending_users','no_pending');
    }
}else if($page == ADMIN_APPROVE_PENDING_PAGE_VALIDATED){
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
        <H2><?php echo  $hp->purify($row['realname'], CODENDI_PURIFIER_CONVERT_HTML) .' ('.$row['user_name'].')'; ?></H2>
    
        <p>
                                            <A href="/users/<?php echo $row['user_name']; ?>"><H3>[<?php echo $Language->getText('admin_approve_pending_users','user_info'); ?>]</H3></A>
    
        <p>
        <A href="/admin/usergroup.php?user_id=<?php echo $row['user_id']; ?>"><H3>[<?php echo $Language->getText('admin_approve_pending_users','user_edit'); ?>]</H3></A>
    
        <p>
            <TABLE WIDTH="70%">
            <TR>
        <?php 
        if($GLOBALS['sys_user_approval'] == 1 && $page==ADMIN_APPROVE_PENDING_PAGE_PENDING && ! ForgeConfig::areRestrictedUsersAllowed()) {
            
            // Can select Activate/validate
            echo '<TD>
            <FORM name="pending_user'.$row['user_id'].'" action="?page='.$page.'" method="POST">';
            echo $Language->getText('admin_approve_pending_users', 'expiry_date').'<BR>'; 
            echo $GLOBALS['HTML']->getDatePicker("form_expiry", "form_expiry", "");
            ?>
            <BR>
             <?php echo $Language->getText('admin_approve_pending_users', 'expiry_date_directions').
                '<p><select name="action_select" size="1">
                <option value="validate" selected>'.$Language->getText('admin_approve_pending_users','validate').'
                <option value="activate">'.$Language->getText('admin_approve_pending_users','activate').'
                <option value="delete">'.$Language->getText('admin_approve_pending_users','delete').'        
                </select>
            '.$Language->getText('admin_approve_pending_users','account').'          
            <INPUT TYPE="HIDDEN" NAME="list_of_users" VALUE="'.$row['user_id'].'">
            <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending_users','ok').'">            
            </FORM>';
            
            if($row['status']=='V' ||$row['status']=='W') {
                echo '<p><FORM name="resend" action="/account/pending-resend.php?user_name='.$row['user_name'].'" method="POST">'
                    .$Language->getText('admin_approve_pending_users','resend_notice').' <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending_users','resend').'">            
                    </FORM>
                    </p>';
             }
            echo '</TD>';
            
        } else if($GLOBALS['sys_user_approval'] == 1 && $page==ADMIN_APPROVE_PENDING_PAGE_PENDING && ForgeConfig::areRestrictedUsersAllowed()) {
             
           // Can select Std/Restricted and Activate/validate
           echo '<TD>
            <FORM name="pending_user'.$row['user_id'].'" action="?page='.$page.'" method="POST">';
            echo $Language->getText('admin_approve_pending_users', 'expiry_date').'<BR>'; 
            echo $GLOBALS['HTML']->getDatePicker("form_expiry", "form_expiry", "");
            ?>
            <BR>
             <?php echo $Language->getText('admin_approve_pending_users', 'expiry_date_directions').
                '<p><select name="action_select" size="1">
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
            <INPUT TYPE="HIDDEN" NAME="list_of_users" VALUE="'.$row['user_id'].'">
            <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending_users','ok').'">            
            </FORM>';
             if($row['status']=='V' ||$row['status']=='W') {
                 echo '<p><FORM name="resend" action="/account/pending-resend.php?user_name='.$row['user_name'].'" method="POST">'
                    .$Language->getText('admin_approve_pending_users','resend_notice').' <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending_users','resend').'">            
                    </FORM>
                    </p>';               
             }
            echo '</TD>';   
            
        } else {
           // Can select Std/Restricted but only Activate
           // We don't take into account the fact that we may have sys_user_approval=0 and Config::areRestrictedUsersAllowed()
           // which is not coherent (users may activate their account as standard themselves).
            echo '<TD>
            <FORM name="pending_user'.$row['user_id'].'" action="?page='.$page.'" method="POST">';
            $exp_date='';
            if($row['expiry_date'] != 0){
                $exp_date = format_date('Y-m-d',$row['expiry_date']); 
            }
            echo $Language->getText('admin_approve_pending_users', 'expiry_date').'<BR>'; 
            echo $GLOBALS['HTML']->getDatePicker("form_expiry", "form_expiry", $exp_date);
            ?>
            <BR>
             <?php echo $Language->getText('admin_approve_pending_users', 'expiry_date_directions').
                '<p><select name="action_select" size="1">
                <option value="activate" selected>'.$Language->getText('admin_approve_pending_users','activate').'
                <option value="delete">'.$Language->getText('admin_approve_pending_users','delete').'        
                </select>
            '.$Language->getText('admin_approve_pending_users','account');
            if(ForgeConfig::areRestrictedUsersAllowed()) {
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
            echo '<INPUT TYPE="HIDDEN" NAME="list_of_users" VALUE="'.$row['user_id'].'">
            <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending_users','ok').'">            
            </FORM>';
            if ($GLOBALS['sys_user_approval'] == 0 || ($row['status']=='V' ||$row['status']=='W')) {
                echo '<p><FORM name="resend" action="/account/pending-resend.php?user_name='.$row['user_name'].'" method="POST">'
                    .$Language->getText('admin_approve_pending_users','resend_notice').' <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending_users','resend').'">            
                    </FORM>
                    </p>';
            }
            
            echo '</TD>';

        }
        ?>
        
            </TR>
            </TABLE>
        <P>
        <B><?php echo $Language->getText('admin_approve_pending_users','purpose'); ?>:</B><br> <?php echo  $hp->purify($row['register_purpose'], CODENDI_PURIFIER_CONVERT_HTML) ; ?>
    
        <br>
        &nbsp;
        <?php
    
        // ########################## OTHER INFO
    
        print "<P><B>".$Language->getText('admin_approve_pending_users','other_info')."</B>";
        print "<br>&nbsp;&nbsp;".$Language->getText('admin_approve_pending_users','name').": $row[user_name]";
    
        print "<br>&nbsp;&nbsp;".$Language->getText('admin_approve_pending_users','id').":  $row[user_id]";
    
        print "<br>&nbsp;&nbsp;".$Language->getText('admin_approve_pending_users','email').":  <a href=\"mailto:$row[email]\">$row[email]</a>";
        print "<br>&nbsp;&nbsp;".$Language->getText('admin_approve_pending_users','reg_date').":  ".format_date($GLOBALS['Language']->getText('system', 'datefmt'),$row['add_date']);
        echo "<P><HR><P>";
    
    }
    
    //list of user_id's of pending users
    $arr=result_column_to_array($res,0);
    $user_list=implode(',',$arr);
    
      echo '
        <CENTER>
            <TABLE WIDTH="70%">
            <TR>';
            
            
        if($GLOBALS['sys_user_approval'] != 1 || $page!=ADMIN_APPROVE_PENDING_PAGE_PENDING){
            echo '<TD>
            <FORM action="?page='.$page.'" method="POST">
            '.$Language->getText('admin_approve_pending_users','activate').'
            '.$Language->getText('admin_approve_pending_users','all_accounts').' ';
            if(ForgeConfig::areRestrictedUsersAllowed()) {
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

    if (ForgeConfig::areRestrictedUsersAllowed() && $page==ADMIN_APPROVE_PENDING_PAGE_PENDING) {

        echo '<TD>
            <FORM action="?page='.$page.'" method="POST">
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
            <INPUT TYPE="HIDDEN" NAME="list_of_users" VALUE="'.$user_list.'">
            <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending_users','ok').'">            
            </FORM>
            </TD>';        
    }
 
        if($GLOBALS['sys_user_approval'] == 1 && $page==ADMIN_APPROVE_PENDING_PAGE_PENDING && ! ForgeConfig::areRestrictedUsersAllowed()){
            echo '<TD>
            <FORM action="?page='.$page.'" method="POST">
                <select name="action_select" size="1">
                <option value="validate" selected>'.$Language->getText('admin_approve_pending_users','validate').'
                <option value="activate">'.$Language->getText('admin_approve_pending_users','activate').'
                </select>
            '.$Language->getText('admin_approve_pending_users','all_accounts').'          
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
