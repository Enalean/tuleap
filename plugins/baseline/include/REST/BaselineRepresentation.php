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

use Tuleap\Baseline\Domain\Baseline;
use Tuleap\REST\JsonCast;

class BaselineRepresentation
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var int */
    public $artifact_id;

    /** @var string */
    public $snapshot_date;

    /** @var int */
    public $author_id;

    public function __construct(int $id, string $name, int $artifact_id, string $snapshot_date, int $author_id)
    {
        $this->id            = $id;
        $this->name          = $name;
        $this->artifact_id   = $artifact_id;
        $this->snapshot_date = $snapshot_date;
        $this->author_id     = $author_id;
    }

    public static function fromBaseline(Baseline $baseline): BaselineRepresentation
    {
        return new self(
            JsonCast::toInt($baseline->getId()),
            $baseline->getName(),
            JsonCast::toInt($baseline->getArtifact()->getId()),
            JsonCast::fromDateTimeToDate($baseline->getSnapshotDate()),
            JsonCast::toInt($baseline->getAuthor()->getId())
        );
    }
}
