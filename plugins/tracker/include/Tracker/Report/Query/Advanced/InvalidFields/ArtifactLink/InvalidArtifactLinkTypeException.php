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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;

final class InvalidArtifactLinkTypeException extends \Exception
{
    public function __construct(string $link_type, array $visible_types)
    {
        parent::__construct(
            sprintf(
                dngettext(
                    'tuleap-tracker',
                    "Link type '%s' is invalid. Available type: %s",
                    "Link type '%s' is invalid. Available types: %s",
                    count($visible_types),
                ),
                $link_type,
                implode(', ', array_map(static fn (TypePresenter $type): string => $type->shortname, $visible_types)),
            )
        );
    }
}
