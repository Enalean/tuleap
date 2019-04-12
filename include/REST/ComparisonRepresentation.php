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

namespace Tuleap\Baseline\REST;

use Tuleap\Baseline\Comparison;
use Tuleap\REST\JsonCast;

class ComparisonRepresentation
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string|null */
    public $comment;

    /** @var int */
    public $base_baseline_id;

    /** @var int */
    public $compared_to_baseline_id;

    private function __construct(
        int $id,
        string $name,
        ?string $comment,
        int $base_baseline_id,
        int $compared_to_baseline_id
    ) {
        $this->id                      = $id;
        $this->name                    = $name;
        $this->comment                 = $comment;
        $this->base_baseline_id        = $base_baseline_id;
        $this->compared_to_baseline_id = $compared_to_baseline_id;
    }

    public static function fromComparison(Comparison $comparison): ComparisonRepresentation
    {
        return new self(
            Jsoncast::toInt($comparison->getId()),
            $comparison->getName(),
            $comparison->getComment(),
            Jsoncast::toInt($comparison->getBaseBaseline()->getId()),
            Jsoncast::toInt($comparison->getComparedToBaseline()->getId())
        );
    }
}
