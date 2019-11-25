<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;

/**
 * Routes the one step creation requests
 */
class Project_OneStepCreation_OneStepCreationRouter //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{

    /**
     * @var TroveCatFactory
     */
    private $trove_cat_factory;

    /** @var ProjectManager */
    private $project_manager;
    /**
     * @var DefaultProjectVisibilityRetriever
     */
    private $default_project_visibility_retriever;
    /** @var Project_CustomDescription_CustomDescriptionFactory */
    private $custom_description_factory;
    /**
     * @var ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;

    public function __construct(
        ProjectManager $project_manager,
        DefaultProjectVisibilityRetriever $default_project_visibility_retriever,
        Project_CustomDescription_CustomDescriptionFactory $custom_description_factory,
        TroveCatFactory $trove_cat_factory,
        ProjectRegistrationUserPermissionChecker $permission_checker
    ) {
        $this->project_manager                      = $project_manager;
        $this->default_project_visibility_retriever = $default_project_visibility_retriever;
        $this->custom_description_factory           = $custom_description_factory;
        $this->trove_cat_factory                    = $trove_cat_factory;
        $this->permission_checker                   = $permission_checker;
    }

    public function route(Codendi_Request $request)
    {
        $csrf_token = new CSRFSynchronizerToken('/project/register.php');
        $controller = new Project_OneStepCreation_OneStepCreationController(
            $request,
            $this->project_manager,
            $this->default_project_visibility_retriever,
            $this->custom_description_factory,
            $this->trove_cat_factory,
            $csrf_token,
            $this->permission_checker
        );

        if ($request->get('create_project')) {
            $controller->create();
        } else {
            $controller->index();
        }
    }
}
