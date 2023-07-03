<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Action\CollectDryRunTypingField;
use Tuleap\Tracker\Action\DuckTypedMoveFieldCollection;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * @psalm-immutable
 */
final class CollectDryRunTypingFieldStub implements CollectDryRunTypingField
{
    private function __construct(
        private readonly bool $should_throw_when_collect_is_called,
        private readonly DuckTypedMoveFieldCollection $collection,
    ) {
    }

    public static function withCollectionOfField(DuckTypedMoveFieldCollection $collection): self
    {
        return new self(false, $collection);
    }

    public static function withNoExpectedCalls(): self
    {
        return new self(true, DuckTypedMoveFieldCollection::fromFields([], [], [], []));
    }

    public function collect(\Tracker $source_tracker, \Tracker $destination_tracker, Artifact $artifact, \PFUser $user): DuckTypedMoveFieldCollection
    {
        if ($this->should_throw_when_collect_is_called) {
            throw new \Exception("Attempted to collect fields in a dry run context while it was not expected");
        }

        return $this->collection;
    }
}
