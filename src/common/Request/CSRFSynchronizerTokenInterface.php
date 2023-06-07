<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Request;

use Codendi_Request;

interface CSRFSynchronizerTokenInterface
{
    public function getToken(): string;

    public function getTokenName(): string;

    /**
     * Check that a challenge token is valid.
     * @see Constructor
     *
     * @param string $token The token to check against what is stored in the user session
     *
     * @return bool true if token valid, false otherwise
     */
    public function isValid($token): bool;

    /**
     * Redirect to somewhere else if the token in request is not valid
     *
     * @param Codendi_Request $request     The request object, if null then use HTTPRequest
     * @param string          $redirect_to Url to be redirected to in case of error.
     */
    public function check(?string $redirect_to = null, ?Codendi_Request $request = null): void;
}
