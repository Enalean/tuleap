<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Changeset;

class ChangesetFromXmlPresenter
{
    /**
     * @var string|null
     */
    public $user_name;
    /**
     * @var string
     */
    public $profil_url;
    /**
     * @var false|string
     */
    public $imported_on;

    public function __construct(\PFUser $user, int $timestamp)
    {
        $this->user_name  = $user->getName();
        $this->profil_url = $user->getPublicProfileUrl();

        $this->imported_on = format_date($GLOBALS['Language']->getText('system', 'datefmt'), $timestamp);
    }
}
