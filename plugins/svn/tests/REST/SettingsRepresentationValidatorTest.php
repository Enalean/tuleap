<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
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

require_once __DIR__ . '/../bootstrap.php';

class SettingsRepresentationValidatorTest extends \TuleapTestCase
{
    /**
     * @var SettingsRepresentationValidator
     */
    private $validator;

    public function setUp()
    {
        parent::setUp();

        $this->validator = new SettingsRepresentationValidator();
    }

    public function itThrowAnExceptionWHenPathAreNotUnique()
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

    public function itDontThrowExceptionWhenPathAreUnique()
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

        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function itDontThrowAnExceptionAccessFileIsSentEmpty()
    {
        $settings = new SettingsPUTRepresentation();
        $settings->access_file = "";

        $this->validator->validateForPUTRepresentation($settings);
    }

    public function itThrowAnExceptionAccessFileKeyIsNotPresent()
    {
        $settings = new SettingsPUTRepresentation();

        $this->expectException('Tuleap\SVN\REST\v1\SettingsInvalidException');
        $this->validator->validateForPUTRepresentation($settings);
    }

    public function itDontRaiseErrorWhenSettingsAreNotProvided()
    {
        $this->validator->validateForPUTRepresentation(null);
    }

    public function itThrowsAnExceptionWhenUsersAndEmailAreBothEmpty()
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

    public function itThrowsAnExceptionWhenSameMailIsAddedTwiceOnTheSamePathOnPOST()
    {
        $notification_representation_01 = new NotificationRepresentation();
        $notification_representation_01->path = "/tags";
        $notification_representation_01->emails = array('test@example.com', 'test1@example.com', 'test@example.com');

        $settings = new SettingsPOSTRepresentation();
        $settings->email_notifications = array($notification_representation_01);

        $this->expectException('Tuleap\SVN\REST\v1\SettingsInvalidException');
        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function itDontThrowExceptionWhenSameMailIsUsedForTwoDifferentPathOnPOST()
    {
        $notification_representation_01 = new NotificationRepresentation();
        $notification_representation_01->path = "/tags";
        $notification_representation_01->emails = array('test@example.com');

        $notification_representation_02 = new NotificationRepresentation();
        $notification_representation_02->path = "/trunks";
        $notification_representation_02->emails = array('test@example.com');


        $settings = new SettingsPOSTRepresentation();
        $settings->email_notifications = array($notification_representation_01, $notification_representation_02);

        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function itThrowsAnExceptionWhenSameMailIsAddedTwiceOnTheSamePathOnPUT()
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

    public function itDontThrowExceptionWhenSameMailIsUsedForTwoDifferentPathOnPUT()
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

        $this->validator->validateForPUTRepresentation($settings);
    }
}
