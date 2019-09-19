<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication;

class SessionState
{
    /**
     * @var string
     */
    private $secret_key;
    /**
     * @var string
     */
    private $return_to;
    /**
     * @var string
     */
    private $nonce;

    public function __construct($secret_key, $return_to, $nonce)
    {
        $this->secret_key = $secret_key;
        $this->return_to  = $return_to;
        $this->nonce      = $nonce;
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return $this->secret_key;
    }

    /**
     * @return string
     */
    public function getReturnTo()
    {
        return $this->return_to;
    }

    /**
     * @return string
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @return \stdClass
     */
    public function convertToMinimalRepresentation()
    {
        $representation = new \stdClass();
        $representation->secret_key = $this->secret_key;
        $representation->return_to  = $this->return_to;
        $representation->nonce      = $this->nonce;
        return $representation;
    }

    public static function buildFromMinimalRepresentation(\stdClass $representation)
    {
        if (! isset($representation->secret_key, $representation->return_to, $representation->nonce)) {
            throw new \InvalidArgumentException('Given $representation is incorrectly formatted');
        }
        return new self(
            $representation->secret_key,
            $representation->return_to,
            $representation->nonce
        );
    }
}
