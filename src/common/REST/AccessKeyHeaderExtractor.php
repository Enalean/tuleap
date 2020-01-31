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

namespace Tuleap\REST;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;

class AccessKeyHeaderExtractor
{
    private const PHP_HTTP_ACCESS_KEY_HEADER = 'HTTP_X_AUTH_ACCESSKEY';

    /**
     * @var SplitTokenIdentifierTranslator
     */
    private $access_key_identifier_unserializer;
    /**
     * @var array
     */
    private $server_information;

    public function __construct(SplitTokenIdentifierTranslator $access_key_identifier_unserializer, array $server_information)
    {
        $this->access_key_identifier_unserializer = $access_key_identifier_unserializer;
        $this->server_information                 = $server_information;
    }


    public function isAccessKeyHeaderPresent(): bool
    {
        return isset($this->server_information[self::PHP_HTTP_ACCESS_KEY_HEADER]);
    }

    /**
     * @throws SplitTokenException
     */
    public function extractAccessKey(): ?SplitToken
    {
        if (! $this->isAccessKeyHeaderPresent()) {
            return null;
        }

        $access_key_identifier = $this->server_information[self::PHP_HTTP_ACCESS_KEY_HEADER];
        return $this->access_key_identifier_unserializer->getSplitToken(new ConcealedString($access_key_identifier));
    }
}
