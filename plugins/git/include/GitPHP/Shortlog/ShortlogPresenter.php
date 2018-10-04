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

namespace GitPHP\Shortlog;

class ShortlogPresenter
{
    /** @var ShortlogCommitsPerDayPresenter[] */
    public $commits = [];

    /** @var ShortlogCommitsPerDayPresenter */
    public $first_commit;

    /**
     * @param ShortlogCommitPresenter[] $commits_presenters
     */
    public function __construct(array $commits_presenters)
    {
        foreach ($commits_presenters as $commit_presenter) {
            $committed_on  = new \DateTimeImmutable('@' . $commit_presenter->commit->GetCommitterEpoch());
            $committed_day = $committed_on->format($GLOBALS['Language']->getText('system', 'datefmt_short'));
            if (! isset($this->commits[$committed_day])) {
                $this->commits[$committed_day] = new ShortlogCommitsPerDayPresenter($committed_day);
            }
            $this->commits[$committed_day]->add($commit_presenter);
        }

        if (count($commits_presenters) > 0) {
            $this->first_commit = array_values($commits_presenters)[0];
        }
    }
}
