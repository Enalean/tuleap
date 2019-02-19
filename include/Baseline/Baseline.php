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

namespace Tuleap\Baseline;

use DateTime;
use PFUser;
use Tracker_Artifact;

/**
 * Persisted baseline
 */
class Baseline extends TransientBaseline
{
    /** @var int */
    private $id;

    /** @var PFUser */
    private $author;

    /** @var DateTime */
    private $creation_date;

    public function __construct(
        int $id,
        string $name,
        Tracker_Artifact $release,
        PFUser $author,
        DateTime $creation_date
    ) {
        parent::__construct($name, $release);
        $this->id            = $id;
        $this->author        = $author;
        $this->creation_date = $creation_date;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAuthor(): PFUser
    {
        return $this->author;
    }

    public function getCreationDate(): DateTime
    {
        return $this->creation_date;
    }
}
