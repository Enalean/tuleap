<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\GitReference;

class GitPullRequestReference
{
    public const string PR_NAMESPACE = 'refs/tlpr/';

    public const int STATUS_OK              = 0;
    public const int STATUS_NOT_YET_CREATED = 1;
    public const int STATUS_BROKEN          = 2;

    /**
     * @var int
     */
    private $git_reference_id;
    /**
     * @var int
     */
    private $status;

    public function __construct($git_reference_id, $status)
    {
        switch ($status) {
            case self::STATUS_OK:
            case self::STATUS_NOT_YET_CREATED:
            case self::STATUS_BROKEN:
                break;
            default:
                throw new \DomainException("Git pull reference status $status is unknown.");
        }

        $this->git_reference_id = $git_reference_id;
        $this->status           = $status;
    }

    /**
     * @return GitPullRequestReference
     */
    public static function buildReferenceWithUpdatedId($new_git_reference_id, self $existing_reference)
    {
        return new self($new_git_reference_id, $existing_reference->status);
    }

    /**
     * @return int
     */
    public function getGitReferenceId()
    {
        return $this->git_reference_id;
    }

    /**
     * @return string
     */
    public function getGitHeadReference()
    {
        return self::PR_NAMESPACE . $this->git_reference_id . '/head';
    }

    /**
     * @return bool
     */
    public function isGitReferenceUpdatable()
    {
        return $this->status === self::STATUS_OK || $this->status === self::STATUS_NOT_YET_CREATED;
    }

    /**
     * @return bool
     */
    public function isGitReferenceBroken()
    {
        return $this->status === self::STATUS_BROKEN;
    }

    public function isGitReferenceNeedToBeCreatedInRepository()
    {
        return $this->status === self::STATUS_NOT_YET_CREATED;
    }
}
