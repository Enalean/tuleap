<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\AddReverseLinksCommand;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ConvertAddReverseLinks;

final class ConvertAddReverseLinksStub implements ConvertAddReverseLinks
{
    /**
     * @return Ok<list<NewChangeset>> | Err<Fault>
     */
    private function __construct(private readonly CollectionOfReverseLinks $expected_links_to_add, private readonly Ok|Err $result)
    {
    }

    public static function willReturnListOfNewChangesets(CollectionOfReverseLinks $expected_links_to_add, NewChangeset $first_changeset, NewChangeset ...$other_changesets): self
    {
        return new self($expected_links_to_add, Result::ok([$first_changeset, ...$other_changesets]));
    }

    public static function willReturnEmptyListOfNewChangesets(CollectionOfReverseLinks $expected_links_to_add): self
    {
        return new self($expected_links_to_add, Result::ok([]));
    }

    public static function willFault(CollectionOfReverseLinks $expected_links_to_add, Fault $fault): self
    {
        return new self($expected_links_to_add, Result::err($fault));
    }

    public function convertAddReverseLinks(AddReverseLinksCommand $command, \PFUser $submitter, \DateTimeImmutable $submission_date,): Ok|Err
    {
        if ($this->expected_links_to_add === $command->getLinksToAdd()) {
            return $this->result;
        }

        throw new \Exception("Unexpected call to convertAddReverseLinks");
    }
}
