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

use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\MFA\OTP\TOTP;
use Tuleap\MFA\OTP\TOTPMode;

class TOTPRetriever
{
    /**
     * @var TOTPEnrollmentDAO
     */
    private $dao;
    /**
     * @var EncryptionKey
     */
    private $encryption_key;
    /**
     * @var TOTPMode
     */
    private $totp_mode;

    public function __construct(TOTPEnrollmentDAO $dao, EncryptionKey $encryption_key, TOTPMode $totp_mode)
    {
        $this->dao            = $dao;
        $this->encryption_key = $encryption_key;
        $this->totp_mode      = $totp_mode;
    }

    /**
     * @return TOTP
     * @throws NotFoundTOTPEnrollmentException
     * @throws \Tuleap\Cryptography\Exception\InvalidCiphertextException
     */
    public function getTOTP(\PFUser $user)
    {
        $encrypted_secret = $this->dao->getSecretByUserID($user->getId());
        if ($encrypted_secret === false) {
            throw new NotFoundTOTPEnrollmentException($user);
        }
        $secret = SymmetricCrypto::decrypt($encrypted_secret, $this->encryption_key);

        return new TOTP($this->totp_mode, $secret);
    }
}
