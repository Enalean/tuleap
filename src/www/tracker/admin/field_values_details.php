<?php
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
//
//
//
//  Written for Codendi by Stephane Bouhet

if (! user_isloggedin()) {
    exit_not_logged_in();
    return;
}

if (! $ath->userIsAdmin()) {
    exit_permission_denied();
    return;
}

// Check if this tracker is valid (not deleted)
if (! $ath->isValid()) {
    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_add', 'invalid'));
}

$ath->adminHeader(['title' => $Language->getText('tracker_admin_field_usage', 'tracker_admin') . $Language->getText('tracker_admin_field_values_details', 'values_admin'),
]);

$hp = Codendi_HTMLPurifier::instance();
echo "<H2>" . $Language->getText('tracker_import_admin', 'tracker') . ' \'<a href="/tracker/admin/?group_id=' . (int) $group_id . "&atid=" . (int) $atid . '">' . $hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODENDI_PURIFIER_CONVERT_HTML) . "</a>'" .
$Language->getText('tracker_admin_field_values_details', 'manage_for', $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODENDI_PURIFIER_CONVERT_HTML)) . "</H2>";

if (! $field->isSelectBox() && ! $field->isMultiSelectBox()) {
    $ath->displayDefaultValueForm($field_id, $field->getDefaultValue());
} else {
        $val_func = $field->getValueFunction();
    if (isset($val_func[0]) && $val_func[0]) {
        $ath->displayValueFunctionForm($field_id, $val_func);
        $ath->displayDefaultValueFunctionForm($field_id, $field->getDefaultValue(), $val_func);
    } else {
        $ath->displayFieldValuesList($field_id);
        $ath->displayDefaultValueForm($field_id, $field->getDefaultValue());
     // For severity field, we don't display the Bind form or the Create Form
        if (($field->getName() != "severity" && $field->getName() != "status_id") || user_is_super_user()) {
            echo '<hr>';
            $ath->displayFieldValueForm("value_create", $field_id);
            $ath->displayValueFunctionForm($field_id, null, $Language->getText('global', 'or'));
        }
    }
}

$ath->footer([]);
