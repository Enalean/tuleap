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

namespace Tuleap\GitLFS\Batch\Response\Action;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;

class BatchResponseActionContent implements \JsonSerializable
{
    /**
     * @var BatchResponseActionHref
     */
    private $href;
    /**
     * @var SplitToken
     */
    private $action_authorization_token;
    /**
     * @var SplitTokenFormatter
     */
    private $token_header_formatter;
    /**
     * @var int
     */
    private $expires_in;

    public function __construct(
        BatchResponseActionHref $href,
        SplitToken $action_authorization_token,
        SplitTokenFormatter $token_header_formatter,
        $expires_in
    ) {
        $this->href                       = $href;
        $this->action_authorization_token = $action_authorization_token;
        $this->token_header_formatter     = $token_header_formatter;
        $this->expires_in                 = $expires_in;
    }

    public function jsonSerialize()
    {
        return [
            'href'       => $this->href->getHref(),
            'expires_in' => $this->expires_in,
            'header'     => [
                'Authorization' => (string) $this->token_header_formatter->getIdentifier($this->action_authorization_token)
            ]
        ];
    }
}
