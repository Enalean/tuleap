<?php

require_once('common/dao/ArtifactGlobalNotificationDao.class.php');
require_once('ArtifactGlobalNotification.class.php');

/**
* ArtifactGlobalNotificationFactory
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class ArtifactGlobalNotificationFactory {
    
    function getGlobalNotificationsForTracker($tracker_id) {
        $notifs = array();
        $dao =& new ArtifactGlobalNotificationDao(CodeXDataAccess::instance());
        $dar =& $dao->searchByTrackerId($tracker_id);
        if ($dar) {
            $notifs = array();
            while($dar->valid()) {
                $row = $dar->current();
                $notifs[$row['id']] =& new ArtifactGlobalNotification($row);
                $dar->next();
            }
        }
        return $notifs;
    }
    
    function addGlobalNotificationForTracker($tracker_id) {
        $dao =& new ArtifactGlobalNotificationDao(CodeXDataAccess::instance());
        return $dao->create($tracker_id, '', 0, 1);
    }
    function removeGlobalNotificationForTracker($global_notification_id, $tracker_id) {
        $dao =& new ArtifactGlobalNotificationDao(CodeXDataAccess::instance());
        return $dao->delete($global_notification_id, $tracker_id);
    }
    function updateGlobalNotification($global_notification_id, $data) {
        $dao =& new ArtifactGlobalNotificationDao(CodeXDataAccess::instance());
        $feedback = '';
        $arr_email_address = split('[,;]', $data['addresses']);
        if (!util_validateCCList($arr_email_address, $feedback, false)) {
          $GLOBALS['Response']->addFeedback('error', $feedback);
        } else {
          $data['addresses'] = util_cleanup_emails(implode(', ', $arr_email_address));
          return $dao->modify($global_notification_id, $data);
        }
        return false;
    }
    function getAllAddresses($tracker_id, $update = true) {
        $addresses = array();
        $notifs = $this->getGlobalNotificationsForTracker($tracker_id);
        foreach($notifs as $key => $nop) {
            if (!$update || $notifs[$key]->isAllUpdates()) {
                foreach(split('[,;]', $notifs[$key]->getAddresses()) as $address) {
                    $addresses[] = array('address' => $address, 'check_permissions' => $notifs[$key]->isCheckPermissions());
                }
            }
        }
        return $addresses;
    }
}
?>
