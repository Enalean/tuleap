<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Svn\Repository;

use Tuleap\Svn\Admin\ImmutableTag;

class Settings
{
    /**
     * @var array
     */
    private $commit_rules;

    /**
     * @var ImmutableTag
     */
    private $immutable_tag;

    public function __construct(array $commit_rules, ImmutableTag $immutable_tag)
    {
        $this->commit_rules  = $commit_rules;
        $this->immutable_tag = $immutable_tag;
    }

    /**
     * @return array|null
     */
    public function getCommitRules()
    {
        return $this->commit_rules;
    }

    /**
     * @return ImmutableTag|null
     */
    public function getImmutableTag()
    {
        return $this->immutable_tag;
    }

    public function hasSettings()
    {
        return count($this->commit_rules) > 0
            || count($this->immutable_tag->getPaths()) > 0
            || count($this->immutable_tag->getWhitelist()) > 0;
    }
}
