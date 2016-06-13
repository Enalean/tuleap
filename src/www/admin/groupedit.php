<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('vars.php');
require_once('www/admin/admin_utils.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('www/project/export/project_export_utils.php');
require_once('www/project/admin/project_history.php');
require_once('common/include/TemplateSingleton.class.php');
require_once('common/event/EventManager.class.php');

session_require(array('group'=>'1','admin_flags'=>'A'));
$pm = ProjectManager::instance();
$group = $pm->getProject($group_id);
if (!$group || $group->isError()) {
    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_groupedit','error_group'));
    $GLOBALS['Response']->redirect('/admin');
}

$em = EventManager::instance();

if ($request->existAndNonEmpty('Rename')) {
    $new_name = $request->get('new_name');
    if (isset($new_name) && $group_id) {
        if (SystemEventManager::instance()->canRenameProject($group)) {
            $rule = new Rule_ProjectName();
            if (!$rule->isValid($new_name)) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_groupedit','invalid_short_name'));
                $GLOBALS['Response']->addFeedback('error', $rule->getErrorMessage());
            } else {
                $em->processEvent(Event::PROJECT_RENAME, array('group_id' => $group_id,
                                                           'new_name' => $new_name));
                //update group history
                group_add_history('rename_request', $group->getUnixName(false).' :: '.$new_name, $group_id);
                $GLOBALS['Response']->addFeedback('info', $Language->getText('admin_groupedit','rename_project_msg', array($group->getUnixName(false), $new_name )));
                $GLOBALS['Response']->addFeedback('warning', $Language->getText('admin_groupedit','rename_project_warn'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('warning', $Language->getText('admin_groupedit', 'rename_project_already_queued'), CODENDI_PURIFIER_DISABLED);
        }
    }
    $GLOBALS['Response']->redirect('/admin/groupedit.php?group_id='.$group_id);
}

if ($request->existAndNonEmpty('Update')) {

    $form_status = $group->getStatus();

    if ($group->getGroupId() != Project::ADMIN_PROJECT_ID) {
        $form_status  = $request->getValidated('form_status', 'string', $group->getStatus());
    }
    $form_public  = $request->getValidated('form_public', 'string', $group->isPublic());
    $group_type   = $request->getValidated('group_type', 'string', $group->getType());
    $form_domain  = $request->getValidated('form_domain', 'string', $group->getHTTPDomain());
    $form_box     = $request->getValidated('form_box', 'string', $group->getUnixBox());
    if ($group->getStatus() != $form_status && $group->getGroupId() != Project::ADMIN_PROJECT_ID) {
        group_add_history('status', $Language->getText('admin_groupedit', 'status_' . $group->getStatus()) . " :: " . $Language->getText('admin_groupedit', 'status_' . $form_status), $group_id);
    }
    if ($group->getType() != $group_type) {
        group_add_history('group_type', $group->getType(), $group_id);
    }
    if ($group->getHTTPDomain() != $form_domain) {
        group_add_history('http_domain', $group->getHTTPDomain(), $group_id);
    }
    if ($group->getUnixBox() != $form_box) {
        group_add_history('unix_box', $group->getUnixBox(), $group_id);
    }
    if (isset($form_status) && $form_status) {
        db_query("UPDATE groups SET status='".db_es($form_status)."',"
            . "type='".db_es($group_type)."',"
            . "unix_box='".db_es($form_box)."',http_domain='".db_es($form_domain)."'"
            . " WHERE group_id=".db_ei($group_id));
    }

    $GLOBALS['Response']->addFeedback('info', $Language->getText('admin_groupedit', 'feedback_info'));

    // ZD: Raise an event for group update
    if (isset($form_status) && $form_status && ($form_status == "H" || $form_status == "P")) {
        $em->processEvent('project_is_suspended_or_pending', array(
            'group_id' => $group_id
        ));
    } else if (isset($form_status) && $form_status && $form_status == "A") {
        $em->processEvent('project_is_active', array(
            'group_id' => $group_id
        ));
    } else if (isset($form_status) && $form_status && $form_status == "D") {
        $em->processEvent('project_is_deleted', array('group_id' => $group_id));
    }
    $GLOBALS['Response']->redirect('/admin/groupedit?group_id='.$group_id);
}

if ($request->exist('export')) {
    export_grouphistory($group_id, $event, $subEvents, $value, $startDate, $endDate, $by);
    exit;
}

site_admin_header(array('title'=>$Language->getText('admin_groupedit','title')));

echo '<H2>'.$group->getPublicName().'</H2>' ;?>

<p>
<A href="/project/admin/?group_id=<?php print $group_id; ?>"><B><BIG>[<?php echo $Language->getText('admin_groupedit','proj_admin'); ?>]</BIG></B></A><BR/>
<A href="userlist.php?group_id=<?php print $group_id; ?>"><B><BIG>[<?php echo $Language->getText('admin_groupedit','proj_member'); ?>]</BIG></B></A><BR/>
<A href="show_pending_documents.php?group_id=<?php print $group_id; ?>"><B><BIG>[<?php echo $Language->getText('admin_groupedit','pending_data'); ?>]</BIG></B></A><BR/>
<?php $em->processEvent('groupedit_data', array('group_id' => $group_id)); ?>
</p>

<p>
<FORM action="?" method="POST">
<B><?php echo $Language->getText('admin_groupedit','group_type'); ?>:</B>
<?php

$template =& TemplateSingleton::instance();
echo $template->showTypeBox('group_type',$group->getType());

?>

<B>
<?php if ($group->getGroupId() != Project::ADMIN_PROJECT_ID){
            echo $Language->getText('global','status');
?>
</B>
<?php
//Disable the possibilty to switch from deleted status to an active one
?>
<SELECT name="form_status" <?php if ($group->getStatus() == "D") print "disabled=disabled"; ?>>
<OPTION <?php if ($group->getStatus() == "I") print "selected "; ?> value="I">
<?php echo $Language->getText('admin_groupedit', 'status_I'); ?></OPTION>
<OPTION <?php if ($group->getStatus() == "A") print "selected "; ?> value="A">
<?php echo $Language->getText('admin_groupedit', 'status_A'); ?></OPTION>
<OPTION <?php if ($group->getStatus() == "P") print "selected "; ?> value="P">
<?php echo $Language->getText('admin_groupedit', 'status_P'); ?></OPTION>
<OPTION <?php if ($group->getStatus() == "H") print "selected "; ?> value="H">
<?php echo $Language->getText('admin_groupedit', 'status_H'); ?></OPTION>
<OPTION <?php if ($group->getStatus() == "D") print "selected "; ?> value="D">
<?php echo $Language->getText('admin_groupedit', 'status_D'); ?></OPTION>
</SELECT>
<?php
}
?>
<a href="/project/admin/editgroupinfo.php?group_id=<?php echo $group_id; ?>"><?php echo $Language->getText('admin_groupedit', 'manage_access'); ?></a>

<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<BR><?php echo $Language->getText('admin_groupedit','home_box'); ?>:
<INPUT type="text" name="form_box" value="<?php print $group->getUnixBox(); ?>">
<BR><?php echo $Language->getText('admin_groupedit','http_domain'); ?>:
<INPUT size=40 type="text" name="form_domain" value="<?php print $group->getHTTPDomain() ?>">
<BR><INPUT type="submit" name="Update" class="tlp-button-primary" value="<?php echo $Language->getText('global','btn_update'); ?>">
</FORM>

<P><A href="newprojectmail.php?group_id=<?php print $group_id; ?>">
<?php echo $Language->getText('admin_groupedit','send_email'); ?></A>

<?php

// ########################## OTHER INFO

print "<h3>".$Language->getText('admin_groupedit','other_info')."</h3>";
print $Language->getText('admin_groupedit','unix_grp').": ".$group->getUnixName();
?>
<FORM action="?" method="POST" class="form-inline">
<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<?php echo $Language->getText('admin_groupedit','rename_project_label'); ?>:
<INPUT type="text" name="new_name" value="<?php $new_name; ?>" id="new_name">
<INPUT type="submit" name="Rename" class="tlp-button-secondary" value="<?php echo $Language->getText('global','btn_update'); ?>">
</FORM>

<?php 
$group->displayProjectsDescFieldsValue();

$template_group = $pm->getProject($group->getTemplate());
print "<h3>".$Language->getText('admin_groupedit','built_from_template').':</h3> <a href="/projects/'.$template_group->getUnixName().'"> <B> '.$template_group->getPublicname().' </B></A>';


echo "<P><HR><P>";

echo '
<P>'.show_grouphistory($group_id, $offset, $limit, $event, $subEvents, $value, $startDate, $endDate, $by);

site_admin_footer(array());

?>
