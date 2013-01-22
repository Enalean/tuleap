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
require_once 'OneStepCreationRequest.class.php';
require_once 'OneStepCreationValidator.class.php';
require_once 'common/project/CustomDescription/CustomDescriptionPresenter.class.php';

/**
 * Base controller for one step creation project
 */
class Project_OneStepCreation_OneStepCreationController extends MVC2_Controller {

    /** @var Project_OneStepCreation_OneStepCreationRequest */
    private $creation_request;

    /** @var Project_OneStepCreation_OneStepCreationPresenter */
    private $presenter;

    public function __construct(Codendi_Request $request) {
        parent::__construct('project', $request);

        $this->creation_request = new Project_OneStepCreation_OneStepCreationRequest($request);

        $this->presenter = new Project_OneStepCreation_OneStepCreationPresenter(
            $this->creation_request,
            $GLOBALS['LICENSE'],
            $this->getCustomDescriptions()
        );
    }

    /**
     * Display the create project form
     */
    public function index() {
        $GLOBALS['HTML']->header(array('title'=> $GLOBALS['Language']->getText('register_index','project_registration')));
        $this->render('register', $this->presenter);
        $GLOBALS['HTML']->footer(array());
    }

    /**
     * Create the project if request is valid
     */
    public function create() {
        $validator = new Project_OneStepCreation_OneStepCreationValidator($this->creation_request, $this->getCustomDescriptions());
        if (! $validator->validateAndGenerateErrors()) {
            $this->index();
        }

        $data = $this->creation_request->getProjectValues();
        $this->setDefaultTemplateIfNoOneHasBeenChoosen($data);
        $this->setServices($data);
        require_once 'www/project/create_project.php';
        create_project($data);
    }

    private function setDefaultTemplateIfNoOneHasBeenChoosen(array &$data) {
        //TODO: useless? What about Project_OneStepCreation_OneStepCreationPresenter::DEFAULT_TEMPLATE_ID
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

    // TODO: to be injected, avoid sql here
    private function getCustomDescriptions() {
        $required_custom_descriptions = array();
        $res = db_query('SELECT * FROM group_desc WHERE desc_required = 1 ORDER BY desc_rank');
        while ($row = db_fetch_array($res)) {
            $required_custom_descriptions[$row['group_desc_id']] = new Project_CustomDescription_CustomDescription(
                $row['group_desc_id'],
                $row['desc_name'],
                $row['desc_description'],
                $row['desc_required'],
                $row['desc_type'],
                $row['desc_rank']
            );
        }
        return $required_custom_descriptions;
    }
}
?>
