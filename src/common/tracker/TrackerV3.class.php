<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

class TrackerV3
{
    public const REFERENCE_NATURE = 'artifact';

    /**
     * @var ArtifactDao
     */
    private $dao;

    /**
     * @var TrackerV3
     */
    private static $instance;

    /**
     * @var bool
     */
    private $available = null;

    public function __construct(ArtifactDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return TrackerV3
     */
    public static function instance()
    {
        if (! self::$instance) {
            self::$instance = new TrackerV3(new ArtifactDao());
        }
        return self::$instance;
    }

    /**
     * Return True if Trackerv3 are available on the platform
     *
     * @return bool
     */
    public function available()
    {
        if ($this->available === null) {
            $this->available = $this->dao->artifactTableExists();
        }
        return $this->available;
    }
}
