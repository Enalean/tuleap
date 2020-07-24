<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Admin;

use Rule_Email;

class MailReceivedFromUserExtractor
{

    private $valid_adresses   = [];
    private $invalid_adresses = [];

    public function __construct($addresses)
    {
        $list = $this->splitEmails($addresses);
        $rule = new Rule_Email();

        foreach ($list as $address) {
            if ($rule->isValid($address)) {
                $this->valid_adresses[] = $address;
            } else {
                $this->invalid_adresses[] = $address;
            }
        }
    }

    public function getValidAdresses()
    {
        return $this->valid_adresses;
    }

    public function getInvalidAdresses()
    {
        return $this->invalid_adresses;
    }

    private function splitEmails($addresses)
    {
        $addresses = preg_replace("/\s+[,;]/", ",", $addresses);
        $addresses = preg_replace("/[,;]\s+/", ",", $addresses);
        $addresses = str_replace(";", ",", $addresses);

        return preg_split('/[,]+/', strtolower(trim($addresses)));
    }
}
