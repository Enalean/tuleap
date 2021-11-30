<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Label;

use Tuleap\PullRequest\PullRequest;

class LabeledPullRequestPresenter
{
    public $title;

    public $purified_repository_link;

    public $purified_user_link;

    public $creation_date;

    public function __construct(
        PullRequest $pull_request,
        $repository_link,
        $user_link,
    ) {
        $this->purified_repository_link = \Codendi_HTMLPurifier::instance()->purify(
            $repository_link,
            CODENDI_PURIFIER_LIGHT
        );
        $this->purified_user_link       = \Codendi_HTMLPurifier::instance()->purify(
            $user_link,
            CODENDI_PURIFIER_LIGHT
        );
        $this->title                    = $pull_request->getTitle();
        $this->creation_date            = format_date(
            $GLOBALS['Language']->getText('system', 'datefmt'),
            $pull_request->getCreationDate()
        );
    }
}
