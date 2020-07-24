<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\Permissions;

use Feedback;

class TemplateFineGrainedPermissionSaver
{
    public const BRANCH_PATTERN_PREFIX = 'refs/heads/';
    public const TAG_PATTERN_PREFIX    = 'refs/tags/';

    /**
     * @var FineGrainedDao
     */
    private $dao;

    public function __construct(FineGrainedDao $dao)
    {
        $this->dao = $dao;
    }

    public function saveTagPermission(DefaultFineGrainedPermission $permission)
    {
        $pattern = self::TAG_PATTERN_PREFIX . $permission->getPatternWithoutPrefix();

        return $this->save($permission, $pattern);
    }

    public function saveBranchPermission(DefaultFineGrainedPermission $permission)
    {
        $pattern = self::BRANCH_PATTERN_PREFIX . $permission->getPatternWithoutPrefix();

        return $this->save($permission, $pattern);
    }

    private function save(DefaultFineGrainedPermission $permission, $pattern)
    {
        if ($this->dao->getPermissionIdByPatternForProject($permission->getProjectId(), $pattern)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(dgettext('tuleap-git', 'Pattern %1$s is already existing.'), $pattern)
            );

            return;
        }

        return $this->dao->saveDefault(
            $permission->getProjectId(),
            $pattern,
            $this->getWriterIds($permission),
            $this->getRewinderIds($permission)
        );
    }

    /**
     * @return array
     */
    private function getWriterIds(DefaultFineGrainedPermission $permission)
    {
        $ids = [];
        foreach ($permission->getWritersUgroup() as $ugroup) {
            $ids[] = $ugroup->getId();
        }

        return $ids;
    }

    /**
     * @return array
     */
    private function getRewinderIds(DefaultFineGrainedPermission $permission)
    {
        $ids = [];
        foreach ($permission->getRewindersUgroup() as $ugroup) {
            $ids[] = $ugroup->getId();
        }

        return $ids;
    }

    public function updateTemplatePermission(DefaultFineGrainedPermission $permission)
    {
        return $this->dao->updateDefaultPermission(
            $permission->getId(),
            $this->getWriterIds($permission),
            $this->getRewinderIds($permission)
        );
    }
}
