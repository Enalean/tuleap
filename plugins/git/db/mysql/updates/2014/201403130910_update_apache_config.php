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

class b201403130910_update_apache_config extends ForgeUpgrade_Bucket
{
    public const BACKUP_FILE = '/etc/httpd/conf.d/codendi_aliases.conf_b201403130910_update_apache_config';
    public const CONFIG_FILE = '/etc/httpd/conf.d/codendi_aliases.conf';

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Update Apache config to allow Rewrite rules
EOT;
    }

    /**
     * Adding the data
     *
     * @return void
     */
    public function up()
    {
        if (file_exists(self::BACKUP_FILE)) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Backup file ' . self::BACKUP_FILE . ' already exists please save it or remove it first');
        }
        if (! copy(self::CONFIG_FILE, self::BACKUP_FILE)) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to backup config file: ' . self::CONFIG_FILE);
        }
        $this->patchConfig();
    }

    private function patchConfig()
    {
        $in_block    = false;
        $first_match = false;
        $had_match   = false;

        $source_lines = file(self::CONFIG_FILE, FILE_IGNORE_NEW_LINES);
        if ($source_lines === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to read config file: ' . self::CONFIG_FILE);
        }
        $target_lines = array();
        foreach ($source_lines as $line) {
            if ($line == '<DirectoryMatch "/usr/share/codendi/plugins/([^/]*)/www/">') {
                $app_name    = 'codendi';
                $in_block    = true;
                $first_match = true;
            }
            if ($line == '<DirectoryMatch "/usr/share/tuleap/plugins/([^/]*)/www/">') {
                $app_name    = 'tuleap';
                $in_block    = true;
                $first_match = true;
            }
            if ($first_match) {
                $had_match = true;
                $target_lines[] = '<Directory "/usr/share/' . $app_name . '/plugins/*/www/">';
                $target_lines[] = '    Options MultiViews FollowSymlinks';
                $target_lines[] = '    AllowOverride All';
                $target_lines[] = '    Order allow,deny';
                $target_lines[] = '    Allow from all';
                $target_lines[] = '</Directory>';
            }
            if ($in_block) {
                if ($line == '</DirectoryMatch>') {
                    $in_block = false;
                }
            } else {
                $target_lines[] = $line;
            }
            $first_match = false;
        }

        if ($had_match) {
            $content = implode(PHP_EOL, $target_lines);
            $written = file_put_contents(self::CONFIG_FILE, $content);
            if ($written !== strlen($content)) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to write config file: ' . self::CONFIG_FILE);
            }
        } else {
            $this->log->warn(self::CONFIG_FILE . ' was not modified by bucket. If it was already patched it\'s ok, otherwise you should check it manually. See plugins/git/README.txt for reference.');
        }
    }
}
