<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
declare(strict_types=1);

namespace Tuleap\Core\RSS\News;

use ForgeConfig;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\News\NewsDao;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Laminas\Feed\Writer\Feed;

class LatestNewsController implements DispatchableWithRequest
{

    /**
     * @var NewsDao
     */
    private $dao;
    /**
     * @var \Codendi_HTMLPurifier
     */
    private $html_purifier;

    public function __construct(NewsDao $dao, \Codendi_HTMLPurifier $html_purifier)
    {
        $this->dao = $dao;
        $this->html_purifier = $html_purifier;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $feed = new Feed();
        $feed->setTitle(sprintf(_('%s news'), ForgeConfig::get('sys_name')));
        $feed->setCopyright(sprintf(_('Copyright (c) %s, %s Team, 2001-%d. All Rights Reserved'), ForgeConfig::get('sys_long_org_name'), ForgeConfig::get('sys_name'), date('Y')));
        $feed->setDescription(sprintf(_('%s project news highlights'), ForgeConfig::get('sys_name')));
        $feed->setLink($request->getServerUrl());
        $feed->setLanguage('en-us');
        $feed->setDateModified($request->getTime());

        foreach ($this->dao->getNewsForSitePublicRSSFeed() as $row) {
            $entry = $feed->createEntry();
            $entry->setTitle($this->html_purifier->purify($row['summary']));
            $entry->setLink($request->getServerUrl() . '/forum/forum.php?forum_id=' . (int) $row['forum_id']);
            $entry->setDescription($this->html_purifier->purify($row['details']));
            $feed->addEntry($entry);
        }

        header('Content-type: application/rss+xml');
        echo $feed->export('rss');
    }
}
