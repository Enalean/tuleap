<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * @returns array the permissions for the ugroups
 */
function plugin_tracker_permission_get_tracker_ugroups_permissions($group_id, $object_id)
{
    return permission_get_ugroups_permissions($group_id, $object_id, array(Tracker::PERMISSION_ADMIN, Tracker::PERMISSION_FULL, Tracker::PERMISSION_ASSIGNEE, Tracker::PERMISSION_SUBMITTER, Tracker::PERMISSION_SUBMITTER_ONLY), false);
}

function plugin_tracker_permission_process_update_fields_permissions($group_id, $atid, $fields, $permissions_wanted_by_user)
{
    //The actual permissions

    $stored_ugroups_permissions = plugin_tracker_permission_get_field_tracker_ugroups_permissions($group_id, $atid, $fields);

    $permissions_updated = false;

    //some special ugroup names
    $anonymous_name  = $GLOBALS['Language']->getText('project_ugroup', 'ugroup_anonymous_users_name_key');
    $registered_name = $GLOBALS['Language']->getText('project_ugroup', 'ugroup_registered_users_name_key');

    //We process the request
    foreach ($permissions_wanted_by_user as $field_id => $ugroups_permissions) {
        if (
            is_numeric($field_id)
            && isset($stored_ugroups_permissions[$field_id])
        ) {
            $the_field_can_be_submitted = $stored_ugroups_permissions[$field_id]['field']['field']->isSubmitable();
            $the_field_can_be_updated   = $stored_ugroups_permissions[$field_id]['field']['field']->isUpdateable();

            $fake_object_id = $field_id;

            //small variables for history
            $add_submit_to_history = false;
            $add_read_to_history   = false;
            $add_update_to_history = false;

            //We look for anonymous and registered users' permissions, both in the user's request and in the db
            $user_set_anonymous_to_submit = isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]) &&
                isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['submit']) &&
                $ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['submit'] === "on";
            $user_set_anonymous_to_read   = isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]) &&
                isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['others']) &&
                $ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['others'] === "0";
            $user_set_anonymous_to_update = isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]) &&
                isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['others']) &&
                $ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['others'] === "1";
            $user_set_registered_to_submit = isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]) &&
                isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['submit']) &&
                $ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['submit'] === "on";
            $user_set_registered_to_read   = isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]) &&
                isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['others']) &&
                $ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['others'] === "0";
            $user_set_registered_to_update = isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]) &&
                isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['others']) &&
                $ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['others'] === "1";

            $anonymous_is_already_set_to_submit  = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_ANONYMOUS']]['permissions']['PLUGIN_TRACKER_FIELD_SUBMIT']);
            $anonymous_is_already_set_to_read    = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_ANONYMOUS']]['permissions']['PLUGIN_TRACKER_FIELD_READ']);
            $anonymous_is_already_set_to_update  = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_ANONYMOUS']]['permissions']['PLUGIN_TRACKER_FIELD_UPDATE']);
            $registered_is_already_set_to_submit = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['permissions']['PLUGIN_TRACKER_FIELD_SUBMIT']);
            $registered_is_already_set_to_read   = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['permissions']['PLUGIN_TRACKER_FIELD_READ']);
            $registered_is_already_set_to_update = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['permissions']['PLUGIN_TRACKER_FIELD_UPDATE']);

            //ANONYMOUS
            ////////////////////////////////////////////////////////////////
            //Firstly we set permissions for anonymous users
            if (isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']])) {
                $ugroup_permissions = $ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']];

                //SUBMIT Permission
                //-----------------
                if ($the_field_can_be_submitted && !$anonymous_is_already_set_to_submit && $user_set_anonymous_to_submit) {
                    //if the ugroup is anonymous, we have to erase submit permissions for other ugroups
                    foreach ($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                        if ($stored_ugroup_id === $GLOBALS['UGROUP_ANONYMOUS']) {
                            permission_add_ugroup($group_id, 'PLUGIN_TRACKER_FIELD_SUBMIT', $fake_object_id, $stored_ugroup_id);
                            $add_submit_to_history = true;
                            $anonymous_is_already_set_to_submit = true;
                        } else {
                            if (
                                isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_FIELD_SUBMIT'])
                                && (!isset($ugroups_permissions[$stored_ugroup_id])
                                    || !isset($ugroups_permissions[$stored_ugroup_id]['submit'])
                                    || $ugroups_permissions[$stored_ugroup_id]['submit'] !== "on")
                            ) {
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_submit', array($stored_ugroup_permissions['ugroup']['name'], $anonymous_name)));
                                permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_SUBMIT', $stored_ugroup_id, $fake_object_id);
                                $add_submit_to_history = true;
                            }
                        }
                    }
                } elseif ($anonymous_is_already_set_to_submit && !$user_set_anonymous_to_submit) {
                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_SUBMIT', $GLOBALS['UGROUP_ANONYMOUS'], $fake_object_id);
                    $add_submit_to_history = true;
                    $anonymous_is_already_set_to_submit = false;
                }

                //UPDATE Permission
                //---------------
                if ($the_field_can_be_updated && !$anonymous_is_already_set_to_update && $user_set_anonymous_to_update) {
                    //if the ugroup is anonymous, we have to erase submt permissions for other ugroups
                    foreach ($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                        if ($stored_ugroup_id === $GLOBALS['UGROUP_ANONYMOUS']) {
                            permission_add_ugroup($group_id, 'PLUGIN_TRACKER_FIELD_UPDATE', $fake_object_id, $stored_ugroup_id);
                            $add_update_to_history = true;
                            $anonymous_is_already_set_to_update = true;
                        } else {
                            if (
                                !isset($ugroups_permissions[$stored_ugroup_id])
                                || !isset($ugroups_permissions[$stored_ugroup_id]['others'])
                                || $ugroups_permissions[$stored_ugroup_id]['others'] !== "100"
                            ) {
                                if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_FIELD_UPDATE'])) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', array($stored_ugroup_permissions['ugroup']['name'], $anonymous_name)));
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_UPDATE', $stored_ugroup_id, $fake_object_id);
                                    $add_update_to_history = true;
                                }
                                if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_FIELD_READ'])) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', array($stored_ugroup_permissions['ugroup']['name'], $anonymous_name)));
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_READ', $stored_ugroup_id, $fake_object_id);
                                    $add_read_to_history = true;
                                }
                            }
                        }
                    }
                } elseif ($anonymous_is_already_set_to_update && !$user_set_anonymous_to_update) {
                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_UPDATE', $GLOBALS['UGROUP_ANONYMOUS'], $fake_object_id);
                    $add_update_to_history = true;
                    $anonymous_is_already_set_to_update = false;
                }

                //READ Permission
                //---------------
                if (!$anonymous_is_already_set_to_read && $user_set_anonymous_to_read) {
                    //if the ugroup is anonymous, we have to erase submit permissions for other ugroups
                    foreach ($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                        if ($stored_ugroup_id === $GLOBALS['UGROUP_ANONYMOUS']) {
                            permission_add_ugroup($group_id, 'PLUGIN_TRACKER_FIELD_READ', $fake_object_id, $stored_ugroup_id);
                            $add_read_to_history = true;
                            $anonymous_is_already_set_to_read = true;
                        } else {
                            if (
                                !isset($ugroups_permissions[$stored_ugroup_id])
                                || !isset($ugroups_permissions[$stored_ugroup_id]['others'])
                                || $ugroups_permissions[$stored_ugroup_id]['others'] !== "100"
                            ) {
                                if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_FIELD_READ'])) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_read', array($stored_ugroup_permissions['ugroup']['name'], $anonymous_name)));
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_READ', $stored_ugroup_id, $fake_object_id);
                                    $add_read_to_history = true;
                                }
                            }
                        }
                    }
                } elseif ($anonymous_is_already_set_to_read && !$user_set_anonymous_to_read) {
                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_READ', $GLOBALS['UGROUP_ANONYMOUS'], $fake_object_id);
                    $add_read_to_history = true;
                    $anonymous_is_already_set_to_read = false;
                }
            }

            //REGISTERED
            ////////////////////////////////////////////////////////////////
            //Secondly we set permissions for registered users
            if (isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']])) {
                $ugroup_permissions = $ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']];

                //SUBMIT Permission
                //-----------------
                if ($the_field_can_be_submitted && !$registered_is_already_set_to_submit && $user_set_registered_to_submit) {
                    //if the ugroup is registered, we have to:
                    // 1. check consistency with current permissions for anonymous users
                    if ($user_set_anonymous_to_submit || $anonymous_is_already_set_to_submit) {
                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_submit', array($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name)));
                    } else {
                        // 2. erase submit permissions for other ugroups
                        foreach ($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                            if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                permission_add_ugroup($group_id, 'PLUGIN_TRACKER_FIELD_SUBMIT', $fake_object_id, $stored_ugroup_id);
                                $add_submit_to_history = true;
                                $registered_is_already_set_to_submit = true;
                            } elseif ($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) {
                                if (
                                    isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_FIELD_SUBMIT'])
                                    && (!isset($ugroups_permissions[$stored_ugroup_id])
                                        || !isset($ugroups_permissions[$stored_ugroup_id]['submit'])
                                        || $ugroups_permissions[$stored_ugroup_id]['submit'] !== "on")
                                ) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_submit', array($stored_ugroup_permissions['ugroup']['name'], $registered_name)));
                                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_SUBMIT', $stored_ugroup_id, $fake_object_id);
                                    $add_submit_to_history = true;
                                }
                            }
                        }
                    }
                } elseif ($registered_is_already_set_to_submit && !$user_set_registered_to_submit) {
                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_SUBMIT', $GLOBALS['UGROUP_REGISTERED'], $fake_object_id);
                    $add_submit_to_history = true;
                    $registered_is_already_set_to_submit = false;
                }

                //UPDATE Permission
                //---------------
                if ($the_field_can_be_updated && !$registered_is_already_set_to_update && $user_set_registered_to_update) {
                    //if the ugroup is registered, we have to:
                    // 1. check consistency with current permissions for anonymous users
                    if ($user_set_anonymous_to_update || $anonymous_is_already_set_to_update) {
                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', array($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name)));
                    } else {
                        // 2. erase update permissions for other ugroups
                        foreach ($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                            if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                permission_add_ugroup($group_id, 'PLUGIN_TRACKER_FIELD_UPDATE', $fake_object_id, $stored_ugroup_id);
                                $add_update_to_history = true;
                                $registered_is_already_set_to_update = true;
                            } elseif ($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                                if (
                                    !isset($ugroups_permissions[$stored_ugroup_id])
                                    || !isset($ugroups_permissions[$stored_ugroup_id]['others'])
                                    || $ugroups_permissions[$stored_ugroup_id]['others'] !== "100"
                                ) {
                                    if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_FIELD_UPDATE'])) {
                                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_update', array($stored_ugroup_permissions['ugroup']['name'], $registered_name)));
                                        permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_UPDATE', $stored_ugroup_id, $fake_object_id);
                                        $add_update_to_history = true;
                                    }
                                    if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_FIELD_READ'])) {
                                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_update', array($stored_ugroup_permissions['ugroup']['name'], $registered_name)));
                                        permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_READ', $stored_ugroup_id, $fake_object_id);
                                        $add_read_to_history = true;
                                    }
                                }
                            }
                        }
                    }
                } elseif ($registered_is_already_set_to_update && !$user_set_registered_to_update) {
                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_UPDATE', $GLOBALS['UGROUP_REGISTERED'], $fake_object_id);
                    $add_update_to_history = true;
                    $registered_is_already_set_to_update = false;
                }

                //READ Permission
                //---------------
                if (!$registered_is_already_set_to_read && $user_set_registered_to_read) {
                    //if the ugroup is registered, we have to:
                    // 1. check consistency with current permissions for anonymous users
                    if ($user_set_anonymous_to_read || $anonymous_is_already_set_to_read || $anonymous_is_already_set_to_update) {
                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_read', array($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name)));
                    } else {
                        // 2. erase read permissions for other ugroups
                        foreach ($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                            if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                permission_add_ugroup($group_id, 'PLUGIN_TRACKER_FIELD_READ', $fake_object_id, $stored_ugroup_id);
                                $add_read_to_history = true;
                                $registered_is_already_set_to_read = true;
                            } elseif ($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                                if (
                                    !isset($ugroups_permissions[$stored_ugroup_id])
                                    || !isset($ugroups_permissions[$stored_ugroup_id]['others'])
                                    || $ugroups_permissions[$stored_ugroup_id]['others'] !== "100"
                                ) {
                                    if (isset($stored_ugroup_permissions['permissions']['PLUGIN_TRACKER_FIELD_READ'])) {
                                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_read', array($stored_ugroup_permissions['ugroup']['name'], $registered_name)));
                                        permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_READ', $stored_ugroup_id, $fake_object_id);
                                        $add_read_to_history = true;
                                    }
                                }
                            }
                        }
                    }
                } elseif ($registered_is_already_set_to_read && !$user_set_registered_to_read) {
                    permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_READ', $GLOBALS['UGROUP_REGISTERED'], $fake_object_id);
                    $registered_is_already_set_to_read = false;
                }
            }

            //OTHER INSIGNIFIANT UGROUPS
            ////////////////////////////////////////////////////////////////
            $ugroup_manager = new UGroupManager();
            foreach ($ugroups_permissions as $ugroup_id => $ugroup_permissions) {
                if (is_numeric($ugroup_id) && $ugroup_id != $GLOBALS['UGROUP_REGISTERED'] && $ugroup_id != $GLOBALS['UGROUP_ANONYMOUS']) {
                    if (! isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id])) {
                        $ugroup         = $ugroup_manager->getById($ugroup_id);
                        $name_of_ugroup = $ugroup->getName();
                    } else {
                        $name_of_ugroup = $stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['ugroup']['name'];
                    }

                    //SUBMIT Permission
                    //-----------------
                    if (
                        $the_field_can_be_submitted && !isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['PLUGIN_TRACKER_FIELD_SUBMIT'])
                        && isset($ugroup_permissions['submit'])
                        && $ugroup_permissions['submit'] === "on"
                    ) {
                        //if the ugroup is not anonymous and not registered, we have to:
                        // check consistency with current permissions for anonymous users
                        // and current permissions for registered users
                        if ($user_set_anonymous_to_submit || $anonymous_is_already_set_to_submit) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_submit', array($name_of_ugroup, $anonymous_name)));
                        } elseif ($user_set_registered_to_submit || $registered_is_already_set_to_submit) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_submit', array($name_of_ugroup, $registered_name)));
                        } else {
                            permission_add_ugroup($group_id, 'PLUGIN_TRACKER_FIELD_SUBMIT', $fake_object_id, $ugroup_id);
                            $add_submit_to_history = true;
                        }
                    } elseif (
                        isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['PLUGIN_TRACKER_FIELD_SUBMIT'])
                              && isset($ugroup_permissions['submit'])
                              && $ugroup_permissions['submit'] !== "on"
                    ) {
                        //If we don't have already clear the permissions
                        if (!$user_set_anonymous_to_submit && !$user_set_registered_to_submit) {
                            permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_SUBMIT', $ugroup_id, $fake_object_id);
                            $add_submit_to_history = true;
                        }
                    }

                    //UPDATE Permission
                    //-----------------
                    if (
                        $the_field_can_be_updated && !isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['PLUGIN_TRACKER_FIELD_UPDATE'])
                        && isset($ugroup_permissions['others'])
                        && $ugroup_permissions['others'] === "1"
                    ) {
                        //if the ugroup is not anonymous and not registered, we have to:
                        // check consistency with current permissions for anonymous users
                        // and current permissions for registered users
                        if ($user_set_anonymous_to_update || $anonymous_is_already_set_to_update) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', array($name_of_ugroup, $anonymous_name)));
                        } elseif ($user_set_registered_to_update || $registered_is_already_set_to_update) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_update', array($name_of_ugroup, $registered_name)));
                        } else {
                            permission_add_ugroup($group_id, 'PLUGIN_TRACKER_FIELD_UPDATE', $fake_object_id, $ugroup_id);
                            $add_update_to_history = true;
                        }
                    } elseif (
                        isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['PLUGIN_TRACKER_FIELD_UPDATE'])
                              && isset($ugroup_permissions['others'])
                              && $ugroup_permissions['others'] !== "1"
                    ) {
                        //If we don't have already clear the permissions
                        if (!$user_set_anonymous_to_update && !$user_set_registered_to_update) {
                            permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_UPDATE', $ugroup_id, $fake_object_id);
                            $add_update_to_history = true;
                        }
                    }

                    //READ Permission
                    //-----------------
                    if (
                        !isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['PLUGIN_TRACKER_FIELD_READ'])
                        && isset($ugroup_permissions['others'])
                        && $ugroup_permissions['others'] === "0"
                    ) {
                        //if the ugroup is not anonymous and not registered, we have to:
                        // check consistency with current permissions for anonymous users
                        // and current permissions for registered users
                        if ($user_set_anonymous_to_read || $anonymous_is_already_set_to_read) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_read', array($name_of_ugroup, $anonymous_name)));
                        } elseif ($user_set_registered_to_read || $registered_is_already_set_to_read) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_read', array($name_of_ugroup, $registered_name)));
                        } elseif ($user_set_anonymous_to_update || $anonymous_is_already_set_to_update) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', array($name_of_ugroup, $anonymous_name)));
                        } elseif ($user_set_registered_to_update || $registered_is_already_set_to_update) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_update', array($name_of_ugroup, $registered_name)));
                        } else {
                            permission_add_ugroup($group_id, 'PLUGIN_TRACKER_FIELD_READ', $fake_object_id, $ugroup_id);
                            $add_read_to_history = true;
                        }
                    } elseif (
                        isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['PLUGIN_TRACKER_FIELD_READ'])
                              && isset($ugroup_permissions['others'])
                              && $ugroup_permissions['others'] !== "0"
                    ) {
                        //If we don't have already clear the permissions
                        if (!$user_set_anonymous_to_read && !$user_set_registered_to_read) {
                            permission_clear_ugroup_object($group_id, 'PLUGIN_TRACKER_FIELD_READ', $ugroup_id, $fake_object_id);
                            $add_read_to_history = true;
                        }
                    }
                }
            }

            //history
            if ($add_submit_to_history) {
                permission_add_history($group_id, 'PLUGIN_TRACKER_FIELD_SUBMIT', $fake_object_id);
            }
            if ($add_read_to_history) {
                permission_add_history($group_id, 'PLUGIN_TRACKER_FIELD_READ', $fake_object_id);
            }
            if ($add_update_to_history) {
                permission_add_history($group_id, 'PLUGIN_TRACKER_FIELD_UPDATE', $fake_object_id);
            }
            if (!$permissions_updated && ($add_submit_to_history || $add_read_to_history || $add_update_to_history)) {
                $permissions_updated = true;
            }
        }
    }
    return $permissions_updated;
    //$GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_userperms', 'perm_upd'));
}

function plugin_tracker_permission_get_input_value_from_permission($perm)
{
    $ret = false;
    switch ($perm) {
        case 'PLUGIN_TRACKER_FIELD_SUBMIT':
            $ret = array('submit' => 'on');
            break;
        case 'PLUGIN_TRACKER_FIELD_READ':
            $ret = array('others' => '0');
            break;
        case 'PLUGIN_TRACKER_FIELD_UPDATE':
            $ret = array('others' => '1');
            break;
        default:
            //Do nothing
            break;
    }
    return $ret;
}

/**
 * @returns array the permissions for the ugroups
 */
function plugin_tracker_permission_get_field_tracker_ugroups_permissions($group_id, $atid, $fields)
{
    $tracker_permissions = plugin_tracker_permission_get_tracker_ugroups_permissions($group_id, $atid);
    //Anonymous can access ?
    if (
        isset($tracker_permissions[$GLOBALS['UGROUP_ANONYMOUS']])
        && isset($tracker_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['permissions'])
        && count($tracker_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['permissions']) > 0
    ) {
        //Do nothing
    } else {
        //We remove the id
        if (isset($tracker_permissions[$GLOBALS['UGROUP_ANONYMOUS']])) {
            unset($tracker_permissions[$GLOBALS['UGROUP_ANONYMOUS']]);
        }

        //Registered can access ?
        if (
            isset($tracker_permissions[$GLOBALS['UGROUP_REGISTERED']])
            && isset($tracker_permissions[$GLOBALS['UGROUP_REGISTERED']]['permissions'])
            && count($tracker_permissions[$GLOBALS['UGROUP_REGISTERED']]['permissions']) > 0
        ) {
            //Do nothing
        } else {
            //We remove the id
            if (isset($tracker_permissions[$GLOBALS['UGROUP_REGISTERED']])) {
                unset($tracker_permissions[$GLOBALS['UGROUP_REGISTERED']]);
            }

            //Each group can access ?
            foreach ($tracker_permissions as $key => $value) {
                if (!isset($value['permissions']) || count($value['permissions']) < 1) {
                    unset($tracker_permissions[$key]);
                }
            }
        }
    }
    $ugroups_that_can_access_to_tracker = $tracker_permissions;

    $ugroups_permissions = array();
    foreach ($fields as $field) {
        $fake_id = $field->getID();
        $ugroups = permission_get_ugroups_permissions($group_id, $fake_id, array('PLUGIN_TRACKER_FIELD_READ','PLUGIN_TRACKER_FIELD_UPDATE','PLUGIN_TRACKER_FIELD_SUBMIT'), false);

        //{{{ We remove the ugroups which can't access to tracker and don't have permissions
        /*foreach($ugroups as $key => $value) {
            if (!isset($ugroups_that_can_access_to_tracker[$key]) && count($ugroups[$key]['permissions']) == 0) {
                unset($ugroups[$key]);
            }
        }*/
        //}}}

        //We store permission for the current field
        $ugroups_permissions[$field->getID()] = array(
                                                      'field' => array(
                                                                       'shortname'  => $field->getName(),
                                                                       'name'       => $field->getLabel() . ($field->isRequired() ? ' *' : ''),
                                                                       'id'         => $field->getID(),
                                                                       'field'      => $field,
                                                                       'link'       => '/tracker/admin/index.php?group_id=' . $group_id . '&atid=' . $atid . '&func=display_field_update&field_id=' . $field->getID()
                                                                       ),
                                                      'ugroups' => $ugroups
        );

        //{{{ We store tracker permissions
        foreach ($ugroups_permissions[$field->getID()]['ugroups'] as $key => $ugroup) {
            if (isset($tracker_permissions[$key])) {
                $ugroups_permissions[$field->getID()]['ugroups'][$key]['tracker_permissions'] = $tracker_permissions[$key]['permissions'];
            } else {
                $ugroups_permissions[$field->getID()]['ugroups'][$key]['tracker_permissions'] = array();
            }
        }
        //}}}
    }
    return $ugroups_permissions;
}

function plugin_tracker_permission_fetch_selection_field($permission_type, $object_id, $group_id, $html_name = "ugroups[]", $html_disabled = false, $selected = array())
{
    $html = '';
    // Get ugroups already defined for this permission_type
    if (empty($selected)) {
        $res_ugroups = permission_db_authorized_ugroups($permission_type, $object_id);
        $nb_set = db_numrows($res_ugroups);
    } else {
        $res_ugroups = $selected;
        $nb_set = count($res_ugroups);
    }
    // Now retrieve all possible ugroups for this project, as well as the default values
    $sql = "SELECT ugroup_id,is_default FROM permissions_values WHERE permission_type='$permission_type'";

    $res = db_query($sql);
    $predefined_ugroups = '';
    $default_values = array();
    if (db_numrows($res) < 1) {
        $html .= "<p><b>" . $GLOBALS['Language']->getText('global', 'error') . "</b>: " . $GLOBALS['Language']->getText('project_admin_permissions', 'perm_type_not_def', $permission_type);
        return $html;
    } else {
        while ($row = db_fetch_array($res)) {
            if ($predefined_ugroups) {
                $predefined_ugroups .= ' ,';
            }
            $predefined_ugroups .= $row['ugroup_id'];
            if ($row['is_default']) {
                $default_values[] = $row['ugroup_id'];
            }
        }
    }
    $sql = "SELECT * FROM ugroup WHERE group_id=" . $group_id . " OR ugroup_id IN (" . $predefined_ugroups . ") ORDER BY ugroup_id";
    $res = db_query($sql);

    $array = array();
    while ($row = db_fetch_array($res)) {
        $name = util_translate_name_ugroup($row[1]);
        $array[] = array(
            'value' => $row[0],
            'text' => $name
        );
    }

    if (empty($selected)) {
        if ($nb_set) {
            $res_ugroups = util_result_column_to_array($res_ugroups);
        } else {
            $res_ugroups = $default_values;
        }
    }
    $html .= html_build_multiple_select_box(
        //result
        $array,
        //name
        $html_name,
        //checked_array
        //($nb_set?util_result_column_to_array($res_ugroups):$default_values),
        $res_ugroups,
        //size
        8,
        //show_100
        false,
        //text_100
        util_translate_name_ugroup('ugroup_nobody_name_key'),
        //show_any
        false,
        //text_any
        '',
        //show_unchanged
        false,
        //text_unchanged
        '',
        //show_value=true
        false,
        //purify_level
        CODENDI_PURIFIER_CONVERT_HTML,
        //html_disabled
        $html_disabled
    );
    return $html;
}
