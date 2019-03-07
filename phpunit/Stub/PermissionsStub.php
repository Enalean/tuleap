<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Baseline\Stub;

use Project;
use Tuleap\Baseline\NotAuthorizedException;
use Tuleap\Baseline\Permissions;
use Tuleap\Baseline\SimplifiedBaseline;
use Tuleap\Baseline\TransientBaseline;

/**
 * Implementation of Permissions used for tests.
 */
class PermissionsStub implements Permissions
{
    /** @var bool */
    private $all_permitted;

    public function permitAll(): void
    {
        $this->all_permitted = true;
    }

    public function denyAll()
    {
        $this->all_permitted = false;
    }

    /**
     * @throws NotAuthorizedException
     */
    public function checkCreateBaseline(TransientBaseline $baseline)
    {
        if (! $this->all_permitted) {
            throw new NotAuthorizedException('Baseline creation not authorized');
        }
    }

    /**
     * @throws NotAuthorizedException
     */
    public function checkReadSimpleBaseline(SimplifiedBaseline $baseline): void
    {
        if (! $this->all_permitted) {
            throw new NotAuthorizedException('Simple baseline read not authorized');
        }
    }

    /**
     * @throws NotAuthorizedException
     */
    public function checkReadBaselinesOn(Project $project)
    {
        if (! $this->all_permitted) {
            throw new NotAuthorizedException('Baseline read not authorized on given project');
        }
    }
}
