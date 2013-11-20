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
 * I'm responsible for instanciating Gerrit's Templates
 */
class Git_Driver_Gerrit_Template_TemplateFactory {

    /** @var Git_Driver_Gerrit_Template_TemplateDao */
    private $dao;

    public function __construct(Git_Driver_Gerrit_Template_TemplateDao $template_dao) {
        $this->$dao = $template_dao;
    }

    /**
     * Get all templates of a project
     *
     * @param Project
     * @return Git_Driver_Gerrit_Template_Template[]
     */
    public function getAllTemplatesOfProject(Project $project) {
        $templates = $this->dao->getAllTemplatesOfProject($project->getId())
            ->instanciateWith(array($this, 'instantiateTemplateFromRow'));

        return $templates;
    }

    /**
     * Instatiate a Template from a SQL row
     *
     * @param array
     * @return Git_Driver_Gerrit_Template_Template
     */
    public function instantiateTemplateFromRow(array $row) {
        return new Git_Driver_Gerrit_Template_Template(
            $row['id'],
            $row['group_id'],
            $row['name'],
            $row['content']
        );
    }
}
?>
