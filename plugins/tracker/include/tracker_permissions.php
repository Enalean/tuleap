<?php

function plugin_tracker_permission_process_update_tracker_permissions($group_id, $atid, $permissions_wanted_by_user) {
    //The user want to update permissions for the tracker.
    //We look into the request for specials variable
    $prefixe_expected     = 'permissions_';
    $len_prefixe_expected = strlen($prefixe_expected);

    //some special ugroup names
    $anonymous_name    = $GLOBALS['Language']->getText('project_ugroup', ugroup_get_name_from_id($GLOBALS['UGROUP_ANONYMOUS']));
    $registered_name   = $GLOBALS['Language']->getText('project_ugroup', ugroup_get_name_from_id($GLOBALS['UGROUP_REGISTERED']));

    //small variables for history
    $add_full_to_history      = false;
    $add_assignee_to_history  = false;
    $add_submitter_to_history = false;

    //The actual permissions
    $stored_ugroups_permissions = plugin_tracker_permission_get_tracker_ugroups_permissions($group_id, $atid);

    //We look for anonymous and registered users' permissions, both in the user's request and in the db
    $user_set_anonymous_to_fullaccess        = isset($_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_ANONYMOUS']]) && $_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_ANONYMOUS']] === "0";
    $user_set_registered_to_fullaccess       = isset($_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_REGISTERED']]) && $_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_ANONYMOUS']] === "0";
    $anonymous_is_already_set_to_fullaccess  = isset($stored_ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['permissions']['PLUGIN_TRACKER_ACCESS_FULL']);
    $registered_is_already_set_to_fullaccess = isset($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['permissions']['PLUGIN_TRACKER_ACCESS_FULL']);
    $registered_is_already_set_to_assignee   = isset($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['permissions']['PLUGIN_TRACKER_ACCESS_ASSIGNEE']);
    $registered_is_already_set_to_submitter  = isset($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['permissions']['PLUGIN_TRACKER_ACCESS_SUBMITTER']);
    //ANONYMOUS
    ////////////////////////////////////////////////////////////////
    if (isset($_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_ANONYMOUS']])) {
        switch($_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_ANONYMOUS']]) {
        case 0:
            //PLUGIN_TRACKER_ACCESS_FULL
            //-------------------
            if (!$anonymous_is_already_set_to_fullaccess) {
                foreach($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                    if ($stored_ugroup_id === $GLOBALS['UGROUP_ANONYMOUS']) {
                        permission_add_ugroup($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $atid, $stored_ugroup_id);
                        $add_full_to_history = true;
                        $anonymous_is_already_set_to_fullaccess = true;
                    } else {
                        //We remove permissions for others ugroups
                        if (count($stored_ugroup_permissions['permissions']) > 0 
                            && (!isset($_REQUEST[$prefixe_expected.$stored_ugroup_id]) || $_REQUEST[$prefixe_expected.$stored_ugroup_id] != 100)) {

                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($stored_ugroup_permissions['ugroup']['name'], $anonymous_name)));
                            if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_FULL'])) {
                                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                $add_full_to_history = true;
                                if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                    $registered_is_already_set_to_fullaccess = false;
                                }
                            }
                            if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_ASSIGNEE'])) {
                                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                $add_assignee_to_history = true;
                                if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                    $registered_is_already_set_to_assignee = false;
                                }
                            }
                            if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_SUBMITTER'])) {
                                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                $add_submitter_to_history = true;
                                if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                    $registered_is_already_set_to_submitter = false;
                                }
                            }
                        }
                    }
                }
            }
            break;
        case 1:
            //PLUGIN_TRACKER_ACCESS_ASSIGNEE
            //-----------------------
            //forbidden, do nothing
            break;
        case 2:
            //PLUGIN_TRACKER_ACCESS_SUBMITTER
            //------------------------
            //forbidden, do nothing
            break;
        case 3:
            //PLUGIN_TRACKER_ACCESS_SUBMITTER && PLUGIN_TRACKER_ACCESS_ASSIGNEE
            //---------------------------------------------------
            //forbidden, do nothing
            break;
        case 100:
            //NO ACCESS
            //---------
            if ($anonymous_is_already_set_to_fullaccess) {
                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $GLOBALS['UGROUP_ANONYMOUS'], $atid);
                $add_submitter_to_history = true;
                $anonymous_is_already_set_to_fullaccess = false;
            }
            break;
        default:
            //do nothing
            break;
        }
    }

    //REGISTERED
    ////////////////////////////////////////////////////////////////
    if (isset($_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_REGISTERED']])) {
        switch($_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_REGISTERED']]) {
        case 0:
            //PLUGIN_TRACKER_ACCESS_FULL
            //-------------------
            if (!$registered_is_already_set_to_fullaccess) {
                //It is not necessary to process if the anonymous has full access
                if ($anonymous_is_already_set_to_fullaccess) {
                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name)));
                } else {
                    foreach($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                        if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                            //We remove old permissions
                            if ($registered_is_already_set_to_assignee) {
                                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                $add_assignee_to_history = true;
                                $registered_is_already_set_to_assignee = false;
                            }
                            if ($registered_is_already_set_to_submitter) {
                                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                $add_submitter_to_history = true;
                                $registered_is_already_set_to_submitter = false;
                            }
                            permission_add_ugroup($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $atid, $stored_ugroup_id);
                            $add_full_to_history = true;
                            $registered_is_already_set_to_fullaccess = true;
                        } else if($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                            //We remove permissions for others ugroups
                            if (count($stored_ugroup_permissions['permissions']) > 0 
                                && (!isset($_REQUEST[$prefixe_expected.$stored_ugroup_id]) || $_REQUEST[$prefixe_expected.$stored_ugroup_id] != 100)) {
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', array($stored_ugroup_permissions['ugroup']['name'], $registered_name)));
                                if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_FULL'])) {
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                    $add_full_to_history = true;
                                }
                                if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_ASSIGNEE'])) {
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                    $add_assignee_to_history = true;
                                }
                                if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_SUBMITTER'])) {
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                    $add_submitter_to_history = true;
                                }
                            }
                        }
                    }
                }
            }
            break;
        case 1:
            //TRACKER_ACCESS_ASSIGNEE
            //-----------------------
            if (!$registered_is_already_set_to_assignee) {
                //It is not necessary to process if the anonymous has full access (anon can't have assignee or submitter access)
                if ($anonymous_is_already_set_to_fullaccess) {
                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name)));
                } else {
                    foreach($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                        if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                            //We remove old permissions
                            if ($registered_is_already_set_to_fullaccess) {
                                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                $add_full_to_history = true;
                                $registered_is_already_set_to_fullaccess = false;
                            }
                            if ($registered_is_already_set_to_submitter) {
                                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                $add_submitter_to_history = true;
                                $registered_is_already_set_to_submitter = false;
                            }
                            permission_add_ugroup($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $atid, $stored_ugroup_id);
                            $registered_is_already_set_to_assignee = true;
                        } else if($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                            //We remove permissions for others ugroups if they have assignee
                            if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_ASSIGNEE']) && !isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_SUBMITTER']) 
                                && (!isset($_REQUEST[$prefixe_expected.$stored_ugroup_id]) || $_REQUEST[$prefixe_expected.$stored_ugroup_id] != 100)) {
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_assignee', array($stored_ugroup_permissions['ugroup']['name'], $registered_name)));
                                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                $add_assignee_to_history = true;
                            }
                        }
                    }
                }
            }
            break;
        case 2:
            //PLUGIN_TRACKER_ACCESS_SUBMITTER
            //------------------------
            if (!$registered_is_already_set_to_submitter) {
                //It is not necessary to process if the anonymous has full access (anon can't have assignee or submitter access)
                if ($anonymous_is_already_set_to_fullaccess) {
                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name)));
                } else {
                    foreach($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                        if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                            //We remove old permissions
                            if ($registered_is_already_set_to_fullaccess) {
                                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                $add_full_to_history = true;
                                $registered_is_already_set_to_fullaccess = false;
                            }
                            if ($registered_is_already_set_to_assignee) {
                                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                $add_assignee_to_history = true;
                                $registered_is_already_set_to_assignee = false;
                            }
                            permission_add_ugroup($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $atid, $stored_ugroup_id);
                            $add_submitter_to_history = true;
                            $registered_is_already_set_to_submitter = true;
                        } else if($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                            //We remove permissions for others ugroups if they have submitter
                            if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_SUBMITTER']) && !isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_ASSIGNEE']) 
                                && (!isset($_REQUEST[$prefixe_expected.$stored_ugroup_id]) || $_REQUEST[$prefixe_expected.$stored_ugroup_id] != 100)) {
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter', array($stored_ugroup_permissions['ugroup']['name'], $registered_name)));
                                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                $add_submitter_to_history = true;
                            }
                        }
                    }
                }
            }
            break;
        case 3:
            //PLUGIN_TRACKER_ACCESS_SUBMITTER && PLUGIN_TRACKER_ACCESS_ASSIGNEE
            //---------------------------------------------------
            if (!($registered_is_already_set_to_submitter && $registered_is_already_set_to_assignee)) {
                //It is not necessary to process if the anonymous has full access (anon can't have assignee or submitter access)
                if ($anonymous_is_already_set_to_fullaccess) {
                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name)));
                } else {
                    foreach($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                        if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                            //We remove old permissions
                            if ($registered_is_already_set_to_fullaccess) {
                                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                $add_full_to_history = true;
                                $registered_is_already_set_to_fullaccess = false;
                            }
                            if (!$registered_is_already_set_to_assignee) {
                                permission_add_ugroup($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $atid, $stored_ugroup_id);
                                $add_assignee_to_history = true;
                                $registered_is_already_set_to_assignee = true;
                            }
                            if (!$registered_is_already_set_to_submitter) {
                                permission_add_ugroup($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $atid, $stored_ugroup_id);
                                $add_submitter_to_history = true;
                                $registered_is_already_set_to_submitter = true;
                            }
                        } else if($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                            //We remove permissions for others ugroups if they have submitter or assignee
                            if ((isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_SUBMITTER']) || isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_ASSIGNEE'])) 
                                && (!isset($_REQUEST[$prefixe_expected.$stored_ugroup_id]) || $_REQUEST[$prefixe_expected.$stored_ugroup_id] != 100)) {
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter_assignee', array($stored_ugroup_permissions['ugroup']['name'], $registered_name)));
                                if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_SUBMITTER'])) {
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid); 
                                    $add_submitter_to_history = true;
                                }
                                if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_ACCESS_ASSIGNEE'])) {
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                    $add_assignee_to_history = true;
                                }
                            }
                        }
                    }
                }
            }
            break;
        case 100:
            //NO SPECIFIC ACCESS
            //------------------
            if ($registered_is_already_set_to_assignee) {
                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $GLOBALS['UGROUP_REGISTERED'], $atid);
                $add_assignee_to_history = true;
                $registered_is_already_set_to_assignee = false;
            }
            if ($registered_is_already_set_to_submitter) {
                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $GLOBALS['UGROUP_REGISTERED'], $atid);
                $add_submitter_to_history = true;
                $registered_is_already_set_to_submitter = false;
            }
            if ($registered_is_already_set_to_fullaccess) {
                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $GLOBALS['UGROUP_REGISTERED'], $atid);
                $add_full_to_history = true;
                $registered_is_already_set_to_fullaccess = false;
            }
            break;
        default:
            //do nothing
            break;
        }
    }


    //OTHERS INSIGNIFIANT UGROUPS
    ////////////////////////////////////////////////////////////////
    foreach($_REQUEST as $key => $value) {
        $pos = strpos($key, $prefixe_expected);
        if ($pos !== false) {
            //We've just found a variable
            //We check now if the suffixe (id of ugroup) and the value is numeric values
            $suffixe = substr($key, $len_prefixe_expected);
            if (is_numeric($suffixe)) {
                $ugroup_id  = $suffixe;
                if ($ugroup_id != $GLOBALS['UGROUP_ANONYMOUS'] && $ugroup_id != $GLOBALS['UGROUP_REGISTERED']) { //already done.
                    $ugroup_name = $stored_ugroups_permissions[$ugroup_id]['ugroup']['name'];
                    switch($value) {
                    case 0: 
                        //PLUGIN_TRACKER_FULL_ACCESS
                        //-------------------
                        if (!isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_FULL'])) {
                            if ($anonymous_is_already_set_to_fullaccess) { //It is not necessary to process if the anonymous has full access 
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($ugroup_name, $anonymous_name)));
                            } else if ($registered_is_already_set_to_fullaccess) {//It is not necessary to process if the registered has full access 
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', array($ugroup_name, $registered_name)));
                            } else {
                                //We remove old permissions
                                if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_ASSIGNEE'])) {
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $ugroup_id, $atid);
                                    $add_assignee_to_history = true;
                                }
                                if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_SUBMITTER'])) {
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $ugroup_id, $atid);
                                    $add_submitter_to_history = true;
                                }
                                permission_add_ugroup($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $atid, $ugroup_id);
                                $add_full_to_history = true;
                            }
                        }
                        break;
                    case 1: 
                        //PLUGIN_TRACKER_ACCESS_ASSIGNEE
                        //-----------------------
                        if (!isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_ASSIGNEE'])) {
                            //It is not necessary to process if the anonymous has full access 
                            if ($anonymous_is_already_set_to_fullaccess) {
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($ugroup_name, $anonymous_name)));
                            } else if ($registered_is_already_set_to_fullaccess) {//It is not necessary to process if the registered has full access 
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', array($ugroup_name, $registered_name)));
                            } else if ($registered_is_already_set_to_submitter && $registered_is_already_set_to_assignee) {//It is not necessary to process if the registered has submitter and assignee
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter_assignee', array($ugroup_name, $registered_name)));
                            } else if ($registered_is_already_set_to_assignee) {//It is not necessary to process if the registered has assignee
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_assignee', array($ugroup_name, $registered_name)));
                            } else {
                                //We remove old permissions
                                if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_FULL'])) {
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $ugroup_id, $atid);
                                    $add_full_to_history = true;
                                }
                                if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_SUBMITTER'])) {
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $ugroup_id, $atid);
                                    $add_submitter_to_history = true;
                                }
                                permission_add_ugroup($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $atid, $ugroup_id);
                                $add_assignee_to_history = true;
                            }
                        }
                        break;
                    case 2: 
                        //PLUGIN_TRACKER_ACCESS_SUBMITTER
                        //------------------------
                        if (!isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_SUBMITTER'])) {
                            //It is not necessary to process if the anonymous has full access
                            if ($anonymous_is_already_set_to_fullaccess) {
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($ugroup_name, $anonymous_name)));
                            } else if ($registered_is_already_set_to_fullaccess) {//It is not necessary to process if the registered has full access 
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', array($ugroup_name, $registered_name)));
                            } else if ($registered_is_already_set_to_submitter && $registered_is_already_set_to_assignee) {//It is not necessary to process if the registered has submitter and assignee
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter_assignee', array($ugroup_name, $registered_name)));
                            } else if ($registered_is_already_set_to_submitter) {//It is not necessary to process if the registered has submitter
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter', array($ugroup_name, $registered_name)));
                            } else {
                                //We remove old permissions
                                if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_FULL'])) {
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $ugroup_id, $atid);
                                    $add_full_to_history = true;
                                }
                                if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_ASSIGNEE'])) {
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $ugroup_id, $atid);
                                    $add_assignee_to_history = true;
                                }
                                permission_add_ugroup($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $atid, $ugroup_id);
                                $add_submitter_to_history = true;
                            }
                        }
                        break;
                    case 3: 
                        //PLUGIN_TRACKER_ACCESS_SUBMITTER && PLUGIN_TRACKER_ACCESS_ASSIGNEE
                        //---------------------------------------------------
                        if (!(isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_ASSIGNEE']) && isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_SUBMITTER']))) {
                            //It is not necessary to process if the anonymous has full access
                            if ($anonymous_is_already_set_to_fullaccess) {
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($ugroup_name, $anonymous_name)));
                            } else if ($registered_is_already_set_to_fullaccess) {//It is not necessary to process if the registered has full access 
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', array($ugroup_name, $registered_name)));
                            } else if ($registered_is_already_set_to_submitter && $registered_is_already_set_to_assignee) {//It is not necessary to process if the registered has submitter and assignee
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter_assignee', array($ugroup_name, $registered_name)));
                            } else {
                                //We remove old permissions
                                if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_FULL'])) {
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $ugroup_id, $atid);
                                    $add_full_to_history = true;
                                }
                                if (!isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_ASSIGNEE'])) {
                                    permission_add_ugroup($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $atid, $ugroup_id);
                                    $add_assignee_to_history = true;
                                }
                                if (!isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_SUBMITTER'])) {
                                    permission_add_ugroup($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $atid, $ugroup_id);
                                    $add_submitter_to_history = true;
                                }
                            }
                        }
                        break;
                    case 100: 
                        //NO SPECIFIC ACCESS
                        //------------------
                        if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_FULL'])) {
                            permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $ugroup_id, $atid);
                            $add_full_to_history = true;
                        }
                        if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_ASSIGNEE'])) {
                            permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $ugroup_id, $atid);
                            $add_assignee_to_history = true;
                        }
                        if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['PLUGIN_TRACKER_ACCESS_SUBMITTER'])) {
                            permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $ugroup_id, $atid);
                            $add_submitter_to_history = true;
                        }
                        break;
                    default:
                        //do nothing
                        break;
                    }
                }
            }
        }
    }
    //history
    if ($add_full_to_history) {
        permission_add_history($group_id, 'PLUGIN_TRACKER_ACCESS_FULL', $atid);
    }
    if ($add_assignee_to_history) {
        permission_add_history($group_id, 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', $atid);
    }
    if ($add_submitter_to_history) {
        permission_add_history($group_id, 'PLUGIN_TRACKER_ACCESS_SUBMITTER', $atid);
    }
    
    //feedback
    if ($add_full_to_history || $add_assignee_to_history || $add_submitter_to_history) {
        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_userperms', 'perm_upd'));
    }
}

/**
 * @returns array the permissions for the ugroups
 */
function plugin_tracker_permission_get_tracker_ugroups_permissions($group_id, $object_id) {
  return permission_get_ugroups_permissions($group_id, $object_id, array('PLUGIN_TRACKER_ACCESS_FULL','PLUGIN_TRACKER_ACCESS_ASSIGNEE','PLUGIN_TRACKER_ACCESS_SUBMITTER'), false);
}





?>
