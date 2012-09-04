<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('FRSFile.class.php');
require_once('common/dao/FileModuleMonitorDao.class.php');

/**
 * 
 */
class FileModuleMonitorFactory {

    var $dao;

    function whoIsMonitoringPackageById($group_id, $package_id) {
        $_group_id   = (int) $group_id;
        $_package_id = (int) $package_id;

        $dao = $this->_getFileModuleMonitorDao();
        $dar = $dao->whoIsMonitoringPackageByID($group_id, $package_id);
        if ($dar->isError()) {
            return;
        }
        
        if (!$dar->valid()) {
            return;
        }
        
        $data_array = array();
        while ($dar->valid()) {
            $data_array[] = $dar->current();
            $dar->next();
        }
        return $data_array;
    }

    /**
     * Get the list of users publicly monitoring a package
     *
     * @param Integer $packageId Id of the package
     *
     * @return DataAccessResult
     */
    function whoIsPubliclyMonitoringPackage($packageId) {
        $dao    = $this->_getFileModuleMonitorDao();
        $dar    = $dao->whoIsPubliclyMonitoringPackage($packageId);
        $result = array();
        if ($dar && !$dar->isError()) {
            $result = $dar;
        }
        return $result;
    }

    function getFilesModuleMonitorFromDb($id) {
        $_id = (int) $id;
        $dao = $this->_getFileModuleMonitorDao();
        $dar = $dao->searchById($_id);
        

        $data_array = array();
        if (!$dar->isError() && $dar->valid()) {
            while ($dar->valid()) {
                $data_array[] = $dar->current();
                $dar->next();
            }
        }
        return $data_array;
    }
    
    /**
     * Is the user in the list of people monitoring this package.
     *
     * @param Integer $filemodule_id Id of the package
     * @param User    $user          The user
     * @param Boolean $publicly      If true check if the user is monitoring publicly
     *
     * @return Boolean is_monitoring
     */
    function isMonitoring($filemodule_id, User $user = null, $publicly = false) {
        $_filemodule_id = (int) $filemodule_id;
        if (!$user) {
            $user = UserManager::instance()->getCurrentUser();
        }
        $dao = $this->_getFileModuleMonitorDao();
        $dar = $dao->searchMonitoringFileByUserAndPackageId($_filemodule_id, $user, $publicly);

        if ($dar->isError()) {
            return;
        }


        if (!$dar->valid() || $dar->rowCount() < 1) {
            return false;
        } else {
            return true;
        }
    }

    function _getFileModuleMonitorDao() {
        if (!$this->dao) {
            $this->dao = new FileModuleMonitorDao(CodendiDataAccess :: instance());
        }
        return $this->dao;
    }

    /**
     * Set package monitoring
     *
     * @param Integer $filemodule_id Id of the package
     * @param User    $user          The user
     * @param Boolean $anonymous     True if the user want to monitor the package anonymously
     *
     * @return DataAccessResult
     */
    function setMonitor($filemodule_id, User $user, $anonymous = true) {
        $dao = $this->_getFileModuleMonitorDao();
        $res = $dao->create($filemodule_id, $user, $anonymous);
        return $res;
    }

    /**
     * Stop the package monitoring
     *
     * @param Integer $filemodule_id Id of th package
     * @param User    $user          The user
     * @param Boolean $onlyPublic    If true delete only user publicly monitoring the package
     *
     * @return Boolean
     */
    function stopMonitor($filemodule_id, User $user, $onlyPublic = false) {
        $_id = (int) $filemodule_id;
        $dao = $this->_getFileModuleMonitorDao();
        return $dao->delete($_id, $user, $onlyPublic);
    }

    /**
     * Prepare mail
     *
     * @param FRSPackage $package Id of th package
     * @param User       $user    The deleted user
     *
     * @return Codendi_Mail
     */
    function prepareMail(FRSPackage $package, User $user) {
        $subject   = $GLOBALS['Language']->getText('file_filemodule_monitor', 'mail_subject', array($GLOBALS['sys_name'], $package->getName()));
        $mail      = new Codendi_Mail();
        $mail->getLookAndFeelTemplate()->set('title', $subject);
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->setTo($user->getEmail());
        $mail->setSubject($subject);
        return $mail;
    }

    /**
     * Notify after adding monitoring for a user
     *
     * @param FRSPackage $package Id of th package
     * @param User       $user    The added user
     *
     * @return Boolean
     */
    function notifyAfterAdd(FRSPackage $package, User $user) {
        $mailMgr   = new MailManager();
        $mailPrefs = $mailMgr->getMailPreferencesByUser($user);
        $mail      = $this->prepareMail($package, $user);
        if ($mailPrefs == Codendi_Mail_Interface::FORMAT_HTML) {
            $htmlBody = $GLOBALS['Language']->getText('file_filemodule_monitor', 'add_monitor_mail');
            $htmlBody .= ' <a href="'.get_server_url().'/file/showfiles.php?group_id='.$package->getGroupID().'&package_id='.$package->getPackageID().'" >'.$package->getName().'</a>';
            $htmlBody .= '<br /><br /><a href="'.get_server_url().'/file/filemodule_monitor.php?group_id='.$package->getGroupID().'&filemodule_id='.$package->getPackageID().'" >'.$GLOBALS['Language']->getText('file_showfiles', 'stop_monitoring').'</a>';
            $mail->setBodyHtml($htmlBody);
        }
        $txtBody = $GLOBALS['Language']->getText('file_filemodule_monitor', 'add_monitor_mail').' "'.$package->getName().'" : ';
        $txtBody .= get_server_url().'/file/showfiles.php?group_id='.$package->getGroupID().'&package_id='.$package->getPackageID();
        $txtBody .= "\n\n".$GLOBALS['Language']->getText('file_showfiles', 'stop_monitoring').': ';
        $txtBody .= get_server_url().'/file/filemodule_monitor.php?group_id='.$package->getGroupID().'&filemodule_id='.$package->getPackageID();
        $mail->setBodyText($txtBody);
        return $mail->send();
    }

    /**
     * Notify after deleting monitoring for a user
     *
     * @param FRSPackage $package Id of th package
     * @param User       $user    The deleted user
     *
     * @return Boolean
     */
    function notifyAfterDelete(FRSPackage $package, User $user) {
        $mailMgr   = new MailManager();
        $mailPrefs = $mailMgr->getMailPreferencesByUser($user);
        $mail      = $this->prepareMail($package, $user);
        if ($mailPrefs == Codendi_Mail_Interface::FORMAT_HTML) {
            $htmlBody = $GLOBALS['Language']->getText('file_filemodule_monitor', 'delete_monitor_mail');
            $htmlBody .= ' <a href="'.get_server_url().'/file/showfiles.php?group_id='.$package->getGroupID().'&package_id='.$package->getPackageID().'" >'.$package->getName().'</a>';
            $htmlBody .= '<br /><br /><a href="'.get_server_url().'/file/filemodule_monitor.php?group_id='.$package->getGroupID().'&filemodule_id='.$package->getPackageID().'" >'.$GLOBALS['Language']->getText('file_showfiles', 'start_monitoring').'</a>';
            $mail->setBodyHtml($htmlBody);
        }
        $txtBody = $GLOBALS['Language']->getText('file_filemodule_monitor', 'delete_monitor_mail').' "'.$package->getName().'" : ';
        $txtBody .= get_server_url().'/file/showfiles.php?group_id='.$package->getGroupID().'&package_id='.$package->getPackageID();
        $txtBody .= "\n\n".$GLOBALS['Language']->getText('file_showfiles', 'start_monitoring').': ';
        $txtBody .= get_server_url().'/file/filemodule_monitor.php?group_id='.$package->getGroupID().'&filemodule_id='.$package->getPackageID();
        $mail->setBodyText($txtBody);
        return $mail->send();
    }

}

?>