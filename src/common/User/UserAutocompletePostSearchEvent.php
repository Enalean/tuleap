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

namespace Tuleap\User;

use Tuleap\Event\Dispatchable;

class UserAutocompletePostSearchEvent implements Dispatchable
{
    const NAME = 'user_autocomplete_post_search';

    /**
     * @var array
     */
    private $user_list;
    /**
     * @var mixed
     */
    private $additional_information;

    public function __construct(array $user_list, $additional_information_json)
    {
        $this->user_list              = $user_list;
        $this->additional_information = json_decode($additional_information_json, true);
    }

    /**
     * @return array
     */
    public function getUserList()
    {
        return $this->user_list;
    }

    public function setUserList(array $user_list)
    {
        $this->user_list = $user_list;
    }

    /**
     * @return mixed
     */
    public function getAdditionalInformation()
    {
        return $this->additional_information;
    }
}
