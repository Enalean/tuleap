<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

/**
 * Store informations about a push
 */
class Git_Hook_PushDetails
{
    public const ACTION_ERROR  = 'error';
    public const ACTION_CREATE = 'create';
    public const ACTION_DELETE = 'delete';
    public const ACTION_UPDATE = 'update';

    public const OBJECT_TYPE_COMMIT = 'commit';
    public const OBJECT_TYPE_TAG    = 'tag';

    public const TYPE_BRANCH          = 'branch';
    public const TYPE_UNANNOTATED_TAG = 'tag';
    public const TYPE_ANNOTATED_TAG   = 'annotated_tag';
    public const TYPE_TRACKING_BRANCH = 'tracking_branch';
    public const TYPE_UNKNOWN         = '';

    private $type;
    private $rev_type;
    private $revision_list;
    private $repository;
    private $user;
    private $refname;

    public function __construct(GitRepository $repository, PFUser $user, $refname, $type, $rev_type, array $revision_list)
    {
        $this->repository    = $repository;
        $this->user          = $user;
        $this->refname       = $refname;
        $this->type          = $type;
        $this->rev_type      = $rev_type;
        $this->revision_list = $revision_list;
    }

    /**
     * The repository where the push was made
     *
     *  @return GitRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Who made the push
     *
     * @return PFUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * On which element in the repository the push was done (branch, tag, etc)
     *
     * @return String
     */
    public function getRefname()
    {
        return $this->refname;
    }

    /**
     * Operation type (create, update, delete)
     *
     * @return String
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * What object type (commit, tag)
     *
     * @return String
     */
    public function getRevType()
    {
        return $this->rev_type;
    }

    /**
     * What kind of reference was updated (tag, annotated_tag, commit, tracking_branch)
     *
     * @return String
     */
    public function getRefnameType()
    {
        if (strpos($this->refname, 'refs/tags/') === 0) {
            switch ($this->rev_type) {
                case self::OBJECT_TYPE_COMMIT:
                    return self::TYPE_UNANNOTATED_TAG;
                    break;
                case self::OBJECT_TYPE_TAG:
                    return self::TYPE_ANNOTATED_TAG;
                    break;
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
     * @return String[] A list of sha1
     */
    public function getRevisionList()
    {
        return $this->revision_list;
    }
}
