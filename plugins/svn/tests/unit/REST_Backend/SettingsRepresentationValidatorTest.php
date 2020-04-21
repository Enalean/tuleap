<?php
/**
 *  Copyright (c) Enalean, 2017 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\SVN\REST\v1;

use PHPUnit\Framework\TestCase;

class SettingsRepresentationValidatorTest extends TestCase
{
    /**
     * @var SettingsRepresentationValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new SettingsRepresentationValidator();
    }

    public function testItThrowAnExceptionWHenPathAreNotUnique(): void
    {
        $notification_representation_01         = new NotificationRepresentation();
        $notification_representation_01->path   = "/tags";
        $notification_representation_01->emails = array('test@example.com');
        $notification_representation_01->users  = array();

        $notification_representation_02         = new NotificationRepresentation();
        $notification_representation_02->path   = "/tags";
        $notification_representation_02->emails = array('user@example.com');
        $notification_representation_02->users  = array();

        $settings = new SettingsPOSTRepresentation();
        $settings->email_notifications = array($notification_representation_01, $notification_representation_02);

        $this->expectException('Tuleap\SVN\REST\v1\SettingsInvalidException');
        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItDontThrowExceptionWhenPathAreUnique(): void
    {
        $notification_representation_01         = new NotificationRepresentation();
        $notification_representation_01->path   = "/tags";
        $notification_representation_01->emails = array('test@example.com');
        $notification_representation_01->users  = array();

        $notification_representation_02         = new NotificationRepresentation();
        $notification_representation_02->path   = "/trunks";
        $notification_representation_02->emails = array('user@example.com');
        $notification_representation_02->users  = array();

        $settings = new SettingsPOSTRepresentation();
        $settings->email_notifications = array($notification_representation_01, $notification_representation_02);

        $this->addToAssertionCount(1);

        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItDontThrowAnExceptionAccessFileIsSentEmpty(): void
    {
        $settings = new SettingsPUTRepresentation();
        $settings->access_file = "";

        $this->addToAssertionCount(1);

        $this->validator->validateForPUTRepresentation($settings);
    }

    public function testItThrowAnExceptionAccessFileKeyIsNotPresent(): void
    {
        $settings = new SettingsPUTRepresentation();

        $this->expectException('Tuleap\SVN\REST\v1\SettingsInvalidException');
        $this->validator->validateForPUTRepresentation($settings);
    }

    public function testItDontRaiseErrorWhenSettingsAreNotProvided(): void
    {
        $this->addToAssertionCount(1);

        $this->validator->validateForPUTRepresentation(null);
    }

    public function testItThrowsAnExceptionWhenUsersAndEmailAreBothEmpty(): void
    {
        $notification_representation_01         = new NotificationRepresentation();
        $notification_representation_01->path   = "/tags";
        $notification_representation_01->emails = array();
        $notification_representation_01->users  = array();

        $settings                      = new SettingsPOSTRepresentation();
        $settings->email_notifications = array($notification_representation_01);

        $this->expectException('Tuleap\SVN\REST\v1\SettingsInvalidException');
        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItThrowsAnExceptionWhenSameMailIsAddedTwiceOnTheSamePathOnPOST(): void
    {
        $notification_representation_01 = new NotificationRepresentation();
        $notification_representation_01->path = "/tags";
        $notification_representation_01->emails = array('test@example.com', 'test1@example.com', 'test@example.com');

        $settings = new SettingsPOSTRepresentation();
        $settings->email_notifications = array($notification_representation_01);

        $this->expectException('Tuleap\SVN\REST\v1\SettingsInvalidException');
        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItDontThrowExceptionWhenSameMailIsUsedForTwoDifferentPathOnPOST(): void
    {
        $notification_representation_01 = new NotificationRepresentation();
        $notification_representation_01->path = "/tags";
        $notification_representation_01->emails = array('test@example.com');

        $notification_representation_02 = new NotificationRepresentation();
        $notification_representation_02->path = "/trunks";
        $notification_representation_02->emails = array('test@example.com');

        $settings = new SettingsPOSTRepresentation();
        $settings->email_notifications = array($notification_representation_01, $notification_representation_02);

        $this->addToAssertionCount(1);

        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItThrowsAnExceptionWhenSameMailIsAddedTwiceOnTheSamePathOnPUT(): void
    {
        $notification_representation_01         = new NotificationRepresentation();
        $notification_representation_01->path   = "/tags";
        $notification_representation_01->emails = array('test@example.com', 'test1@example.com', 'test@example.com');

        $settings                      = new SettingsPUTRepresentation();
        $settings->email_notifications = array($notification_representation_01);
        $settings->access_file         = "";
        $settings->immutable_tags      = array();
        $settings->commit_rules        = array();

        $this->expectException('Tuleap\SVN\REST\v1\SettingsInvalidException');
        $this->validator->validateForPUTRepresentation($settings);
    }

    public function testItDontThrowExceptionWhenSameMailIsUsedForTwoDifferentPathOnPUT(): void
    {
        $notification_representation_01         = new NotificationRepresentation();
        $notification_representation_01->path   = "/tags";
        $notification_representation_01->emails = array('test@example.com');

        $notification_representation_02         = new NotificationRepresentation();
        $notification_representation_02->path   = "/trunks";
        $notification_representation_02->emails = array('test@example.com');

        $settings                      = new SettingsPUTRepresentation();
        $settings->email_notifications = array($notification_representation_01, $notification_representation_02);
        $settings->access_file         = "";
        $settings->immutable_tags      = array();
        $settings->commit_rules        = array();

        $this->addToAssertionCount(1);

        $this->validator->validateForPUTRepresentation($settings);
    }
}
