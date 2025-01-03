<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */


/**
 * Compute md5sum for frs file
 *
 */
class SystemEvent_COMPUTE_MD5SUM extends SystemEvent
{
    public function setLog(string $log): void
    {
        if (! isset($this->log) || $this->log == '') {
            $this->log = $log;
        } else {
            $this->log .= PHP_EOL . $log;
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
    public function verbalizeParameters($with_link)
    {
        $txt  = '';
        $txt .= 'File ID: #' . $this->getIdFromParam($this->parameters);
        return $txt;
    }

    /**
     * Process stored event
     *
     * @return bool
     */
    public function process()
    {
        $fileId = $this->getIdFromParam($this->parameters);
        if ($fileId > 0) {
            $fileFactory = $this->getFileFactory();
            $file        = $fileFactory->getFRSFileFromDb($fileId);
            $user        = $this->getUser($file->getUserID());

            $language = $this->getBaseLanguageFactory()->getBaseLanguage($user->getLocale());

            //Compute Md5sum for files
            $md5Computed = $this->computeFRSMd5Sum($file->getFileLocation());
            if (! $md5Computed) {
                $body = sprintf(_('Dear Files service user,

 An error occurs while trying to compute md5sum in your uploaded file %1$s.
Please try to upload it again.'), $file->getFileLocation());
                if (! $this->sendNotificationMail($user, $file, $body)) {
                    $this->error('Could not send mail to inform user that computing md5sum failed');
                    return false;
                }
                $this->error('Computing md5sum failed');
                return false;
            }
            // Update DB
            if (! $this->updateDB($fileId, $md5Computed)) {
                $this->error('Could not update the computed checksum for file (Filename: ' . $file->getFileName() . ')');
                return false;
            }

            //Compare file checksum
            $file = $fileFactory->getFRSFileFromDb($fileId);
            if (! $this->compareMd5Checksums($file)) {
                $body = sprintf(_('Dear Files service user,

The entered reference md5sum for the file %1$s differs from the computed one which equals = %2$s.
 Note that an error message will be shown each time you display the release content in the web interface.
If you consider that the upload has been well done, you can modify the value in the md5sum field by putting the right value.'), $file->getFileLocation(), $md5Computed);
                if (! $this->sendNotificationMail($user, $file, $body)) {
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
    public function computeFRSMd5Sum($filePath)
    {
        return hash_file('md5', $filePath);
    }

    /**
     * Inserts the computed md5sum for the uploaded files using ftp
     *
     * @param int $fileId
     * @param String  $md5Computed
     *
     * @return bool
     */
    public function updateDB($fileId, $md5Computed)
    {
        $fileFactory = $this->getFileFactory();
        return $fileFactory->updateComputedMd5sum($fileId, $md5Computed);
    }

    /**
     * Returns a FRSFileFactory
     *
     * @return FRSFileFactory
     */
    public function getFileFactory()
    {
        return new FRSFileFactory();
    }

    protected function getBaseLanguageFactory(): BaseLanguageFactory
    {
        return new BaseLanguageFactory();
    }

    /**
     * Manage the mail content and send it
     *
     * @param PFUser    $user
     * @param FRSFile $file
     * @param String  $body
     *
     * @return bool
     */
    protected function sendNotificationMail($user, $file, $body)
    {
        $mail =  new Codendi_Mail();

        $subject = ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME) . ' Error in ' . $file->getFileLocation();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setBcc($user->getEmail());
        $mail->setSubject($subject);
        $mail->setBodyText($body);
        return $mail->send();
    }

    /**
     * Make comparison between the computed and the reference md5sum
     *
     * @param FRSFile $file
     *
     * @return bool
     */
    public function compareMd5Checksums($file)
    {
        $fileFactory = $this->getFileFactory();
        return $fileFactory->compareMd5Checksums($file->getComputedMd5(), $file->getReferenceMd5());
    }
}
