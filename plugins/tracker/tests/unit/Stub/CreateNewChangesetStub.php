<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;

final class CreateNewChangesetStub implements \Tuleap\Tracker\Artifact\Changeset\CreateNewChangeset
{
    private function __construct(
        private ?\Tracker_Artifact_Changeset $changeset,
        private ?\Throwable $exception,
    ) {
    }

    public static function withReturnChangeset(\Tracker_Artifact_Changeset $changeset): self
    {
        return new self($changeset, null);
    }

    public static function withNullReturnChangeset(): self
    {
        return new self(null, null);
    }

    public static function withException(\Throwable $param): self
    {
        return new self(null, $param);
    }

    public function create(NewChangeset $changeset, PostCreationContext $context): ?\Tracker_Artifact_Changeset
    {
        if ($this->exception) {
            throw $this->exception;
        }
        return $this->changeset;
    }
}
