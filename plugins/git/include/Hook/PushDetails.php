<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook;

use GitRepository;
use PFUser;

/**
 * Store informations about a push
 */
class PushDetails
{
    public const string ACTION_ERROR  = 'error';
    public const string ACTION_CREATE = 'create';
    public const string ACTION_DELETE = 'delete';
    public const string ACTION_UPDATE = 'update';

    public const string OBJECT_TYPE_COMMIT = 'commit';
    public const string OBJECT_TYPE_TAG    = 'tag';

    public const string TYPE_BRANCH          = 'branch';
    public const string TYPE_UNANNOTATED_TAG = 'tag';
    public const string TYPE_ANNOTATED_TAG   = 'annotated_tag';
    public const string TYPE_TRACKING_BRANCH = 'tracking_branch';
    public const string TYPE_UNKNOWN         = '';

    public function __construct(
        private GitRepository $repository,
        private PFUser $user,
        private string $refname,
        private string $type,
        private string $rev_type,
        private array $revision_list,
    ) {
    }

    /**
     * The repository where the push was made
     */
    public function getRepository(): GitRepository
    {
        return $this->repository;
    }

    /**
     * Who made the push
     */
    public function getUser(): PFUser
    {
        return $this->user;
    }

    /**
     * On which element in the repository the push was done (branch, tag, etc)
     */
    public function getRefname(): string
    {
        return $this->refname;
    }

    /**
     * Operation type (create, update, delete)
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * What object type (commit, tag)
     */
    public function getRevType(): string
    {
        return $this->rev_type;
    }

    /**
     * What kind of reference was updated (tag, annotated_tag, commit, tracking_branch)
     */
    public function getRefnameType(): string
    {
        if (strpos($this->refname, 'refs/tags/') === 0) {
            switch ($this->rev_type) {
                case self::OBJECT_TYPE_COMMIT:
                    return self::TYPE_UNANNOTATED_TAG;
                case self::OBJECT_TYPE_TAG:
                    return self::TYPE_ANNOTATED_TAG;
            }
        } elseif ($this->rev_type == self::OBJECT_TYPE_COMMIT) {
            if (strpos($this->refname, 'refs/heads/') === 0) {
                return self::TYPE_BRANCH;
            } elseif (strpos($this->refname, 'refs/remotes/') === 0) {
                return self::TYPE_TRACKING_BRANCH;
            }
        }
        return self::TYPE_UNKNOWN;
    }

    /**
     * List of impacted revisions
     *
     * @return string[] A list of sha1
     */
    public function getRevisionList(): array
    {
        return $this->revision_list;
    }
}
