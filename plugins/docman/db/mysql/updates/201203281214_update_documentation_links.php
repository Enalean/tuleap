<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201203281214_update_documentation_links extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return <<<EOT
Update Tuleap Documentation url and remove documentation in pdf
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "UPDATE plugin_docman_item 
                SET link_url = '/documentation/user_guide/html/fr_FR/User_Guide.html'
                WHERE link_url = '/documentation/user_guide/html/fr_FR/Codendi_User_Guide.html'";

        $res = $this->db->dbh->exec($sql);

        $sql = "UPDATE plugin_docman_item 
                SET link_url = '/documentation/user_guide/html/en_US/User_Guide.html'
                WHERE link_url = '/documentation/user_guide/html/en_US/Codendi_User_Guide.html'";

        $res = $this->db->dbh->exec($sql);

        $sql = "UPDATE plugin_docman_item 
                SET delete_date = UNIX_TIMESTAMP(NOW())
                WHERE link_url IN ('/documentation/user_guide/pdf/fr_FR/Codendi_User_Guide.pdf',
                                   '/documentation/user_guide/pdf/en_US/Codendi_User_Guide.pdf',
                                   '/documentation/cli/pdf/en_US/Codendi_CLI.pdf',
                                   '/documentation/cli/pdf/fr_FR/Codendi_CLI.pdf')
                    AND delete_date IS NULL";

        $res = $this->db->dbh->exec($sql);
    }
}
