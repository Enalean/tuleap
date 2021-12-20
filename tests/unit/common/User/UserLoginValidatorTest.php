<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\User;

use EventManager;
use ForgeConfig;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class UserLoginValidatorTest extends TestCase
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    private EventManager|Stub $event_manager;
    private UserLoginValidator $user_login_validator;
    private Stub|UserNameNormalizer $user_name_normalizer;

    protected function setUp(): void
    {
        ForgeConfig::set('homedir_prefix', "home/user");
        $this->event_manager        = $this->createStub(EventManager::class);
        $this->user_name_normalizer = $this->createStub(UserNameNormalizer::class);

        $this->user_login_validator = new UserLoginValidator(
            $this->user_name_normalizer,
            $this->event_manager
        );
    }

    public function testValidateLoginWithValidLogin(): void
    {
        $user = UserTestBuilder::aUser()->withUserName('mycurrentuser')->build();
        $this->user_name_normalizer->expects(self::never())->method("normalize");

        $this->event_manager->expects(self::never())->method("processEvent");
        $GLOBALS['Response']->expects(self::never())->method("addFeedback");

        $this->user_login_validator->validateUserLogin($user);
    }

    public function testValidateLoginWithNoUnixUser(): void
    {
        ForgeConfig::set('homedir_prefix', "");

        $user = UserTestBuilder::aUser()->withUserName('666')->build();
        $this->user_name_normalizer->expects(self::never())->method("normalize");

        $this->event_manager->expects(self::never())->method("processEvent");
        $GLOBALS['Response']->expects(self::never())->method("addFeedback");

        $this->user_login_validator->validateUserLogin($user);
    }

    public function testValidateLoginRenameUserIfNumeric(): void
    {
        $user = UserTestBuilder::aUser()->withUserName('666')->withId(123)->build();
        $this->user_name_normalizer->method("normalize")->willReturn('tlp-666');

        $this->event_manager->expects(self::once())->method("processEvent")->with("user_rename", [
            'user_id'  => 123,
            'new_name' => "tlp-666",
            "old_user" => $user,
        ]);

        $GLOBALS['Response']->method("addFeedback")->with(
            "warning",
            'Your old Tuleap login was not was not valid against the configuration of your platform. It will be changed to "tlp-666". If you use Ldap, it will not change your ldap login.'
        );
        $this->user_login_validator->validateUserLogin($user);
    }

    public function testValidateLoginRenameUserDoesntDoAnythingIfNameDontChange(): void
    {
        $user = UserTestBuilder::aUser()->withUserName('666')->withId(123)->build();
        $this->user_name_normalizer->method("normalize")->willReturn('666');

        $this->event_manager->expects(self::never())->method("processEvent");
        $GLOBALS['Response']->expects(self::never())->method("addFeedback");
        $this->user_login_validator->validateUserLogin($user);
    }

    public function testValidateLoginRenameUserDoesntDoAnythingExceptionIsThrowInNormalize(): void
    {
        $user = UserTestBuilder::aUser()->withUserName('666')->withId(123)->build();
        $this->user_name_normalizer->method("normalize")->willThrowException(
            new DataIncompatibleWithUsernameGenerationException()
        );

        $this->event_manager->expects(self::never())->method("processEvent");
        $GLOBALS['Response']->expects(self::never())->method("addFeedback");
        $this->user_login_validator->validateUserLogin($user);
    }
}
