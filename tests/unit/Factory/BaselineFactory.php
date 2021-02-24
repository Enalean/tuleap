<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Baseline\Factory;

use PFUser;
use Project;
use Tuleap\Baseline\Support\DateTimeFactory;

/**
 * ObjectMother pattern applied to baselines
 */
class BaselineFactory
{
    public static function one(): BaselineBuilder
    {
        return (new BaselineBuilder())
            ->id(1)
            ->name('Milestone #1')
            ->artifact(BaselineArtifactFactory::one()->build())
            ->author(new PFUser())
            ->snapshotDate(DateTimeFactory::one());
    }

    public static function fromProject(Project $project): BaselineBuilder
    {
        return self::one()
            ->artifact(
                BaselineArtifactFactory::one()
                    ->project($project)
                    ->build()
            );
    }
}
