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

class NewsCollection
{
    private $news = [];
    private $has_news;

    public function __construct(array $news)
    {
        $this->news = $news;
        $this->has_news = count($news) > 0;
    }

    /**
     * @return array
     */
    public function getNews()
    {
        return $this->news;
    }

    /**
     * @return bool
     */
    public function hasNews()
    {
        return $this->has_news;
    }
}
