<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *
 *
 */

class BackendMailingList extends Backend
{

    protected $_mailinglistdao = null;

    /**
     * @return MailingListDao
     */
    protected function _getMailingListDao()
    {
        if (!$this->_mailinglistdao) {
            $this->_mailinglistdao = new MailingListDao(CodendiDataAccess::instance());
        }
        return $this->_mailinglistdao;
    }


    /**
     * Update mailman configuration for the given list
     * Write configuration in temporary file, and load it with mailman config_list tool
     * @return bool true on success, false otherwise
     */
    protected function updateListConfig($list)
    {
        // write configuration in temporary file
        $config_file = $GLOBALS['tmp_dir'] . "/mailman_config_" . $list->getId() . ".in";

        if ($fp = fopen($config_file, 'w')) {
            // Define encoding of this file for Python. See SR #764
            // Please note that this allows config_list to run with UTF-8 strings, but if the
            // description contains non-ascii chars, they will be displayed badly in mailman config web page.
            fwrite($fp, "# coding=UTF-8\n\n");
            // Deactivate monthly reminders by default
            fwrite($fp, "send_reminders = 0\n");
            // Setup the description
            fwrite($fp, "description = '" . addslashes($list->getDescription()) . "'\n");
            // Allow up to 200 kB messages
            fwrite($fp, "max_message_size = 200\n");

            if ($list->getIsPublic() == 0) { // Private lists
                // Don't advertise this list when people ask what lists are on this machine
                fwrite($fp, "advertised = False\n");
                // Private archives
                fwrite($fp, "archive_private = 1\n");
                // Subscribe requires approval
                fwrite($fp, "subscribe_policy = 2\n");
            }
            fclose($fp);

            if (
                system(
                    $GLOBALS['mailman_bin_dir'] . '/config_list -i ' . escapeshellarg($config_file) . ' ' . escapeshellarg($list->getListName())
                ) !== false
            ) {
                if (unlink($config_file)) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * Create new mailing list with mailman 'newlist' tool
     * then update the list configuration according to list settings
     * @return bool true on success, false otherwise
     */
    public function createList($group_list_id)
    {
        $dar = $this->_getMailingListDao()->searchByGroupListId($group_list_id);

        if ($row = $dar->getRow()) {
            $list = new MailingList($row);

            $list_admin = UserManager::instance()->getUserById($list->getListAdmin());
            $list_admin_email = $list_admin->getEmail();

            $list_dir = $GLOBALS['mailman_list_dir'] . "/" . $list->getListName();

            if ((! is_dir($list_dir)) && ($list->getIsPublic() != 9)) {
                // Create list
                system($GLOBALS['mailman_bin_dir'] . '/newlist -q ' . escapeshellarg($list->getListName()) . ' ' .
                    escapeshellarg($list_admin_email) . ' ' . escapeshellarg($list->getListPassword()) . ' >/dev/null');

                // Then update configuraion
                return $this->updateListConfig($list);
            }
        }
        return false;
    }

    /**
     * Delete mailing list
     * - list and archives are deleted
     * - backup first in temp directory
     * @return bool true on success, false otherwise
     */
    public function deleteList($group_list_id)
    {
        $dar = $this->_getMailingListDao()->searchByGroupListId($group_list_id);

        if ($row = $dar->getRow()) {
            $list = new MailingList($row);
            $list_dir = $GLOBALS['mailman_list_dir'] . "/" . $list->getListName();
            if ((is_dir($list_dir)) && ($list->getIsPublic() == 9)) {
                // Archive first
                $list_archive_dir = $GLOBALS['mailman_list_dir'] . "/../archives/private/" . $list->getListName(); // Does it work? TODO
                $backupfile = ForgeConfig::get('sys_project_backup_path') . "/" . $list->getListName() . "-mailman.tgz";
                system('tar cfz ' . escapeshellarg($backupfile) . ' ' . escapeshellarg($list_dir) . ' ' . escapeshellarg($list_archive_dir));
                chmod($backupfile, 0600);

                // Delete the mailing list if asked to and the mailing exists (archive deleted as well)
                system($GLOBALS['mailman_bin_dir'] . '/rmlist -a ' . escapeshellarg($list->getListName()) . ' >/dev/null');
                return $this->_getMailingListDao()->deleteListDefinitively($group_list_id);
            }
        }
        return false;
    }

    /**
     * Check if the list exists on the file system
     * @return bool true if list exists, false otherwise
     */
    public function listExists($list)
    {
        // Is this the best test?
        $list_dir = $GLOBALS['mailman_list_dir'] . "/" . $list->getListName();
        if (! is_dir($list_dir)) {
            return false;
        }
        return true;
    }

    /**
     * Archive all project mailing lists
     *
     * @param int $projectId id of the project
     *
     * @return bool
     */
    public function deleteProjectMailingLists($projectId)
    {
        $deleteStatus = true;
        $res = $this->_getMailingListDao()->searchByProject($projectId);
        if ($res && !$res->isError()) {
            while ($row = $res->getRow()) {
                if ($this->_getMailingListDao()->deleteList($row['group_list_id'])) {
                    $deleteStatus = $this->deleteList($row['group_list_id']) && $deleteStatus;
                } else {
                    $deleteStatus = false;
                }
            }
            return $deleteStatus;
        }
        return false;
    }
}
