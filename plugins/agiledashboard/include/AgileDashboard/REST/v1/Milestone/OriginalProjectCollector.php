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

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use Project;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Artifact\Artifact;

final class OriginalProjectCollector implements Dispatchable
{
    public const string NAME = 'externalParentCollector';
    /**
     * @var Artifact
     */
    private $original_artifact;

    /**
     * @var Project|null
     */
    private $original_project;
    /**
     * @var \PFUser
     */
    private $user;

    public function __construct(Artifact $original_project, \PFUser $user)
    {
        $this->original_artifact = $original_project;
        $this->user              = $user;
    }

    public function getOriginalArtifact(): Artifact
    {
        return $this->original_artifact;
    }

    public function getOriginalProject(): ?Project
    {
        return $this->original_project;
    }

    public function setOriginalProject(Project $original_project): void
    {
        $this->original_project = $original_project;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }
}
