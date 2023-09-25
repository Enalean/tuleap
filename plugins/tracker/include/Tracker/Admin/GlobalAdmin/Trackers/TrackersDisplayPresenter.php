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

namespace Tuleap\Tracker\Admin\GlobalAdmin\Trackers;

use Tuleap\Tracker\Admin\GlobalAdmin\ArtifactLinks\ArtifactLinksController;

/**
 * @psalm-immutable
 */
final class TrackersDisplayPresenter
{
    /**
     * @var string
     */
    public $trackers_url;
    /**
     * @var string
     */
    public $artifact_links_url;
    /**
     * @var TrackerPresenter[]
     */
    public $trackers;
    /**
     * @var string
     */
    public $promoted_post_url;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var string
     */
    public $creation_url;

    /**
     * @param TrackerPresenter[] $trackers
     */
    public function __construct(
        \Project $project,
        array $trackers,
        \CSRFSynchronizerToken $csrf_token,
        public bool $is_project_allowed_to_promote_trackers_in_sidebar,
    ) {
        $this->trackers_url       = TrackersDisplayController::getURL($project);
        $this->artifact_links_url = ArtifactLinksController::getURL($project);
        $this->promoted_post_url  = PromoteTrackersController::getURL($project);
        $this->creation_url       = TRACKER_BASE_URL . '/' . urlencode($project->getUnixNameLowerCase()) . '/new';

        $this->trackers = $trackers;
        usort($this->trackers, static function (TrackerPresenter $a, TrackerPresenter $b): int {
            return strnatcasecmp($a->label, $b->label);
        });

        $this->csrf_token = $csrf_token;
    }
}
