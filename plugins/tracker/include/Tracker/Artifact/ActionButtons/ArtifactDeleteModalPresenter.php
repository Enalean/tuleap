<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ActionButtons;

use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Tracker\Artifact\Artifact;

final readonly class ArtifactDeleteModalPresenter
{
    public int $tracker_id;
    public int $artifact_id;
    public string $token_name;
    public string $token;

    public function __construct(
        Artifact $artifact,
        CSRFSynchronizerTokenPresenter $csrf_token,
        public int $artifacts_deletion_limit,
        public int $artifacts_deletion_count,
    ) {
        $this->tracker_id  = $artifact->getTrackerId();
        $this->artifact_id = $artifact->getId();
        $this->token_name  = $csrf_token->getTokenName();
        $this->token       = $csrf_token->getToken();
    }
}
