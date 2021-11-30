<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Git\CIBuilds;

use Tuleap\Git\AccessRightsPresenterOptionsBuilder;

class CIBuildsPanePresenterBuilder
{
    /**
     * @var CITokenManager
     */
    private $ci_token_manager;
    /**
     * @var \GitRepository
     */
    private $repository;
    /**
     * @var AccessRightsPresenterOptionsBuilder
     */
    private $access_right_options_builder;
    /**
     * @var BuildStatusChangePermissionManager
     */
    private $build_status_change_permission_manager;

    public function __construct(
        CITokenManager $ci_token_manager,
        \GitRepository $repository,
        AccessRightsPresenterOptionsBuilder $access_right_options_builder,
        BuildStatusChangePermissionManager $build_status_change_permission_manager,
    ) {
        $this->ci_token_manager                       = $ci_token_manager;
        $this->repository                             = $repository;
        $this->access_right_options_builder           = $access_right_options_builder;
        $this->build_status_change_permission_manager = $build_status_change_permission_manager;
    }

    public function build(): CIBuildsPanePresenter
    {
        $granted_groups_ids = $this->build_status_change_permission_manager->getBuildStatusChangePermissions($this->repository);
        $all_options        = $this->access_right_options_builder->getAllOptions($this->repository->getProject(), $granted_groups_ids);

        return new CIBuildsPanePresenter(
            (int) $this->repository->getId(),
            (int) $this->repository->getProjectId(),
            $all_options,
            $this->ci_token_manager->getToken($this->repository)
        );
    }
}
