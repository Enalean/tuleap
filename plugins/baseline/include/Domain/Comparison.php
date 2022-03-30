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

namespace Tuleap\Baseline\Domain;

use DateTimeInterface;
use PFUser;

class Comparison extends TransientComparison
{
    /** @var int */
    private $id;

    /** @var DateTimeInterface */
    private $creation_date;

    /** @var PFUser */
    private $author;

    public function __construct(
        int $id,
        ?string $name,
        ?string $comment,
        Baseline $base_baseline,
        Baseline $compared_to_baseline,
        PFUser $author,
        DateTimeInterface $creation_date,
    ) {
        parent::__construct($name, $comment, $base_baseline, $compared_to_baseline);
        $this->id            = $id;
        $this->creation_date = $creation_date;
        $this->author        = $author;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreationDate(): DateTimeInterface
    {
        return $this->creation_date;
    }

    public function getAuthor(): PFUser
    {
        return $this->author;
    }
}
