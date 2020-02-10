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

/**
 * I'm responsible for instanciating Gerrit's Templates
 */
class Git_Driver_Gerrit_Template_TemplateFactory
{

    /** @var Git_Driver_Gerrit_Template_TemplateDao */
    private $dao;

    public function __construct(Git_Driver_Gerrit_Template_TemplateDao $template_dao)
    {
        $this->dao = $template_dao;
    }

    /**
     * Get all templates of a project
     *
     * @return Git_Driver_Gerrit_Template_Template[]
     */
    public function getAllTemplatesOfProject(Project $project)
    {
        $templates      = array();
        $templates_rows = $this->dao->getAllTemplatesOfProject($project->getId());

        foreach ($templates_rows as $row) {
            $templates[] = $this->instantiateTemplateFromRow($row);
        }

        return $templates;
    }


    /**
     * Get All templates for a repository
     *
     * @param GitRepository the concerned repo
     *
     * @return Git_Driver_Gerrit_Template_Template[] the templates
     */
    public function getTemplatesAvailableForRepository(GitRepository $repository)
    {
        $current_project = $repository->getProject();

        return $this->getTemplatesAvailableForProject($current_project);
    }

    /**
     * Get All templates for a project (and projects higher in hierarchy)
     *
     * @param GitRepository the concerned repo
     *
     * @return Git_Driver_Gerrit_Template_Template[] the templates
     */
    public function getTemplatesAvailableForProject(Project $project)
    {
        if ($project->isError()) {
            return array();
        }

        $templates = array_merge(
            $this->getAllTemplatesOfProject($project),
            $this->getTemplatesAvailableForParentProjects($project)
        );

        return $templates;
    }

    /**
     * Get All templates for projects higher in hierarchy. Does not include
     * templates for project itself.
     *
     * @return Git_Driver_Gerrit_Template_Template[]
     */
    public function getTemplatesAvailableForParentProjects(Project $project)
    {
        if ($project->isError()) {
            return array();
        }

        $templates       = array();
        $project_manager = ProjectManager::instance();
        $projects        = $project_manager->getAllParentsProjects($project->getId());

        foreach ($projects as $project) {
            $templates = array_merge($templates, $this->getAllTemplatesOfProject($project));
        }

        return $templates;
    }

    /**
     *
     * @param int $template_id
     * @return Git_Driver_Gerrit_Template_Template
     * @throws Git_Template_NotFoundException
     */
    public function getTemplate($template_id)
    {
        $row = $this->dao->getTemplate($template_id);
        if (empty($row)) {
            throw new Git_Template_NotFoundException($template_id);
        }
        return $this->instantiateTemplateFromRow($row);
    }

    /**
     * Instatiate a Template from a SQL row
     *
     * @param array $row
     * @return Git_Driver_Gerrit_Template_Template -where the array is in DAR format
     */
    private function instantiateTemplateFromRow(array $row)
    {
        return new Git_Driver_Gerrit_Template_Template(
            $row['id'],
            $row['group_id'],
            $row['name'],
            $row['content']
        );
    }

    /**
     * @return bool
     */
    public function updateTemplate(Git_Driver_Gerrit_Template_Template $template)
    {
        return $this->dao->updateTemplate($template->getId(), $template->getName(), $template->getContent());
    }

    /**
     *
     * @param int $project_id
     * @param string $template_content
     * @param string $template_name
     * @return bool
     */
    public function createTemplate($project_id, $template_content, $template_name)
    {
        return $this->dao->addTemplate($project_id, $template_name, $template_content);
    }

    /**
     * @param int $template_id
     *
     * @return bool
     */
    public function deleteTemplate($template_id)
    {
        return $this->dao->deleteTemplate($template_id);
    }
}
