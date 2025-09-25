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

namespace Tuleap\GitLFS\Transfer;

class Transfer
{
    public const string BASIC_TRANSFER_IDENTIFIER = 'basic';

    /**
     * @var string
     */
    private $identifier;

    public function __construct($identifier)
    {
        if (! \is_string($identifier)) {
            throw new \TypeError('Expected $identifier to be a string, got ' . gettype($identifier));
        }
        $this->identifier = $identifier;
    }

    /**
     * @return self
     */
    public static function buildBasicTransfer()
    {
        return new self(self::BASIC_TRANSFER_IDENTIFIER);
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
