<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\MFA\Enrollment\TOTP;

require_once __DIR__ . '/../../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\MFA\OTP\TOTPMode;
use Tuleap\MFA\OTP\TOTPValidator;

class TOTPEnrollerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MockInterface
     */
    private $dao;
    /**
     * @var MockInterface
     */
    private $encryption_key;
    /**
     * @var MockInterface
     */
    private $totp_validator;
    /**
     * @var MockInterface
     */
    private $totp_mode;
    /**
     * @var MockInterface
     */
    private $user;

    protected function setUp(): void
    {
        $this->dao            = \Mockery::mock(TOTPEnrollmentDAO::class);
        $this->encryption_key = \Mockery::mock(EncryptionKey::class);
        $this->encryption_key->shouldReceive('getRawKeyMaterial')->andReturns(
            str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        );
        $this->totp_validator = \Mockery::mock(TOTPValidator::class);
        $this->totp_mode      = \Mockery::mock(TOTPMode::class);
        $this->user           = \Mockery::mock(\PFUser::class);
    }

    public function testUserIsEnrolledWhenProvidingAValidTOTPCode()
    {
        $totp_enroller = new TOTPEnroller($this->dao, $this->encryption_key, $this->totp_validator, $this->totp_mode);

        $session = [];

        $this->totp_validator->shouldReceive('validate')->andReturns(true);
        $this->user->shouldReceive('getId')->andReturns(101);
        $this->dao->shouldReceive('enrollUserID')->once();

        $totp_enroller->prepareSessionForEnrollment($session);
        $totp_enroller->enrollUser($this->user, $session, '000000');
    }

    public function testUserIsNotEnrolledWhenProvidingAInvalidTOTPCode()
    {
        $totp_enroller = new TOTPEnroller($this->dao, $this->encryption_key, $this->totp_validator, $this->totp_mode);

        $session = [];

        $this->totp_validator->shouldReceive('validate')->andReturns(false);
        $this->dao->shouldReceive('enrollUserID')->never();

        $totp_enroller->prepareSessionForEnrollment($session);

        $this->expectException(InvalidTOTPCodeException::class);

        $totp_enroller->enrollUser($this->user, $session, '111111');
    }

    public function testEnrollmentIsNotAttemptedIfSharedSecretIsNotAvailable()
    {
        $totp_enroller = new TOTPEnroller($this->dao, $this->encryption_key, $this->totp_validator, $this->totp_mode);

        $session = [];

        $this->totp_validator->shouldReceive('validate')->never();
        $this->dao->shouldReceive('enrollUserID')->never();

        $this->expectException(EnrollmentTOTPMissingSessionSecretException::class);

        $totp_enroller->enrollUser($this->user, $session, '000000');
    }
}
