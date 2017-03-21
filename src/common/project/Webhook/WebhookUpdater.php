<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Project\Webhook;

class WebhookUpdater
{
    /**
     * @var WebhookDao
     */
    private $dao;

    public function __construct(WebhookDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @throws WebhookDataAccessException
     * @throws WebhookMalformedDataException
     */
    public function add($name, $url)
    {
        $is_data_valid = $this->isDataValid($name, $url);

        if (! $is_data_valid) {
            throw new WebhookMalformedDataException();
        }

        $has_been_created = $this->dao->createWebhook($name, $url);

        if (! $has_been_created) {
            throw new WebhookDataAccessException();
        }
    }

    /**
     * @return bool
     */
    private function isDataValid($name, $url)
    {
        $string_validator = new \Valid_String();
        $uri_validator    = new \Valid_HTTPURI();

        return $string_validator->validate($name) && $uri_validator->validate($url);
    }
}
