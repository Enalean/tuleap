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

class b201203291146_delete_deprecated_doc extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return <<<EOT
Remove deprecated documentation and update url
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "UPDATE plugin_docman_item 
                SET link_url = '/documentation/cli/html/fr_FR/CLI.html'
                WHERE link_url = '/documentation/cli/html/fr_FR/Codendi_CLI.html'";

        $res = $this->db->dbh->exec($sql);

        $sql = "UPDATE plugin_docman_item 
                SET link_url = '/documentation/cli/html/en_US/CLI.html'
                WHERE link_url = '/documentation/cli/html/en_US/Codendi_CLI.html'";

        $res = $this->db->dbh->exec($sql);

        $sql = "UPDATE plugin_docman_item 
                SET delete_date = UNIX_TIMESTAMP(NOW())
                WHERE link_url IN ('/plugins/eclipse/documentation/doc/help/pdf/Codendi_Eclipse_Plugin_User_Guide.pdf',
                                   '/plugins/eclipse/documentation/doc/help/html/Codendi_Eclipse_Plugin_User_Guide.html',
                                   '/plugins/eclipse/documentation/nl/fr/FR/doc/help/pdf/Guide_Utilisateur_Plugin_Eclipse_Codendi.pdf',
                                   '/plugins/eclipse/documentation/nl/fr/FR/doc/help/html/Codendi_Eclipse_Plugin_User_Guide.html',
                                   '/plugins/eclipse/documentation/doc/help/html/index.html',
                                   '/plugins/eclipse/documentation/nl/fr/FR/doc/help/html/index.html')
                    AND delete_date IS NULL";

        $res = $this->db->dbh->exec($sql);
    }
}
