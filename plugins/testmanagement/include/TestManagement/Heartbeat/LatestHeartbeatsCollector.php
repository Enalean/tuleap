<?php
/*
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

namespace Tuleap\TestManagement\Heartbeat;

use Codendi_HTMLPurifier;
use PFUser;
use Project;
use Tracker_ArtifactFactory;
use Tuleap\Project\HeartbeatsEntry;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\Tracker\Artifact\Artifact;

class LatestHeartbeatsCollector
{
    public function __construct(
        private ExecutionDao $dao,
        private Tracker_ArtifactFactory $factory,
        private \UserManager $user_manager,
    ) {
    }

    public static function build(): self
    {
        return new self(
            new ExecutionDao(),
            Tracker_ArtifactFactory::instance(),
            \UserManager::instance()
        );
    }

    public function collect(\Tuleap\Project\HeartbeatsEntryCollection $collection): void
    {
        $project   = $collection->getProject();
        $user      = $collection->getUser();
        $artifacts = $this->dao->searchLastTestExecUpdate(
            (int) $project->getID(),
            (int) $collection::NB_MAX_ENTRIES,
            $user->getUgroups($project->getID(), [])
        );

        if (! $artifacts) {
            return;
        }

        foreach ($artifacts as $row) {
            $artifact = $this->factory->getInstanceFromRow($row);
            if (! $artifact->userCanView($user)) {
                continue;
            }

            $collection->add(
                new HeartbeatsEntry(
                    $row['last_update_date'],
                    $this->getHTMLMessage($artifact, $project),
                    "fas fa-check",
                    $this->getUser((int) $row['last_updated_by_id'])
                )
            );
        }
    }

    private function getUser(int $last_updated_by_id): ?PFUser
    {
        return $this->user_manager->getUserById($last_updated_by_id);
    }

    private function getHTMLMessage(Artifact $artifact, Project $project): string
    {
        return $this->getTitle($artifact, $project);
    }

    private function getTitle(Artifact $campaign, Project $project): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $tlp_badge_color = $purifier->purify('tlp-swatch-' . $campaign->getTracker()->getColor()->getName());
        $campaign_url    = "/plugins/testmanagement/?group_id=" .
            urlencode((string) $project->getId()) . "#!/campaigns/" . $campaign->getId();
        $title           = '
            <a class="direct-link-to-artifact" href="' . $purifier->purify($campaign_url) . '">
                <span class="cross-ref-badge ' . $tlp_badge_color . '">
                ' . $purifier->purify($campaign->getXRef()) . '
                </span>' . $purifier->purify($campaign->getTitle()) . '</a>';

        return $title;
    }
}
