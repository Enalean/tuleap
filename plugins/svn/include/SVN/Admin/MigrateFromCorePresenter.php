<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\SVN\Admin;

use CSRFSynchronizerToken;
use Project;
use Tuleap\SVN\Repository\CoreRepository;
use Tuleap\SVNCore\Repository;

final class MigrateFromCorePresenter extends BaseGlobalAdminPresenter
{
    /**
     * @var string
     * @psalm-readonly
     */
    public $update_url;
    /**
     * @var string
     * @psalm-readonly
     */
    public $svn_url;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $already_migrated;

    public function __construct(Project $project, CSRFSynchronizerToken $token, bool $has_migrate_from_core, Repository $repository)
    {
        parent::__construct($project, $token, $has_migrate_from_core);

        $this->update_url       = UpdateMigrateFromCoreController::getURL($project);
        $this->svn_url          = rtrim(\Tuleap\ServerHostname::HTTPSUrl(), '/') . $repository->getPublicPath();
        $this->already_migrated = $repository->getId() !== CoreRepository::TO_BE_CREATED_REPOSITORY_ID;
    }
}
