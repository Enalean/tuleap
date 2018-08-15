<?php
/**
  * Copyright (c) Enalean, 2013 - 2018. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

/**
 * Wraps user request for one step creation form
 */
class Project_OneStepCreation_OneStepCreationRequest {

    /**
     *
     * @var string
     */
    private $full_name;

    /**
     *
     * @var string
     */
    private $short_description;

    /**
     *
     * @var string
     */
    private $unix_name;

    /**
     *
     * @var bool
     */
    private $is_public;

    /**
     *
     * @var bool
     */
    private $user_can_choose_project_privacy;

    /**
     *
     * @var int
     */
    private $templateId;

    /**
     *
     * @var bool
     */
    private $term_of_service_approval;

    /**
     *
     * @var bool
     */
    private $is_valid = true;

    /**
     *
     * @var string
     */
    private $form_submission_path;

    /**
     * @var array
     */
    private $custom_descriptions = array();

    /** @var ProjectManager */
    private $project_manager;

    /** @var Codendi_Request */
    private $request;

    /**
     * @var array
     */
    private $trove_cats = [];

    public function __construct(Codendi_Request $request, ProjectManager $project_manager) {
        $this->request                         = $request;
        $this->project_manager                 = $project_manager;
        $this->is_public                       = $GLOBALS['sys_is_project_public'];
        $this->user_can_choose_project_privacy = ForgeConfig::get('sys_user_can_choose_project_privacy');
        $request_data                          = $request->params;
        $this->setFullName($request_data)
            ->setUnixName($request_data)
            ->setShortDescription($request_data)
            ->setIsPublic($request_data)
            ->setTemplateId($request_data)
            ->setTosApproval($request_data)
            ->setCustomDescriptions($request_data)
            ->setTroveCats($request_data);
    }

    /**
     *
     * @return array
     */
    public function getProjectValues() {
        return array(
            'project' => array_merge(
                array(
                    Project_OneStepCreation_OneStepCreationPresenter::FULL_NAME                       => $this->getFullName(),
                    Project_OneStepCreation_OneStepCreationPresenter::IS_PUBLIC                       => $this->isPublic(),
                    Project_OneStepCreation_OneStepCreationPresenter::USER_CAN_CHOOSE_PROJECT_PRIVACY => $this->userCanSelectProjectPrivacy(),
                    Project_OneStepCreation_OneStepCreationPresenter::UNIX_NAME                       => $this->getUnixName(),
                    Project_OneStepCreation_OneStepCreationPresenter::TEMPLATE_ID                     => $this->getTemplateId(),
                    Project_OneStepCreation_OneStepCreationPresenter::SHORT_DESCRIPTION               => $this->getShortDescription(),
                    'is_test'                                                                         => false,
                    'services'                                                                        => $this->getServices(),
                ),
                $this->custom_descriptions,
                $this->getTroveCatDataForProjectRequest()
            )
        );
    }

    private function getTroveCatDataForProjectRequest() {
        $trove_data = array();

        if (count($this->trove_cats) > 0) {
            $troves = array();

            foreach ($this->trove_cats as $trove_id => $selected_child_trove_id) {
                $troves[$trove_id] = array($selected_child_trove_id);
            }

            $trove_data['trove'] = $troves;
        }

        return $trove_data;
    }

    /**
     * @return PFUser
     */
    public function getCurrentUser() {
        return $this->request->getCurrentUser();
    }

    /**
     *
     * @return string
     */
    public function getFormSubmissionPath() {
        return $this->form_submission_path;
    }

    /**
     *
     * @return string
     */
    public function getUnixName() {
        return $this->unix_name;
    }

    /**
     *
     * @return string
     */
    public function getFullName() {
        return $this->full_name;
    }

    /**
     *
     * @return string
     */
    public function getShortDescription() {
        return $this->short_description;
    }

    /**
     *
     * @return int
     */
    public function getTemplateId() {
        return $this->templateId;
    }

    /**
     *
     * @return bool
     */
    public function isPublic() {
        return $this->is_public;
    }

    /**
     *
     * @return bool
     */
    public function userCanSelectProjectPrivacy() {
        return $this->user_can_choose_project_privacy;
    }

    /**
     *
     * @return type
     */
    public function getTosApproval() {
        return $this->term_of_service_approval;
    }

    /**
     * @return string
     */
    public function getCustomProjectDescription($id) {
        if (isset($this->custom_descriptions[Project_OneStepCreation_OneStepCreationPresenter::PROJECT_DESCRIPTION_PREFIX . $id])) {
            return $this->custom_descriptions[Project_OneStepCreation_OneStepCreationPresenter::PROJECT_DESCRIPTION_PREFIX . $id];
        }
    }

    /**
     * @return string
     */
    public function getTroveCat($id) {
        if (isset($this->trove_cats[$id])) {
            return $this->trove_cats[$id];
        }
    }

    /**
     *
     * @param array $data
     * @return \Project_OneStepCreation_OneStepCreationPresenter
     */
    private function setUnixName(array $data) {
        if(isset($data[Project_OneStepCreation_OneStepCreationPresenter::UNIX_NAME])) {
            $this->unix_name = $data[Project_OneStepCreation_OneStepCreationPresenter::UNIX_NAME];
        }

        return $this;
    }

    /**
     *
     * @param array $data
     * @return \Project_OneStepCreation_OneStepCreationPresenter
     */
    private function setFullName(array $data) {
        if(isset($data[Project_OneStepCreation_OneStepCreationPresenter::FULL_NAME])) {
            $this->full_name = $data[Project_OneStepCreation_OneStepCreationPresenter::FULL_NAME];
        }

        return $this;
    }

    /**
     *
     * @param array $data
     * @return \Project_OneStepCreation_OneStepCreationPresenter
     */
    private function setShortDescription(array $data) {
        if(isset($data[Project_OneStepCreation_OneStepCreationPresenter::SHORT_DESCRIPTION])) {
            $this->short_description = trim($data[Project_OneStepCreation_OneStepCreationPresenter::SHORT_DESCRIPTION]);
        }

        return $this;
    }

    /**
     *
     * @param array $data
     * @return \Project_OneStepCreation_OneStepCreationPresenter
     */
    private function setTemplateId(array $data) {
        if(isset($data[Project_OneStepCreation_OneStepCreationPresenter::TEMPLATE_ID])) {
            $this->templateId = $data[Project_OneStepCreation_OneStepCreationPresenter::TEMPLATE_ID];
        } else {
            $this->templateId = Project_OneStepCreation_OneStepCreationPresenter::DEFAULT_TEMPLATE_ID;
        }

        return $this;
    }

    /**
     *
     * @param array $data
     * @return \Project_OneStepCreation_OneStepCreationPresenter
     */
    private function setIsPublic(array $data) {
        if(isset($data[Project_OneStepCreation_OneStepCreationPresenter::IS_PUBLIC])) {
            $this->is_public = $data[Project_OneStepCreation_OneStepCreationPresenter::IS_PUBLIC];
        }

        return $this;
    }

    /**
     *
     * @param type $data
     * @return \Project_OneStepCreation_OneStepCreationPresenter
     */
    private function setTosApproval($data) {
        $this->term_of_service_approval = false;

        if (isset($data[Project_OneStepCreation_OneStepCreationPresenter::TOS_APPROVAL])) {
            $this->term_of_service_approval = true;
        }

        return $this;
    }

    private function setCustomDescriptions($data) {
        foreach ($data as $key => $value) {
            if (preg_match('/^'. preg_quote(Project_OneStepCreation_OneStepCreationPresenter::PROJECT_DESCRIPTION_PREFIX) .'(\d+)$/', $key, $matches)) {
                $this->custom_descriptions[$key] = $value;
            }
        }

        return $this;
    }

    private function setTroveCats($data) {
        foreach ($data as $key => $trove_value) {
            if ($key === Project_OneStepCreation_OneStepCreationPresenter::TROVE_CAT_PREFIX) {
                foreach ($trove_value as $trove_id => $selected_child_trove_id) {
                    $this->trove_cats[$trove_id] = $selected_child_trove_id;
                }
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    private function getServices() {
        $services = array();
        $project = $this->project_manager->getProject($this->getTemplateId());
        foreach($project->getServices() as $service) {
            $id = $service->getId();
            $services[$id]['is_used'] = $service->isUsed();
        }
        return $services;
    }
}
