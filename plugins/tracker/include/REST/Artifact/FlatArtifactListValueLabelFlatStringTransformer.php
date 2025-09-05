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

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class FlatArtifactListValueLabelFlatStringTransformer implements FlatArtifactListValueLabelTransformer
{
    private const SEPARATOR = ';';

    /**
     * @psalm-param list<string> $value_labels
     * @psalm-return Ok<string>|Err<Fault>
     */
    #[\Override]
    public function transformListValueLabels(
        int $artifact_id,
        int $field_id,
        string $field_name,
        array $value_labels,
    ): Ok|Err {
        $invalid_label_values = [];
        foreach ($value_labels as $value_label) {
            if (str_contains($value_label, self::SEPARATOR)) {
                $invalid_label_values[] = $value_label;
            }
        }

        if (count($invalid_label_values) > 0) {
            return Result::err(
                Fault::fromMessage(
                    sprintf(
                        'Artifact #%d contains values (%s) with a forbidden "%s" character in the field #%d (%s)',
                        $artifact_id,
                        \Psl\Str\join($invalid_label_values, ', '),
                        self::SEPARATOR,
                        $field_id,
                        $field_name
                    )
                )
            );
        }

        return Result::ok(\Psl\Str\join($value_labels, self::SEPARATOR));
    }
}
