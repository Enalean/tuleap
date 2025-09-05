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

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class FlatArtifactListValueLabelArrayTransformer implements FlatArtifactListValueLabelTransformer
{
    /**
     * @psalm-param list<string> $value_labels
     * @psalm-return Ok<list<string>>
     */
    #[\Override]
    public function transformListValueLabels(
        int $artifact_id,
        int $field_id,
        string $field_name,
        array $value_labels,
    ): Ok {
        return Result::ok($value_labels);
    }
}
