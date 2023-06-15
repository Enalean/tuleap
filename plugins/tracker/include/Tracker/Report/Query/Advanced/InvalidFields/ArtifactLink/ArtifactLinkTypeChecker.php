<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ArtifactLink;

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\VisibleTypesRetriever;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithForwardLink;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithoutForwardLink;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithoutReverseLink;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithReverseLink;

final class ArtifactLinkTypeChecker
{
    public function __construct(private readonly VisibleTypesRetriever $all_types_retriever)
    {
    }

    public function checkArtifactLinkTypeIsValid(
        WithReverseLink | WithoutReverseLink | WithForwardLink | WithoutForwardLink $condition,
    ): void {
        if ($condition->link_type === null) {
            return;
        }

        $visible_types = $this->all_types_retriever->getOnlyVisibleTypes();
        foreach ($visible_types as $type) {
            if ($type->shortname === $condition->link_type) {
                return;
            }
        }

        throw new InvalidArtifactLinkTypeException($condition->link_type, $visible_types);
    }
}
