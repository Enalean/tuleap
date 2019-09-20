<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class b201403211644_update_default_acl extends ForgeUpgrade_Bucket
{

    public const TULEAP_SECURE_FTP_CONFIG_FILE_CENTOS5  = '/etc/codendi/plugins/proftpd/etc/config.inc';
    public const TULEAP_SECURE_FTP_CONFIG_FILE_OTHER_OS = '/etc/tuleap/plugins/proftpd/etc/config.inc';

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Update default ACL for the secure FTPs
EOT;
    }

    /**
     * Adding the data
     *
     * @return void
     */
    public function up()
    {
        $proftpd_base_directory = $this->getProftpdBaseDirectoryPath();
        $iterator               = new DirectoryIterator($proftpd_base_directory);

        foreach ($iterator as $folder) {
            if (! $folder->isDot()) {
                exec('setfacl -m u:codendiadm:rwx ' . $proftpd_base_directory . '/' . $folder->getFilename());
            }
        }
    }

    private function getProftpdBaseDirectoryPath()
    {
        if (is_file(self::TULEAP_SECURE_FTP_CONFIG_FILE_CENTOS5)) {
            include(self::TULEAP_SECURE_FTP_CONFIG_FILE_CENTOS5);
            return $proftpd_base_directory;
        }

        if (is_file(self::TULEAP_SECURE_FTP_CONFIG_FILE_OTHER_OS)) {
            include(self::TULEAP_SECURE_FTP_CONFIG_FILE_OTHER_OS);
            return $proftpd_base_directory;
        }

        $this->log->warn('Config file for Proftpd not found');
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
            'Config file ' . self::TULEAP_SECURE_FTP_CONFIG_FILE_CENTOS5 . ' or ' .
            self::TULEAP_SECURE_FTP_CONFIG_FILE_OTHER_OS . ' does not exist. Exiting upgrade.'
        );
    }
}
