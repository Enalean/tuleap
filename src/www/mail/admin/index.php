<?php
// Copyright 2014-Present (c) Enalean SAS
// This file is part of Tuleap
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net

use Tuleap\MailingList\MailingListAdministrationController;

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../mail_utils.php';

$sys_lists_domain = ForgeConfig::get('sys_lists_domain');
if ($sys_lists_domain == 'lists.%sys_default_domain%') {
    $sys_lists_domain = ForgeConfig::get('sys_lists_host');
}

$purifier = Codendi_HTMLPurifier::instance();

$pm = ProjectManager::instance();
if ($group_id && user_ismember($group_id, 'A')) {
    $csrf = MailingListAdministrationController::getCSRF($pm->getProject((int) $group_id));
    $list_server = get_list_server_url();

    if ($request->existAndNonEmpty('post_changes')) {
        /*
          Update the DB to reflect the changes
         */

        if ($request->existAndNonEmpty('add_list')) {
            $csrf->check();
            $list_password = sodium_bin2base64(random_bytes(12), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
            $list_name = $request->getValidated('list_name', 'string', '');
            if (! $list_name || strlen($list_name) < ForgeConfig::get('sys_lists_name_min_length')) {
                exit_error($Language->getText('global', 'error'), _('Must Provide List Name That Is 4 or More Characters Long'));
            }
            if (! preg_match('/(^([a-zA-Z\_0-9\.-]*))$/', $list_name)) {
                exit_error($Language->getText('global', 'error'), _('List Name Contains Bad Characters. Authorized Characters are : letters, numbers, -, _, .'));
            }
            if (user_is_super_user()) {
                $new_list_name = strtolower($list_name);
            } else {
                $new_list_name = ForgeConfig::get('sys_lists_prefix') . strtolower($pm->getProject($group_id)->getUnixName() . '-' . $list_name) . ForgeConfig::get('sys_lists_suffix');
            }

            //see if that's a valid email address
            if (validate_email($new_list_name . '@' . $sys_lists_domain)) {
                $result = db_query("SELECT * FROM mail_group_list WHERE lower(list_name)='" . db_es($new_list_name) . "'");

                if (db_numrows($result) > 0) {
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('ERROR - List Already Exists'));
                } else {
                    $group_id = db_ei($group_id);
                    $is_public = db_ei($request->getValidated('is_public', 'int', 0));
                    $description = db_es(htmlspecialchars($request->getValidated('description', 'string', '')));
                    $new_list_name = db_es($new_list_name);
                    $list_password = db_es($list_password);
                    $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());
                    $sql = "INSERT INTO mail_group_list
                                            (group_id,list_name,is_public,password,list_admin,status,description) VALUES (
                                            $group_id,
                                            '$new_list_name',
                                            $is_public,
                                            '$list_password',
                                            $db_escaped_user_id,
                                            1,
                                            '$description')";


                    $result = db_query($sql);
                    $group_list_id = db_insertid($result);

                    if (! $result) {
                        $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('Error Adding List'));
                    } else {
                        $GLOBALS['Response']->addFeedback(Feedback::INFO, _('List added'));
                    }

                    // Raise an event
                    EventManager::instance()->processEvent('mail_list_create', ['group_list_id' => $group_list_id,]);

                    // get email addr
                    $res_email = db_query("SELECT email FROM user WHERE user_id='" . $db_escaped_user_id . "'");
                    if (db_numrows($res_email) < 1) {
                        exit_error(_('Invalid userid'), _('Email address not found.'));
                    }
                    $row_email = db_fetch_array($res_email);

                    // mail password to admin
                    $message = sprintf(_('A mailing list will be created on %1$s in a few minutes
and you are the list administrator.

Your mailing list info is at:
%3$s

List administration can be found at:
%4$s

Your list password is: %5$s
You are encouraged to change this password as soon as possible.

Thank you for using %1$s.

 -- The %1$s Team'), ForgeConfig::get('sys_name'), $new_list_name . '@' . $sys_lists_domain, $list_server . "/mailman/listinfo/$new_list_name", $list_server . "/mailman/admin/$new_list_name", $list_password);

                    $hdrs = "From: " . ForgeConfig::get('sys_email_admin') . ForgeConfig::get('sys_lf');
                    $hdrs .= 'Content-type: text/plain; charset=utf-8' . ForgeConfig::get('sys_lf');

                    mail($row_email['email'], ForgeConfig::get('sys_name') . " " . _('New mailing list'), $message, $hdrs);

                    $GLOBALS['Response']->addFeedback(
                        Feedback::INFO,
                        sprintf(_('Email sent with details to: %1$s'), $row_email['email']),
                    );
                }
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('Invalid List Name'));
            }
            $GLOBALS['Response']->redirect(MailingListAdministrationController::getUrl($pm->getProject($group_id)));
        }
    }
} else {
    /*
      Not logged in or insufficient privileges
     */
    if (! $group_id) {
        exit_no_group();
    } else {
        exit_permission_denied();
    }
}
