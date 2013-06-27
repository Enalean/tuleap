<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
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

require_once 'common/system_event/SystemEvent.class.php';

/**
 * Compute md5sum for frs file
 *
 */
class SystemEvent_COMPUTE_MD5SUM extends SystemEvent {

    /**
     * Set multiple logs
     *  
     * @param String $log Log string
     * 
     * @return void
     */
    public function setLog($log) {
        if (!isset($this->log) || $this->log == '') {
            $this->log = $log;
        } else {
            $this->log .= PHP_EOL.$log;
        }
    }

    /**
     * Verbalize the parameters so they are readable and much user friendly in 
     * notifications
     * 
     * @param bool $with_link true if you want links to entities. The returned 
     * string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link) {
        $txt = '';
        $txt .= 'File ID: #'. $this->getIdFromParam($this->parameters);
        return $txt;
    }

    /** 
     * Process stored event
     * 
     * @return Boolean
     */
    public function process() {
        $fileId = $this->getIdFromParam($this->parameters);
        if ($fileId > 0) {
            $fileFactory = $this->getFileFactory();
            $file        = $fileFactory->getFRSFileFromDb($fileId);
            $user = $this->getUser($file->getUserID());

            //Compute Md5sum for files
            $md5Computed = $this->computeFRSMd5Sum($file->getFileLocation());
            if (!$md5Computed) {
                if (!$this->sendNotificationMail($user, $file, 'md5_compute_error', array($file->getFileLocation()))) {
                    $this->error('Could not send mail to inform user that computing md5sum failed');
                    return false;
                }
                $this->error('Computing md5sum failed ');
                return false;
            }
            // Update DB
            if (!$this->updateDB($fileId, $md5Computed)) {
                $this->error('Could not update the computed checksum for file (Filename: '.$file->getFileName().')');
                return false;
            }

            //Compare file checksum
            $file = $fileFactory->getFRSFileFromDb($fileId);
            if (!$this->compareMd5Checksums($file)) {
                if (!$this->sendNotificationMail($user, $file, 'md5_compare_error', array($file->getFileLocation(), $md5Computed))) {
                    $this->error('Could not send mail to inform user that comparing md5sum failed');
                    return false;
                }
            }
            $this->done();
            return true;
        }
    }
    /**
     * Computes the md5sum for a given file
     * 
     * @param String $filePath
     *
     * @return String
     */
    public function computeFRSMd5Sum($filePath) {
        return PHP_BigFile::getMd5Sum($filePath);
    }
    /**
     * Inserts the computed md5sum for the uploaded files using ftp
     * 
     * @param Integer $fileId
     * @param String  $md5Computed
     *
     * @return Boolean
     */
    public function updateDB($fileId, $md5Computed) {
        $fileFactory = $this->getFileFactory();
        return $fileFactory->updateComputedMd5sum($fileId, $md5Computed);
    }

    /**
     * Returns a FRSFileFactory
     *
     * @return FRSFileFactory
     */
    function getFileFactory() {
        return new FRSFileFactory();
    }
    /**
     * Manage the mail content and send it
     * 
     * @param PFUser    $user
     * @param FRSFile $file
     * @param String  $bodyContent
     * @param Array   $option
     * 
     * @return Boolean
     */
    function sendNotificationMail($user, $file, $bodyContent, $option) {
        
        $mail =  new Mail();
        
        $language = new BaseLanguage($GLOBALS['sys_supported_languages'], $GLOBALS['sys_lang']);
        $language->loadLanguage($user->getLanguageID());
        
        $subject = $GLOBALS['sys_name'] . ' Error in '.$file->getFileLocation();
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->setBcc($user->getEmail());
        $mail->setSubject($subject);
        $mail->setBody($language->getText('mail_system_event', $bodyContent, $option));
        return $mail->send();
    }
    
    /**
     * Make comparison between the computed and the reference md5sum
     * 
     * @param FRSFile $file
     * 
     * @return Boolean 
     */
    function compareMd5Checksums($file) {
        $fileFactory = $this->getFileFactory();
        return $fileFactory->compareMd5Checksums($file->getComputedMd5(), $file->getReferenceMd5());
    }
}

?>
