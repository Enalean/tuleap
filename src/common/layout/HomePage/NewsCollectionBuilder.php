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
 *
 */

namespace Tuleap\layout\HomePage;

use ForgeConfig;
use Tuleap\News\NewsDao;

class NewsCollectionBuilder
{
    public const CONFIG_DISPLAY_NEWS = 'display_homepage_news';
    /**
     * @var NewsDao
     */
    private $dao;
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \Codendi_HTMLPurifier
     */
    private $purifier;

    public function __construct(NewsDao $dao, \ProjectManager $project_manager, \UserManager $user_manager, \Codendi_HTMLPurifier $purifier)
    {
        $this->dao = $dao;
        $this->project_manager = $project_manager;
        $this->user_manager = $user_manager;
        $this->purifier = $purifier;
    }

    public function build()
    {
        $all_news = [];
        if (ForgeConfig::get(self::CONFIG_DISPLAY_NEWS)) {
            foreach ($this->dao->getNewsForSiteHomePage() as $news) {
                $all_news[] = new HomePageNews(
                    $this->purifier,
                    $this->project_manager->getProject($news['group_id']),
                    $this->user_manager->getUserById($news['submitted_by']),
                    new \DateTimeImmutable('@' . $news['date']),
                    $news['summary'],
                    $news['details']
                );
            }
        }
        return new NewsCollection($all_news);
    }
}
