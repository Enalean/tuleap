<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration;

use ForgeConfig;
use PFUser;
use ProjectManager;
use Tuleap\Project\Admin\Categories\CategoryCollectionConsistencyChecker;
use Tuleap\Project\Admin\Categories\ProjectCategoriesException;
use Tuleap\Project\Admin\DescriptionFields\ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\ProjectCreationData;

final class ProjectRegistrationRESTChecker implements ProjectRegistrationChecker
{
    private DefaultProjectVisibilityRetriever $default_project_visibility_retriever;
    private CategoryCollectionConsistencyChecker $category_collection_consistency_checker;
    private ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker $submitted_fields_collection_consistency_checker;

    public function __construct(
        DefaultProjectVisibilityRetriever $default_project_visibility_retriever,
        CategoryCollectionConsistencyChecker $category_collection_consistency_checker,
        ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker $submitted_fields_collection_consistency_checker,
    ) {
        $this->default_project_visibility_retriever            = $default_project_visibility_retriever;
        $this->category_collection_consistency_checker         = $category_collection_consistency_checker;
        $this->submitted_fields_collection_consistency_checker = $submitted_fields_collection_consistency_checker;
    }

    public function collectAllErrorsForProjectRegistration(PFUser $user, ProjectCreationData $project_creation_data): ProjectRegistrationErrorsCollection
    {
        $errors_collection = new ProjectRegistrationErrorsCollection();

        $access             = $project_creation_data->getAccess();
        $default_visibility = $this->default_project_visibility_retriever->getDefaultProjectVisibility();
        if (ForgeConfig::getInt(ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY) === 0 && $access !== $default_visibility) {
            $errors_collection->addError(new ProjectAccessLevelCannotBeChosenByUserException($default_visibility));
        }

        try {
            $this->category_collection_consistency_checker->checkCollectionConsistency(
                $project_creation_data->getTroveData()
            );
        } catch (ProjectCategoriesException $exception) {
            $errors_collection->addError($exception);
        }

        $this->submitted_fields_collection_consistency_checker->checkFieldConsistency(
            $project_creation_data->getDataFields(),
            $errors_collection
        );

        return $errors_collection;
    }
}
