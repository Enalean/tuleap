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

namespace Tuleap\Tracker\Permission;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SubmissionPermissionVerifierTest extends TestCase
{
    private \PFUser $user;
    private SubmissionPermissionVerifier $verifier;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker
     */
    private $tracker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Tracker\FormElement\Field\String\StringField
     */
    private $submitable_field;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Tracker\FormElement\Field\String\StringField
     */
    private $readonly_field;
    /**
     * @var \EventManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $event_manager;

    protected function setUp(): void
    {
        $this->user    = UserTestBuilder::buildWithDefaults();
        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getId')->willReturn(1);
        $this->submitable_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);
        $this->submitable_field->method('userCanSubmit')->willReturn(true);
        $this->readonly_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);
        $this->readonly_field->method('userCanSubmit')->willReturn(false);

        $this->form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->event_manager        = $this->createMock(\EventManager::class);

        $this->verifier = new SubmissionPermissionVerifier(
            $this->form_element_factory,
            $this->event_manager
        );
    }

    public function testAnonymousUserCanNotSubmitArtifact(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        self::assertFalse($this->verifier->canUserSubmitArtifact($user, $this->tracker));
    }

    public function testUserWhoCanNotSeeTrackerCanNotSubmitArtifact(): void
    {
        $this->tracker->method('userCanView')->willReturn(false);
        self::assertFalse($this->verifier->canUserSubmitArtifact($this->user, $this->tracker));
    }

    public function testUserWhoCanNotSeeAnyFieldCanNotSubmitArtifact(): void
    {
        $this->tracker->method('userCanView')->willReturn(true);
        $this->form_element_factory->method('getUsedFields')->willReturn([$this->readonly_field]);
        self::assertFalse($this->verifier->canUserSubmitArtifact($this->user, $this->tracker));
    }

    public function testPluginCanDisableArtifactSubmission(): void
    {
        $this->tracker->method('userCanView')->willReturn(true);
        $this->form_element_factory->method('getUsedFields')->willReturn([$this->submitable_field]);
        $this->event_manager->method('dispatch')->willReturnCallback(
            static function (CanSubmitNewArtifact $event) {
                $event->disableArtifactSubmission();
                return $event;
            }
        );
        self::assertFalse($this->verifier->canUserSubmitArtifact($this->user, $this->tracker));
    }

    public function testUserCanSubmitAnArtifactAndPermissionsAreCached(): void
    {
        $this->tracker->expects($this->once())->method('userCanView')->willReturn(true);
        $this->form_element_factory->expects($this->once())->method('getUsedFields')->willReturn([$this->submitable_field, $this->readonly_field]);
        $this->event_manager->method('dispatch')->willReturn(new CanSubmitNewArtifact($this->user, $this->tracker));
        self::assertTrue($this->verifier->canUserSubmitArtifact($this->user, $this->tracker));
        self::assertTrue($this->verifier->canUserSubmitArtifact($this->user, $this->tracker));
    }
}
