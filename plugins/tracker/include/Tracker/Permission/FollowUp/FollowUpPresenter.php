<?php
/**
 *  Copyright (c) Maximaster, 2020. All rights reserved
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Permission\FollowUp;

use Tuleap\Tracker\Permission\FollowUp\PrivateComments\PermissionsOnPrivateCommentsUpdateController;

class FollowUpPresenter
{
    /**
     * @var string
     */
    public $update_url;

    /**
     * @var array
     */
    public $ugroup_list;

    public function __construct(\Tracker $tracker, array $ugroup_list)
    {
        $this->update_url = PermissionsOnPrivateCommentsUpdateController::getUrl($tracker);
        $this->ugroup_list = $ugroup_list;
    }
}
