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

namespace Tuleap\Baseline\REST;

use Tuleap\Baseline\Domain\Comparison;
use Tuleap\REST\JsonCast;

class ComparisonRepresentation
{
    /** @var int */
    public $id;

    /** @var string|null */
    public $name;

    /** @var string|null */
    public $comment;

    /** @var int */
    public $base_baseline_id;

    /** @var int */
    public $compared_to_baseline_id;

    /** @var int */
    public $author_id;

    /** @var string */
    public $creation_date;

    public function __construct(
        int $id,
        ?string $name,
        ?string $comment,
        int $base_baseline_id,
        int $compared_to_baseline_id,
        int $author_id,
        string $creation_date,
    ) {
        $this->id                      = $id;
        $this->name                    = $name;
        $this->comment                 = $comment;
        $this->base_baseline_id        = $base_baseline_id;
        $this->compared_to_baseline_id = $compared_to_baseline_id;
        $this->author_id               = $author_id;
        $this->creation_date           = $creation_date;
    }

    public static function fromComparison(Comparison $comparison): ComparisonRepresentation
    {
        return new self(
            JsonCast::toInt($comparison->getId()),
            $comparison->getName(),
            $comparison->getComment(),
            JsonCast::toInt($comparison->getBaseBaseline()->getId()),
            JsonCast::toInt($comparison->getComparedToBaseline()->getId()),
            JsonCast::toInt($comparison->getAuthor()->getId()),
            JsonCast::fromDateTimeToDate($comparison->getCreationDate())
        );
    }
}
