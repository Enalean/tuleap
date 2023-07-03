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

namespace Tuleap\Baseline\Adapter;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tuleap\Baseline\Support\CurrentUserContext;

abstract class SemanticValueAdapterTestCase extends \Tuleap\Test\PHPUnit\TestCase
{
    use CurrentUserContext;

    protected SemanticValueAdapter $adapter;
    protected SemanticFieldRepository&MockObject $semantic_field_repository;

    /** @before */
    protected function createInstance(): void
    {
        $this->semantic_field_repository = $this->createMock(SemanticFieldRepository::class);
        $this->adapter                   = new SemanticValueAdapter($this->semantic_field_repository);
    }

    protected Tracker_Artifact_Changeset&MockObject $changeset;
    protected Tracker&MockObject $tracker;

    /** @before */
    protected function createEntities(): void
    {
        $this->changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $this->tracker   = $this->createMock(Tracker::class);
    }

    protected function mockChangesetValue(mixed $value): Tracker_Artifact_ChangesetValue&MockObject
    {
        $changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue::class);
        $changeset_value->method('getValue')->willReturn($value);

        return $changeset_value;
    }
}
