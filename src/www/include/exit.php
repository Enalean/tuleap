<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
/**
 * @psalm-return never-return
 */
function exit_error($title, $text = '')
{
    global $HTML,$Language;
    $GLOBALS['feedback'] .= $title;

    site_header(\Tuleap\Layout\HeaderConfiguration::fromTitle($Language->getText('include_exit', 'exit_error')));
    echo '<p data-test="feedback">' . Codendi_HTMLPurifier::instance()->purify($text) . '</p>';
    $HTML->footer([]);
    exit;
}

function exit_permission_denied()
{
    global $feedback,$Language;
    if (UserManager::instance()->getCurrentUser()->isAnonymous()) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('include_exit', 'perm_denied'));
        $GLOBALS['Response']->addFeedback('error', $Language->getText('include_exit', 'no_perm'));
        if ($feedback) {
            $GLOBALS['Response']->addFeedback('error', $feedback);
        }
        exit_not_logged_in();
    } else {
        exit_error($Language->getText('include_exit', 'perm_denied'), $Language->getText('include_exit', 'no_perm') . '<p>' . $feedback);
    }
}

function exit_not_logged_in()
{
    global $Language;
    //instead of a simple error page, now take them to the login page
    $GLOBALS['Response']->redirect("/account/login.php?return_to=" . urlencode($_SERVER['REQUEST_URI']));
    //exit_error($Language->getText('include_exit','not_logged_in'),$Language->getText('include_exit','need_to_login'));
}

/**
 * @psalm-return never-return
 */
function exit_no_group()
{
    global $feedback,$Language;
    exit_error($Language->getText('include_exit', 'choose_proj_err'), $Language->getText('include_exit', 'no_gid_err') . '<p>' . $feedback);
}

function exit_missing_param()
{
    global $feedback,$Language;
  // Display current $feedback normally, and replace feedback with error message
    $msg      = $feedback;
    $feedback = "";
    exit_error($Language->getText('include_exit', 'missing_param_err'), '<p>' . $msg);
}
