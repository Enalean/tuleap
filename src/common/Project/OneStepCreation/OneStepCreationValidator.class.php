<?php
/**
  * Copyright (c) Enalean, 2013 - present. All rights reserved
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

use Tuleap\Project\ProjectDescriptionUsageRetriever;
use Tuleap\Project\Registration\Template\InsufficientPermissionToUseProjectAsTemplateException;
use Tuleap\Project\Registration\Template\ProjectIDTemplateNotProvidedException;
use Tuleap\Project\Registration\Template\ProjectTemplateNotActiveException;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;

/**
 * Validates the request
 */
class Project_OneStepCreation_OneStepCreationValidator //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var bool
     */
    private $is_valid;

    /** @var Project_OneStepCreation_OneStepCreationRequest */
    private $creation_request;

    /** @var Project_CustomDescription_CustomDescription[] */
    private $required_custom_descriptions;

    /** @var TroveCat[] */
    private $trove_cats;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var Rule_ProjectFullName
     */
    private $rule_project_full_name;
    /**
     * @var Rule_ProjectName
     */
    private $rule_project_name;

    public function __construct(
        Project_OneStepCreation_OneStepCreationRequest $creation_request,
        array $required_custom_descriptions,
        array $trove_cats,
        ProjectManager $project_manager,
        Rule_ProjectFullName $rule_project_full_name,
        Rule_ProjectName $rule_project_name
    ) {
        $this->creation_request             = $creation_request;
        $this->required_custom_descriptions = $required_custom_descriptions;
        $this->trove_cats                   = $trove_cats;
        $this->project_manager              = $project_manager;
        $this->rule_project_full_name       = $rule_project_full_name;
        $this->rule_project_name            = $rule_project_name;
    }

    /**
     * @return bool
     */
    public function validateAndGenerateErrors()
    {
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
    private function validateFullName()
    {
        if ($this->creation_request->getFullName() == null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
            return $this;
        }

        if (! $this->rule_project_full_name->isValid($this->creation_request->getFullName())) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_license', 'invalid_full_name'));
            $GLOBALS['Response']->addFeedback('error', $this->rule_project_full_name->getErrorMessage());
            $this->setIsNotValid();
        }

        return $this;
    }

    private function validateShortDescription(): self
    {
        if (ProjectDescriptionUsageRetriever::isDescriptionMandatory() && $this->creation_request->getShortDescription() === null) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('register_projectname', 'info_missed')
            );
            $this->setIsNotValid();
        }

        return $this;
    }

    /**
     *
     * @return \Project_OneStepCreation_OneStepCreationValidator
     */
    private function validateUnixName()
    {
        if ($this->creation_request->getUnixName() == null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
            return $this;
        }

        // check for valid group name
        if (! $this->rule_project_name->isValid($this->creation_request->getUnixName())) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_license', 'invalid_short_name'));
            $GLOBALS['Response']->addFeedback('error', $this->rule_project_name->getErrorMessage());
            $this->setIsNotValid();
        }

        return $this;
    }

    private function validateTemplateId(): self
    {
        try {
            $template_for_project_creation = TemplateFromProjectForCreation::fromRegisterCreationRequest(
                $this->creation_request,
                $this->project_manager
            );
        } catch (ProjectIDTemplateNotProvidedException $ex) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
            return $this;
        } catch (ProjectTemplateNotActiveException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                _('Non active projects cannot be used to be project template')
            );
            $this->setIsNotValid();
            return $this;
        } catch (InsufficientPermissionToUseProjectAsTemplateException $ex) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
            return $this;
        }

        $this->creation_request->setTemplateForProjectCreation($template_for_project_creation);

        return $this;
    }

    /**
     *
     * @return \Project_OneStepCreation_OneStepCreationValidator
     */
    private function validateProjectPrivacy()
    {
        if ($this->creation_request->isPublic() === null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
        }

        return $this;
    }

    /**
     * @return \Project_OneStepCreation_OneStepCreationValidator
     */
    private function validateTosApproval()
    {
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
    private function validateCustomDescriptions()
    {
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
    private function validateTroveCats()
    {
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
    private function setIsNotValid()
    {
        $this->is_valid = false;
        return $this;
    }
}
