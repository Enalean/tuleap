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

    // if the error comes from the SOAP API, we don't display the site_header and footer, but a soap fault (xml).
    if (substr($_SERVER['SCRIPT_NAME'], 1, 4) != "soap") {
        site_header(array('title' => $Language->getText('include_exit', 'exit_error')));
        echo '<p data-test="feedback">',$text,'</p>';
        $HTML->footer(array('showfeedback' => false));
    } else {
        exit_display_soap_error();
    }
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

function exit_display_soap_error()
{
    header('Content-type: text/xml');
    // Sometimes, there is nothing in $text, so we take the feedback in the $GLOBALS['Response']
    if (array_key_exists('Response', $GLOBALS)) {
        $text = $GLOBALS['Response']->getRawFeedback();
    }
    $fault_code = "1000";
    $fault_factor = 'exit_error';
    $fault_string = strip_tags($text);
    $fault_detail = strip_tags($text);
    print_soap_fault($fault_code, $fault_factor, $fault_string, $fault_detail);
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
    $msg = $feedback;
    $feedback = "";
    exit_error($Language->getText('include_exit', 'missing_param_err'), '<p>' . $msg);
}

function print_soap_fault($fault_code, $fault_factor, $fault_string, $fault_detail)
{
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/">';
    echo '<SOAP-ENV:Body>';
    echo '<SOAP-ENV:Fault>';
    echo '<faultcode xsi:type="xsd:string">' . $fault_code . '</faultcode>';
    echo '<faultactor xsi:type="xsd:string">' . $fault_factor . '</faultactor>';
    echo '<faultstring xsi:type="xsd:string">' . $fault_string . '</faultstring>';
    echo '<detail xsi:type="xsd:string">' . $fault_detail . '</detail>';
    echo '</SOAP-ENV:Fault>';
    echo '</SOAP-ENV:Body>';
    echo '</SOAP-ENV:Envelope>';
}
