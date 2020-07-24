<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han <xiphux@gmail.com>
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

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Head
 *
 * Represents a single head
 *
 */

/**
 * Head class
 *
 */
class Head extends Ref
{

    /**
     * commit
     *
     * Stores the commit internally
     *
     * @access protected
     */
    protected $commit;

    /**
     * __construct
     *
     * Instantiates head
     *
     * @access public
     * @param mixed $project the project
     * @param string $head head name
     * @param string $headHash head hash
     * @return mixed head object
     * @throws \Exception exception on invalid head or hash
     */
    public function __construct($project, $head, $headHash = '')
    {
        parent::__construct($project, 'heads', $head, $headHash);
    }

    /**
     * GetCommit
     *
     * Gets the commit for this head
     *
     * @access public
     * @return mixed commit object for this tag
     */
    public function GetCommit() // @codingStandardsIgnoreLine
    {
        if (! $this->commit) {
            $this->commit = $this->project->GetCommit($this->GetHash());
        }

        return $this->commit;
    }

    /**
     * CompareAge
     *
     * Compares two heads by age
     *
     * @access public
     * @static
     * @param mixed $a first head
     * @param mixed $b second head
     * @return int comparison result
     */
    public static function CompareAge($a, $b) // @codingStandardsIgnoreLine
    {
        $aObj = $a->GetCommit();
        $bObj = $b->GetCommit();
        return Commit::CompareAge($aObj, $bObj);
    }
}
