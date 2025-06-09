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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Status;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StatusValueForChangesetProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private StatusValueForChangesetProvider&MockObject $provider;
    private Tracker_Artifact_Changeset $changeset;
    private PFUser $user;
    private \Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus&MockObject $semantic;

    protected function setUp(): void
    {
        $this->changeset = ChangesetTestBuilder::aChangeset(101)->build();

        $this->user = UserTestBuilder::buildWithDefaults();

        $this->semantic = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus::class);

        $this->provider = $this->createPartialMock(StatusValueForChangesetProvider::class, ['loadSemantic']);
        $this->provider->method('loadSemantic')->willReturn($this->semantic);
    }

    public function testReturnsNullIfNoFieldForStatus()
    {
        $this->semantic->expects($this->once())->method('getField')->willReturn(null);

        $this->assertNull($this->provider->getStatusValueForChangeset($this->changeset, $this->user));
    }

    public function testReturnsNullIfUserCannotReadStatus()
    {
        $field = ListFieldBuilder::aListField(1001)->withReadPermission($this->user, false)->build();
        $this->semantic->expects($this->once())->method('getField')->willReturn($field);

        $this->assertNull($this->provider->getStatusValueForChangeset($this->changeset, $this->user));
    }

    public function testReturnsNullIfNoChangesetValue()
    {
        $field = ListFieldBuilder::aListField(1001)->withReadPermission($this->user, true)->build();
        $this->semantic->expects($this->once())->method('getField')->willReturn($field);

        $this->changeset->setFieldValue($field, null);

        $this->assertNull($this->provider->getStatusValueForChangeset($this->changeset, $this->user));
    }

    public function testReturnsNullIfNoValueForField()
    {
        $field = ListFieldBuilder::aListField(1001)->withReadPermission($this->user, true)->build();
        $this->semantic->expects($this->once())->method('getField')->willReturn($field);

        $value = $this->createMock(\Tracker_Artifact_ChangesetValue_List::class);
        $this->changeset->setFieldValue($field, $value);

        $value->expects($this->once())->method('getListValues')->willReturn([]);

        $this->assertNull($this->provider->getStatusValueForChangeset($this->changeset, $this->user));
    }

    public function testReturnsTheFirstValue()
    {
        $field = ListFieldBuilder::aListField(1001)->withReadPermission($this->user, true)->build();
        $this->semantic->expects($this->once())->method('getField')->willReturn($field);

        $value = $this->createMock(\Tracker_Artifact_ChangesetValue_List::class);
        $this->changeset->setFieldValue($field, $value);

        $todo = ListStaticValueBuilder::aStaticValue('todo')->build();
        $done = ListStaticValueBuilder::aStaticValue('done')->build();
        $value->expects($this->once())->method('getListValues')->willReturn([$todo, $done]);

        self::assertSame($todo, $this->provider->getStatusValueForChangeset($this->changeset, $this->user));
    }
}
