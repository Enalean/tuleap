<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'common/mvc2/Controller.class.php';
require_once 'OneStepCreationPresenter.class.php';
require_once 'common/project/CustomDescription/CustomDescriptionPresenter.class.php';

class Project_OneStepCreation_OneStepCreationController extends MVC2_Controller {

    public function __construct(Codendi_Request $request) {
        parent::__construct('project', $request);

        $required_custom_descriptions = array();
        $res = db_query('SELECT * FROM group_desc WHERE desc_required = 1 ORDER BY desc_rank');
        while ($row = db_fetch_array($res)) {
            $required_custom_descriptions[$row['group_desc_id']] = new Project_CustomDescription_CustomDescriptionPresenter(
                new Project_CustomDescription_CustomDescription(
                    $row['group_desc_id'],
                    $row['desc_name'],
                    $row['desc_description'],
                    $row['desc_required'],
                    $row['desc_type'],
                    $row['desc_rank']
                ),
                Project_OneStepCreation_OneStepCreationPresenter::PROJECT_DESCRIPTION_PREFIX
            );
        }
        $this->presenter = new Project_OneStepCreation_OneStepCreationPresenter(
            $request->params,
            $request->getCurrentUser(),
            $GLOBALS['LICENSE'],
            $required_custom_descriptions
        );
    }

    public function index() {
        $GLOBALS['HTML']->header(array('title'=> $GLOBALS['Language']->getText('register_index','project_registration')));
        $this->render('register', $this->presenter);
        $GLOBALS['HTML']->footer(array());
    }

    public function create() {
        $data = $this->presenter->getProjectValues();
        $this->setDefaultTemplateIfNoOneHasBeenChoosen($data);
        $this->setServices($data);
        require_once 'www/project/create_project.php';
        create_project($data);
    }

    private function setDefaultTemplateIfNoOneHasBeenChoosen(array &$data) {
        if (! isset($data['project']['built_from_template'])) {
            $default_templates = $this->presenter->getDefaultTemplates();
            $first_default_template = current($default_templates);
            $data['project']['built_from_template'] = $first_default_template->getGroupId();
        }
    }

    private function setServices(array &$data) {
        $project = ProjectManager::instance()->getProject($data['project']['built_from_template']);
        foreach($project->services as $service) {
            $id = $service->getId();
            $data['project']['services'][$id]['is_used'] = $service->isUsed();
        }
    }
}
?>
