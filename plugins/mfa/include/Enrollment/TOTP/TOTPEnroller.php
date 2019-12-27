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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\MFA\OTP\TOTP;
use Tuleap\MFA\OTP\TOTPMode;
use Tuleap\MFA\OTP\TOTPValidator;

class TOTPEnroller
{
    public const SESSION_KEY = 'plugin_mfa_enrollment_totp_secret';
    public const SECRET_SIZE = 32;

    /**
     * @var TOTPEnrollmentDAO
     */
    private $dao;
    /**
     * @var EncryptionKey
     */
    private $encryption_key;
    /**
     * @var TOTPValidator
     */
    private $totp_validator;
    /**
     * @var TOTPMode
     */
    private $totp_mode;

    public function __construct(
        TOTPEnrollmentDAO $dao,
        EncryptionKey $encryption_key,
        TOTPValidator $totp_validator,
        TOTPMode $totp_mode
    ) {
        $this->dao            = $dao;
        $this->encryption_key = $encryption_key;
        $this->totp_validator = $totp_validator;
        $this->totp_mode      = $totp_mode;
    }

    /**
     * @return bool
     */
    public function isUserEnrolled(\PFUser $user)
    {
        return $this->dao->isUserIDEnrolled($user->getId());
    }

    /**
     * @return ConcealedString
     */
    public function prepareSessionForEnrollment(array &$session_storage)
    {
        $secret = new ConcealedString(random_bytes(self::SECRET_SIZE));
        $session_storage[self::SESSION_KEY] = SymmetricCrypto::encrypt($secret, $this->encryption_key);
        return $secret;
    }

    /**
     * @throws EnrollmentTOTPMissingSessionSecretException
     * @throws \Tuleap\Cryptography\Exception\InvalidCiphertextException
     * @throws InvalidTOTPCodeException
     */
    public function enrollUser(\PFUser $user, array &$session_storage, $validation_totp_code)
    {
        if (! isset($session_storage[self::SESSION_KEY])) {
            throw new EnrollmentTOTPMissingSessionSecretException();
        }

        $encrypted_secret = $session_storage[self::SESSION_KEY];
        $secret           = SymmetricCrypto::decrypt($encrypted_secret, $this->encryption_key);

        unset($session_storage[self::SESSION_KEY]);

        $totp = new TOTP($this->totp_mode, $secret);

        if (! $this->totp_validator->validate($totp, $validation_totp_code, new \DateTimeImmutable())) {
            throw new InvalidTOTPCodeException();
        }

        $this->dao->enrollUserID($user->getId(), $encrypted_secret);
    }
}
