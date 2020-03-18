<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap;

/**
 * @psalm-immutable
 */
final class CSRFSynchronizerTokenPresenter
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $token;

    private function __construct(string $name, string $token)
    {
        $this->name  = $name;
        $this->token = $token;
    }

    public static function fromToken(\CSRFSynchronizerToken $token): self
    {
        return new self($token->getTokenName(), $token->getToken());
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getTokenName(): string
    {
        return $this->name;
    }
}
