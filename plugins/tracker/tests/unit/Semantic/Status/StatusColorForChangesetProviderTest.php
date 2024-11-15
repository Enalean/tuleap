<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;

final class StatusColorForChangesetProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tuleap\Tracker\Semantic\Status\StatusValueForChangesetProvider|\Tuleap\Tracker\Semantic\Status\StatusValueForChangesetProvider&\PHPUnit\Framework\MockObject\MockObject
     */
    private $value_for_changeset_provider;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_FormElement_Field_List|\Tracker_FormElement_Field_List&\PHPUnit\Framework\MockObject\MockObject
     */
    private $field;
    private StatusColorForChangesetProvider $color_provider;
    private \PFUser $user;
    private \Tracker_FormElement_Field_List_BindValue $bind_value;
    /**
     * @var \Tracker&\PHPUnit\Framework\MockObject\MockObject
     */
    private $tracker;
    private Artifact $artifact;
    private \Tracker_Artifact_Changeset $changeset;

    protected function setUp(): void
    {
        $tracker_id  = 1;
        $artifact_id = 20;
        $changset_id = 300;

        $this->value_for_changeset_provider = $this->createMock(StatusValueForChangesetProvider::class);
        $this->color_provider               = new StatusColorForChangesetProvider($this->value_for_changeset_provider);

        $this->user    = UserTestBuilder::anActiveUser()->build();
        $this->tracker = $this->createMock(\Tracker::class);
        $this->tracker->method('getId')->willReturn($tracker_id);
        $this->artifact  = new Artifact($artifact_id, $this->tracker->getId(), $this->user->getId(), 1669714644, false);
        $this->changeset = new \Tracker_Artifact_Changeset($changset_id, $this->artifact, $this->user->getId(), 1669714644, 'example@email.com');
        $this->field     = $this->createMock(\Tracker_FormElement_Field_List::class);
        $this->field->method('getId')->willReturn(4);
        $this->bind_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(1233, 'My bind value', '', 1, 0);
    }

    public function testNoColorIsDefinedWhenStatusFieldIsNotDefined(): void
    {
        $this->tracker->method('getStatusField')->willReturn(null);

        $this->assertNull($this->color_provider->provideColor($this->changeset, $this->tracker, $this->user));
    }

    public function testNoColorISDefinedWhenStatusValueIsNotFound(): void
    {
        $this->tracker->method('getStatusField')->willReturn($this->field);
        $this->value_for_changeset_provider->method('getStatusValueForChangeset')->willReturn(null);

        $this->assertNull($this->color_provider->provideColor($this->changeset, $this->tracker, $this->user));
    }

    public function testNoColorIsDefinedWhenFieldBindIsNotFound(): void
    {
        $this->tracker->method('getStatusField')->willReturn($this->field);
        $bind_value = $this->createMock(\Tracker_FormElement_Field_List_BindValue::class);
        $this->value_for_changeset_provider->method('getStatusValueForChangeset')->willReturn($bind_value);
        $this->field->method('getBind')->willReturn(null);

        $this->assertNull($this->color_provider->provideColor($this->changeset, $this->tracker, $this->user));
    }

    public function testNoColorIsDefinedWhenDecoratorIsNotFound(): void
    {
        $this->tracker->method('getStatusField')->willReturn($this->field);
        $bind_value = $this->createMock(\Tracker_FormElement_Field_List_BindValue::class);
        $bind_value->method('getId')->willReturn(456);
        $this->value_for_changeset_provider->method('getStatusValueForChangeset')->willReturn($bind_value);
        $bind = new \Tracker_FormElement_Field_List_Bind_Static($this->field, false, [$this->bind_value], [], []);
        $this->field->method('getBind')->willReturn($bind);

        $this->assertNull($this->color_provider->provideColor($this->changeset, $this->tracker, $this->user));
    }

    public function testItProvidesNoColorForLegacyPalette(): void
    {
        $this->tracker->method('getStatusField')->willReturn($this->field);
        $bind_value = $this->createMock(\Tracker_FormElement_Field_List_BindValue::class);
        $bind_value->method('getId')->willReturn(456);
        $bind = new \Tracker_FormElement_Field_List_Bind_Static($this->field, false, [$this->bind_value], [], [$this->field->getId() => new \Tracker_FormElement_Field_List_BindDecorator($this->field->getId(), $this->bind_value->getId(), 234, 456, 123, null)]);
        $this->value_for_changeset_provider->method('getStatusValueForChangeset')->willReturn($bind_value);
        $this->field->method('getBind')->willReturn($bind);

        $this->assertNull($this->color_provider->provideColor($this->changeset, $this->tracker, $this->user));
    }

    public function testItProvidesColor(): void
    {
        $this->tracker->method('getStatusField')->willReturn($this->field);
        $bind_value = $this->createMock(\Tracker_FormElement_Field_List_BindValue::class);
        $bind_value->method('getId')->willReturn($this->field->getId());
        $bind = new \Tracker_FormElement_Field_List_Bind_Static($this->field, false, [$this->bind_value], [], [$this->field->getId() => new \Tracker_FormElement_Field_List_BindDecorator($this->field->getId(), $this->bind_value->getId(), null, null, null, 'flamingo-pink')]);
        $this->value_for_changeset_provider->method('getStatusValueForChangeset')->willReturn($bind_value);
        $this->field->method('getBind')->willReturn($bind);

        $this->assertSame('flamingo-pink', $this->color_provider->provideColor($this->changeset, $this->tracker, $this->user));
    }
}
