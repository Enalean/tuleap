<?php
/**
 * Copyright (c) Enalean SAS - 2014. All rights reserved
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

class b201407081620_replace_mediwiki_apache_conf extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Replace mediawiki apache conf
EOT;
    }

    /**
     * @return void
     */
    public function up()
    {
        $config_file = '/etc/httpd/conf.d/tuleap-plugins/mediawiki.conf';
        if (file_exists($config_file)) {
            exec("mv $config_file $config_file.backup");
            exec("cp /usr/share/codendi/plugins/mediawiki/etc/mediawiki.conf.dist $config_file");
        }
    }
}
