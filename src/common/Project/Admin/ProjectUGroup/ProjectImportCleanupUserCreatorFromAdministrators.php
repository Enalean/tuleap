<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Admin\ProjectUGroup;

use PFUser;
use ProjectUGroup;
use Tuleap\Event\Dispatchable;

final class ProjectImportCleanupUserCreatorFromAdministrators implements Dispatchable
{
    public const NAME = 'projectImportCleanupUserCreatorFromAdministrators';
    /**
     * @var PFUser
     */
    private $creator;
    /**
     * @var ProjectUGroup
     */
    private $ugroup_administrator;

    public function __construct(PFUser $creator, ProjectUGroup $ugroup_administrator)
    {
        $this->creator              = $creator;
        if ($ugroup_administrator->getId() !== ProjectUGroup::PROJECT_ADMIN) {
            throw new NotProjectAdministratorUGroup($ugroup_administrator);
        }
        $this->ugroup_administrator = $ugroup_administrator;
    }

    public function getCreator(): PFUser
    {
        return $this->creator;
    }

    public function getUGroupAdministrator(): ProjectUGroup
    {
        return $this->ugroup_administrator;
    }
}
