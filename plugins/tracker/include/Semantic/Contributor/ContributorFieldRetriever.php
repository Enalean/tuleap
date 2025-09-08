<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Contributor;

use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Tracker;

final readonly class ContributorFieldRetriever implements RetrieveContributorField
{
    public function __construct(private TrackerSemanticContributorFactory $factory)
    {
    }

    #[\Override]
    public function getContributorField(Tracker $tracker): ?ListField
    {
        return $this->factory->getByTracker($tracker)->getField();
    }
}
