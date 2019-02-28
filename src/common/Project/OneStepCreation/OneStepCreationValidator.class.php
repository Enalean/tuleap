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
 * Validates the request
 */
class Project_OneStepCreation_OneStepCreationValidator {

    /** @var Project_OneStepCreation_OneStepCreationRequest */
    private $creation_request;

    /** @var Project_CustomDescription_CustomDescription[] */
    private $required_custom_descriptions;

    /** @var TroveCat[] */
    private $trove_cats;

    public function __construct(
        Project_OneStepCreation_OneStepCreationRequest $creation_request,
        array $required_custom_descriptions,
        array $trove_cats
    ) {
        $this->creation_request             = $creation_request;
        $this->required_custom_descriptions = $required_custom_descriptions;
        $this->trove_cats                   = $trove_cats;
    }

    /**
     * @return boolean
     */
    public function validateAndGenerateErrors() {
        $this->is_valid = true;

        $this->validateTemplateId()
            ->validateUnixName()
            ->validateProjectPrivacy()
            ->validateFullName()
            ->validateShortDescription()
            ->validateTosApproval()
            ->validateCustomDescriptions()
            ->validateTroveCats();

        return $this->is_valid;
    }

    /**
     *
     * @return \Project_OneStepCreation_OneStepCreationValidator
     */
    private function validateFullName() {
        if ($this->creation_request->getFullName() == null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
            return $this;
        }

        $rule = new Rule_ProjectFullName();
        if (!$rule->isValid($this->creation_request->getFullName())) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_license','invalid_full_name'));
            $GLOBALS['Response']->addFeedback('error', $rule->getErrorMessage());
            $this->setIsNotValid();
        }

        return $this;
    }

    /**
     *
     * @return \Project_OneStepCreation_OneStepCreationValidator
     */
    private function validateShortDescription() {
        if ($this->creation_request->getShortDescription() == null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
        }

        return $this;
    }

    /**
     *
     * @return \Project_OneStepCreation_OneStepCreationValidator
     */
    private function validateUnixName() {
        if ($this->creation_request->getUnixName() == null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
            return $this;
        }

        //check for valid group name
        $rule = new Rule_ProjectName();
        if (!$rule->isValid($this->creation_request->getUnixName())) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_license','invalid_short_name'));
            $GLOBALS['Response']->addFeedback('error', $rule->getErrorMessage());
            $this->setIsNotValid();
        }

        return $this;
    }

    /**
     *
     * @return \Project_OneStepCreation_OneStepCreationValidator
     */
    private function validateTemplateId() {
        if ($this->creation_request->getTemplateId() == null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
            return $this;
        }

        $project_manager = ProjectManager::instance();
        $project = $project_manager->getProject($this->creation_request->getTemplateId());

        if (! $project->isActive() && ! $project->isTemplate()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                _('Non active projects cannot be used to be project template')
            );
            $this->setIsNotValid();
        } elseif (! $project->isTemplate() && ! user_ismember($this->creation_request->getTemplateId(), 'A')) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
            $this->setIsNotValid();
        }

        return $this;
    }

    /**
     *
     * @return \Project_OneStepCreation_OneStepCreationValidator
     */
    private function validateProjectPrivacy() {
        if ($this->creation_request->isPublic() === null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
        }

        return $this;
    }

    /**
     * @return \Project_OneStepCreation_OneStepCreationValidator
     */
    private function validateTosApproval() {
        if (! $this->creation_request->getTosApproval()) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'tos_not_approved'));
            $this->setIsNotValid();
        }

        return $this;
    }

    /**
     *
     * @return \Project_OneStepCreation_OneStepCreationValidator
     */
    private function validateCustomDescriptions() {
        foreach ($this->required_custom_descriptions as $id => $description) {
            if (! $this->creation_request->getCustomProjectDescription($id)) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_project_one_step', 'custom_description_missing', $description->getName()));
                $this->setIsNotValid();
            }
        }

        return $this;
    }

    /**
     *
     * @return Project_OneStepCreation_OneStepCreationValidator
     */
    private function validateTroveCats() {
        foreach ($this->trove_cats as $trove_cat) {
            if (! $this->creation_request->getTroveCat($trove_cat->getId())) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_project_one_step', 'mandatory_trovecat_missing', $trove_cat->getFullname()));
                $this->setIsNotValid();
            }
        }

        return $this;
    }

    /**
     * @return Project_OneStepCreation_OneStepCreationValidator
     */
    private function setIsNotValid() {
        $this->is_valid = false;
        return $this;
    }
}