<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use Codendi_Request;
use PFUser;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\View\TrackerArtifactView;

final readonly class ArtifactView extends TrackerArtifactView
{
    public function __construct(private int $release_id, Artifact $artifact, Codendi_Request $request, PFUser $user)
    {
        parent::__construct($artifact, $request, $user);
    }

    /** @see TrackerArtifactView::getTitle() */
    #[\Override]
    public function getTitle(): string
    {
        return dgettext('tuleap-frs', 'File release')
        . ' <i class="fas fa-external-link-alt"></i>';
    }

    /** @see TrackerArtifactView::getIdentifier() */
    #[\Override]
    public function getIdentifier(): string
    {
        return 'frs';
    }

    /** @see TrackerArtifactView::fetch() */
    #[\Override]
    public function fetch(): string
    {
        // Nothing to fetch as the tab is a redirect to the frs
        return '';
    }

    #[\Override]
    public function getURL(): string
    {
        $release_id = urlencode((string) $this->release_id);
        return "/frs/release/$release_id/release-notes";
    }
}
