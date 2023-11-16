<?php
/**
* Copyright (c) Enalean, 2016 - Present. All rights reserved
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
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Tuleap. If not, see <http://www.gnu.org/licenses/
*/
namespace Tuleap\News\Admin;

use CSRFSynchronizerToken;
use ProjectManager;
use Tuleap\Date\DateHelper;
use UserManager;

class AdminNewsBuilder
{
    private $one_week;
    /**
     * @var NewsRetriever
     */
    private $news_manager;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        CSRFSynchronizerToken $csrf_token,
        NewsRetriever $news_manager,
        ProjectManager $project_manager,
        UserManager $user_manager,
    ) {
        $this->one_week        = 7 * 24 * 3600;
        $this->csrf_token      = $csrf_token;
        $this->news_manager    = $news_manager;
        $this->project_manager = $project_manager;
        $this->user_manager    = $user_manager;
    }

    public function getRejectedNewsPresenter()
    {
        $old_date = (time() - $this->one_week);

        $title     = $GLOBALS['Language']->getText('news_admin_index', 'title');
        $presenter = new AdminRejectedNewsPresenter(
            $this->csrf_token,
            $title,
            $this->buildNewsList($this->news_manager->getRejectedNews($old_date), 'rejected_news')
        );

        return $presenter;
    }

    public function getPublishedNewsPresenter()
    {
        $old_date = (time() - $this->one_week);

        $title     = $GLOBALS['Language']->getText('news_admin_index', 'title');
        $presenter = new AdminPublishedNewsPresenter(
            $this->csrf_token,
            $title,
            $this->buildNewsList($this->news_manager->getPublishedNews($old_date), 'published_news')
        );

        return $presenter;
    }

    public function getWaitingPublicationNewsPresenter()
    {
        $news      = $this->news_manager->getWaitingPublicationNews();
        $title     = $GLOBALS['Language']->getText('news_admin_index', 'title');
        $presenter = new AdminWaitingPublicationPresenter(
            $this->csrf_token,
            $title,
            $this->buildNewsList($news, 'waiting_publication')
        );

        return $presenter;
    }

    public function buildNewsList($result, $current_tab)
    {
        $news_list = [];

        foreach ($result as $row) {
            $news_list[] = $this->getNewsDetailsPresenter($row, $current_tab);
        }

        return $news_list;
    }

    public function getNewsDetailsPresenter($row, $current_tab)
    {
        return new AdminNewsPresenter(
            $this->csrf_token,
            $row['id'],
            $row['summary'],
            $row['details'],
            $row['group_id'],
            $row['is_approved'] === NewsRetriever::NEWS_STATUS_REQUESTED_PUBLICATION,
            $this->project_manager->getProject($row['group_id'])->getPublicName(),
            $this->user_manager->getUserById($row['submitted_by'])->getRealName(),
            $this->user_manager->getUserById($row['submitted_by'])->getAvatarUrl(),
            DateHelper::formatForLanguage($GLOBALS['Language'], $row['date']),
            $current_tab
        );
    }
}
