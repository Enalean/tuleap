<?php
/**
 * Copyright Enalean (c) 2013-2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\DB\DataAccessObject;

/**
 * I'm responsible for interactions with the databse regarding Gerrit's templates
 */
class Git_Driver_Gerrit_Template_TemplateDao extends DataAccessObject
{
    public function getAllTemplatesOfProject($project_id)
    {
        $sql = 'SELECT *
                FROM plugin_git_gerrit_config_template
                WHERE group_id = ?';

        return $this->getDB()->run($sql, $project_id);
    }

    public function getTemplate($template_id)
    {
        $sql = 'SELECT *
                FROM plugin_git_gerrit_config_template
                WHERE id = ?';

        return $this->getDB()->row($sql, $template_id);
    }

    public function addTemplate($project_id, $name, $content)
    {
        $sql = 'INSERT INTO plugin_git_gerrit_config_template (
                    group_id,
                    name,
                    content
                ) VALUES (?, ?, ?)';

        try {
            $this->getDB()->run($sql, $project_id, $name, $content);
        } catch (PDOException $ex) {
            return false;
        }
        return true;
    }

    public function updateTemplate($template_id, $name, $content)
    {
        $sql = 'UPDATE plugin_git_gerrit_config_template
                SET name = ?, content = ?
                WHERE id = ?';

        try {
            $this->getDB()->run($sql, $name, $content, $template_id);
        } catch (PDOException $ex) {
            return false;
        }
        return true;
    }

    public function deleteTemplate($template_id)
    {
        $sql = 'DELETE FROM plugin_git_gerrit_config_template
                WHERE id = ?';

        try {
            $this->getDB()->run($sql, $template_id);
        } catch (PDOException $ex) {
            return false;
        }
        return true;
    }
}
