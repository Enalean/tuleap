<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields;

use PHPUnit\Framework\MockObject\Stub;
use Tracker_FormElementFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldNotFoundException;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldPermissionsVerifierTest extends TestCase
{
    private Tracker_FormElementFactory&Stub $form_element_factory;
    private FieldPermissionsVerifier $permission_verifier;
    private UserIdentifierStub $user_identifier;
    private \Tracker_FormElement_Field_String $full_field;
    private TitleFieldReferenceProxy $field;

    protected function setUp(): void
    {
        $this->user_identifier      = UserIdentifierStub::withId(110);
        $retrieve_user              = RetrieveUserStub::withUser(
            UserTestBuilder::aUser()->withSiteAdministrator()->withId(110)->build()
        );
        $this->form_element_factory = $this->createStub(Tracker_FormElementFactory::class);

        $this->permission_verifier = new FieldPermissionsVerifier($retrieve_user, $this->form_element_factory);
        $this->full_field          = StringFieldBuilder::aStringField(1)->build();
        $this->field               = TitleFieldReferenceProxy::fromTrackerField($this->full_field);
    }

    public function testItThrowsExceptionWhenFieldCanNotBeFoundInSubmitContext(): void
    {
        $this->form_element_factory->method('getFieldById')->willReturn(null);

        $this->expectException(FieldNotFoundException::class);
        $this->permission_verifier->canUserSubmit($this->user_identifier, $this->field);
    }

    public function testItReturnsUserCanSubmit(): void
    {
        $this->form_element_factory->method('getFieldById')->willReturn($this->full_field);
        self::assertTrue($this->permission_verifier->canUserSubmit($this->user_identifier, $this->field));
    }

    public function testItThrowsExceptionWhenFieldCanNotBeFoundInUpdateContext(): void
    {
        $this->form_element_factory->method('getFieldById')->willReturn(null);

        $this->expectException(FieldNotFoundException::class);
        $this->permission_verifier->canUserUpdate($this->user_identifier, $this->field);
    }

    public function testItReturnsUserCanUpdate(): void
    {
        $this->form_element_factory->method('getFieldById')->willReturn($this->full_field);
        self::assertTrue($this->permission_verifier->canUserUpdate($this->user_identifier, $this->field));
    }
}
