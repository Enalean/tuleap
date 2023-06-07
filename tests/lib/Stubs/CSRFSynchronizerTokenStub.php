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

namespace Tuleap\Test\Stubs;

use Tuleap\Request\CSRFSynchronizerTokenInterface;

class CSRFSynchronizerTokenStub implements CSRFSynchronizerTokenInterface
{
    private bool $has_been_checked = false;
    private string $token;


    private function __construct()
    {
        $random_number_generator = new \RandomNumberGenerator();
        $this->token             = $random_number_generator->getNumber();
    }

    public static function buildSelf(): self
    {
        return new self();
    }

    public function isValid($token): bool
    {
        $this->has_been_checked = true;
        return true;
    }

    public function check(?string $redirect_to = null, ?\Codendi_Request $request = null): void
    {
        $this->has_been_checked = true;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getTokenName(): string
    {
        return \CSRFSynchronizerToken::DEFAULT_TOKEN_NAME;
    }

    public function hasBeenChecked(): bool
    {
        return $this->has_been_checked;
    }
}
