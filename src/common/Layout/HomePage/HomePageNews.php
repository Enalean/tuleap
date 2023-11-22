<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Layout\HomePage;

use PFUser;
use Project;
use Tuleap\Date\DateHelper;

class HomePageNews
{
    /**
     * @var \Codendi_HTMLPurifier
     */
    private $purifier;
    /**
     * @var string
     * @psalm-readonly
     */
    private $unescaped_details;
    /**
     * @var Project
     * @psalm-readonly
     */
    private $project;

    /**
     * @var string
     * @psalm-readonly
     */
    public $summary;
    /**
     * @var string
     * @psalm-readonly
     */
    public $purified_time_ago;
    /**
     * @var string
     * @psalm-readonly
     */
    public $project_url;
    /**
     * @var string
     * @psalm-readonly
     */
    public $project_name;
    /**
     * @var string
     * @psalm-readonly
     */
    public $author_url;
    /**
     * @var string
     * @psalm-readonly
     */
    public $author_name;
    /**
     * @var string
     * @psalm-readonly
     */
    public $author_avatar_url;

    public function __construct(
        \Codendi_HTMLPurifier $purifier,
        Project $project,
        PFUser $author,
        \DateTimeImmutable $date,
        string $summary,
        string $details,
    ) {
        $this->summary           = $summary;
        $this->purified_time_ago = DateHelper::relativeDateInlineContext((int) $date->getTimestamp(), $author);
        $this->project_url       = $project->getUrl();
        $this->project_name      = $project->getPublicName();
        $this->author_url        = $author->getPublicProfileUrl();
        $this->author_name       = $author->getRealName();
        $this->author_avatar_url = $author->getAvatarUrl();

        $this->purifier          = $purifier;
        $this->unescaped_details = $details;
        $this->project           = $project;
    }

    public function details()
    {
        return $this->purifier->purify($this->unescaped_details, CODENDI_PURIFIER_BASIC, $this->project->getID());
    }
}
