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

namespace Tuleap\Velocity\Semantic;

use Tuleap\Tracker\Semantic\IDuplicateSemantic;

final readonly class SemanticVelocityDuplicator implements IDuplicateSemantic
{
    public function __construct(private SemanticVelocityDao $dao)
    {
    }

    #[\Override]
    public function duplicate(int $from_tracker_id, int $to_tracker_id, array $field_mapping): void
    {
        $template = $this->dao->searchUsedVelocityField($from_tracker_id);
        if ($template) {
            $from_field = $template['field_id'];
            $to_field   = null;
            foreach ($field_mapping as $mapping) {
                if ($mapping['from'] === $from_field) {
                    $to_field = $mapping['to'];
                }
            }
            if ($to_field !== null) {
                $this->dao->addField($to_tracker_id, $to_field);
            }
        }
    }
}
