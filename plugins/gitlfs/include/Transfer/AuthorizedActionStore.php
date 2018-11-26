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

namespace Tuleap\GitLFS\Transfer;

use Tuleap\GitLFS\Authorization\Action\AuthorizedAction;

class AuthorizedActionStore
{
    /**
     * @var AuthorizedAction
     */
    private $authorized_action;

    public function keepAuthorizedAction(AuthorizedAction $authorized_action)
    {
        if ($this->authorized_action !== null) {
            throw new \LogicException(
                'The authorized action store already has a value and cannot be reused, please fix your code to avoid this situation.'
            );
        }
        $this->authorized_action = $authorized_action;
    }

    /**
     * @return AuthorizedAction
     */
    public function getAuthorizedAction()
    {
        if ($this->authorized_action === null) {
            throw new \LogicException(
                'The authorized action store is empty, please add content to it before trying to get something out of it.'
            );
        }
        return $this->authorized_action;
    }
}
