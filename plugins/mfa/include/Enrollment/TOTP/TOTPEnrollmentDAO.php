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

use Tuleap\DB\DataAccessObject;

class TOTPEnrollmentDAO extends DataAccessObject
{
    public function isUserIDEnrolled($user_id)
    {
        $res = $this->getDB()->single(
            'SELECT TRUE FROM plugin_mfa_enrollment_totp WHERE user_id = ?',
            [$user_id]
        );
        return $res !== false;
    }

    public function enrollUserID($user_id, $secret)
    {
        $this->getDB()->run(
            'INSERT INTO plugin_mfa_enrollment_totp(user_id, secret) VALUES (?,?) ON DUPLICATE KEY UPDATE secret = ?',
            $user_id,
            $secret,
            $secret
        );
    }

    /**
     * @return false|string
     */
    public function getSecretByUserID($user_id)
    {
        return $this->getDB()->single(
            'SELECT secret FROM plugin_mfa_enrollment_totp WHERE user_id = ?',
            [$user_id]
        );
    }
}
