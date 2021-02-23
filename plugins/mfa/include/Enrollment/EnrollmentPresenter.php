<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\MFA\Enrollment;

use ParagonIE\ConstantTime\Base32;
use Tuleap\Cryptography\ConcealedString;

class EnrollmentPresenter
{
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var string
     */
    public $secret;
    /**
     * @var bool
     */
    public $is_user_already_registered;

    public function __construct(\CSRFSynchronizerToken $csrf_token, ConcealedString $secret, $is_user_already_registered)
    {
        $this->csrf_token                 = $csrf_token;
        $this->secret                     = Base32::encode($secret);
        $this->is_user_already_registered = $is_user_already_registered;
    }
}
