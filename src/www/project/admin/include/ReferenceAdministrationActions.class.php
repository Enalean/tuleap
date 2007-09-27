<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * ReferenceAdministrationActions
 */
require_once('common/mvc/Actions.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/include/ReferenceManager.class.php');

class ReferenceAdministrationActions extends Actions {
    
    function ReferenceAdministrationActions(&$controler, $view=null) {
        $this->Actions($controler);
    }
    
    /** Actions **/
    
    // Create a new reference
    function do_create() {
        global $feedback;
        $request =& HTTPRequest::instance();
        // Sanity check
        if ((!$request->get('group_id'))
            || (!$request->get('keyword'))
            || (!$request->get('link'))) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('project_reference','missing_parameter'));
        }
        $force=$request->get('force');
        if (!user_is_super_user()) $force=false;

        $reference_manager =& ReferenceManager::instance();
        if ($request->get('service_short_name') == 100) { // none
            $service_short_name="";
        } else $service_short_name=$request->get('service_short_name');
        $ref=& new Reference(0,
                             $request->get('keyword'),
                             $request->get('description'),
                             $request->get('link'),
                             $request->get('scope'),
                             $service_short_name,
                             $request->get('is_used'),
                             $request->get('group_id'));
        if ( ($ref->getGroupId()==100) && ($ref->isSystemReference())) {
            // Add reference to ALL active projects!
            $result=$reference_manager->createSystemReference($ref,$force);
            if (!$result) {
                exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('project_reference','create_fail'));
            } else {
                $feedback .= " ".$GLOBALS['Language']->getText('project_reference','system_r_create_success');
            }
        } else {
            $result=$reference_manager->createReference($ref,$force);
            if (!$result) {
                exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('project_reference','create_fail'));
            } else {
                $feedback .= " ".$GLOBALS['Language']->getText('project_reference','r_create_success')." ";
            }

        }


    }

    // Edit an existing reference
    function do_edit() {
        global $feedback;
        $request =& HTTPRequest::instance();
        // Sanity check
        if ((!$request->get('group_id'))
            || (!$request->get('reference_id'))) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('project_reference','missing_parameter'));
        }
        $reference_manager =& ReferenceManager::instance();

        $force=$request->get('force');
        $su=false;
        if (user_is_super_user()) {
            $su=true;
        } else $force=false;

        // Load existing reference from DB
        $ref=& $reference_manager->loadReference($request->get('reference_id'),$request->get('group_id'));


        if (($ref->isSystemReference())&&($ref->getGroupId()!=100)) {
            // Only update is_active field
            if ($ref->isActive() != $request->get('is_used')) {
                $reference_manager->updateIsActive($ref,$request->get('is_used'));
            }
        } else {
            if (!$su) {
                // Only a server admin may define a service_id
                $service_short_name="";
            } else {
                if ($request->get('service_short_name') == 100) { // none
                    $service_short_name="";
                } else { $service_short_name=$request->get('service_short_name');}
            }

            $new_ref=& new Reference($request->get('reference_id'),
                                     $request->get('keyword'),
                                     $request->get('description'),
                                     $request->get('link'),
                                     $ref->getScope(), # Can't edit a ref scope
                                     $service_short_name,
                                     $request->get('is_used'),
                                     $request->get('group_id'));
            $result=$reference_manager->updateReference($new_ref,$force);
            if (!$result) {
                exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('project_reference','edit_fail',db_error()));
            } 
        }
    }

    // Delete a reference. 
    // If it is shared by several projects, only delete the reference_group entry. 
    // WARNING: If it is a system reference, delete all occurences of the reference!
    function do_delete() {
        global $feedback;
        $request =& HTTPRequest::instance();
        // Sanity check
        if ((!$request->get('group_id'))
            || (!$request->get('reference_id'))) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('project_reference','missing_parameter'));
        }
        $reference_manager =& ReferenceManager::instance();
        // Load existing reference from DB
        $ref=& $reference_manager->loadReference($request->get('reference_id'),$request->get('group_id'));

        if (!$ref) {
            // Already deleted? User reloaded a page?
            return;
        }

        // WARNING: If it is a system reference, delete all occurences of the reference!
        if ($ref->isSystemReference()) {
            $result=$reference_manager->deleteSystemReference($ref);
            if ($result) {
                $feedback .= " ".$GLOBALS['Language']->getText('project_reference','sr_deleted')." ";
            }
        } else {
            $result=$reference_manager->deleteReference($ref);
            if ($result) {
                $feedback .= " ".$GLOBALS['Language']->getText('project_reference','r_deleted')." ";
            }
        }
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('project_reference','del_fail',db_error()));
        } 
    }

}


?>
