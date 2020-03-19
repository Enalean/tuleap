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

namespace Tuleap\Layout\Feedback;

class FeedbackSerializer
{
    /**
     * @var \FeedbackDao
     */
    private $dao;

    public function __construct(\FeedbackDao $feedback_dao)
    {
        $this->dao = $feedback_dao;
    }

    public function serialize(\PFUser $user, NewFeedback $feedback)
    {
        $logs = [
            [
                'level'  => $feedback->getLevel(),
                'msg'    => $feedback->getMessage(),
                'purify' => CODENDI_PURIFIER_CONVERT_HTML
            ]
        ];
        $this->dao->create($user->getSessionId(), $logs);
    }
}
