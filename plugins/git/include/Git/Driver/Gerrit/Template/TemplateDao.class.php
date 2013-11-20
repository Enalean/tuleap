<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
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

/**
 * I'm responsible for interactions with the databse regarding Gerrit's templates
 */
class Git_Driver_Gerrit_Template_TemplateDao extends DataAccessObject {

    public function getAllTemplatesOfProject($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT *
                FROM plugin_git_gerrit_config_template
                WHERE group_id = $project_id";

        return $this->retrieve($sql);
    }

    public function getTemplate($template_id) {
        $template_id = $this->da->escapeInt($template_id);

        $sql = "SELECT *
                FROM plugin_git_gerrit_config_template
                WHERE id = $template_id";

        return $this->retrieve($sql);
    }

    public function addTemplate($project_id, $name, $content) {
        $project_id = $this->da->escapeInt($project_id);
        $name       = $this->da->quoteSmart($name);
        $content    = $this->da->quoteSmart($content);

        $sql = "INSERT INTO plugin_git_gerrit_config_template (
                    group_id,
                    name,
                    content
                ) VALUES (
                    $project_id,
                    $name,
                    $content
                )";

        return $this->update($sql);
    }

    public function updateTemplate($template_id, $name, $content) {
        $template_id = $this->da->escapeInt($template_id);
        $name        = $this->da->quoteSmart($name);
        $content     = $this->da->quoteSmart($content);

        $sql = "UPDATE plugin_git_gerrit_config_template
                SET name = $name, content = $content
                WHERE id = $template_id";

        return $this->update($sql);
    }
}
?>
