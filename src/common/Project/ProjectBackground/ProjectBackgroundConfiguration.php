<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\ProjectBackground;

class ProjectBackgroundConfiguration
{
    /**
     * Should we display project background on every pages ('1') or only on dashboard ('0')? Default is 0
     *
     * @tlp-config-key
     */
    public const CONFIG_FEATURE_FLAG_PROJECT_BACKGROUND = 'feature_flag_project_background';

    /**
     * @var ProjectBackgroundDao
     */
    private $dao;

    public function __construct(ProjectBackgroundDao $dao)
    {
        $this->dao = $dao;
    }

    public static function buildSelf(): self
    {
        return new self(new ProjectBackgroundDao());
    }

    public function getBackground(\Project $project): ?string
    {
        if (\ForgeConfig::getInt(self::CONFIG_FEATURE_FLAG_PROJECT_BACKGROUND, 0) === 0) {
            return null;
        }

        return $this->getBackgroundIgnoringFeatureFlag($project);
    }

    public function getBackgroundIgnoringFeatureFlag(\Project $project): ?string
    {
        $background = $this->dao->getBackground((int) $project->getID());
        if (! $background) {
            return null;
        }

        if (! in_array($background, ProjectBackgroundName::ALLOWED, true)) {
            return null;
        }

        return $background;
    }
}
