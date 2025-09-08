<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset;

use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker_Artifact_Changeset;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationActionsQueuer;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;

final readonly class NewChangesetPostProcessor implements ProcessChangesetPostCreation
{
    public function __construct(
        private EventDispatcherInterface $event_manager,
        private PostCreationActionsQueuer $post_creation_queuer,
        private ChangesetCommentIndexer $changeset_comment_indexer,
    ) {
    }

    #[\Override]
    public function postProcessCreation(
        NewChangesetCreated $changeset_created,
        Artifact $artifact,
        PostCreationContext $context,
        ?Tracker_Artifact_Changeset $old_changeset,
        PFUser $submitter,
    ): void {
        $new_changeset = $changeset_created->changeset;

        if ($changeset_created->should_launch_fts_update) {
            $this->changeset_comment_indexer->indexNewChangesetComment($changeset_created->comment_creation, $artifact);
        }

        if (! $context->getImportConfig()->isFromXml()) {
            $this->post_creation_queuer->queuePostCreation(
                $new_changeset,
                $context->shouldSendNotifications(),
            );
        }

        if (! $old_changeset) {
            $old_changeset = $new_changeset;
        }
        $this->event_manager->dispatch(new ArtifactUpdated($artifact, $submitter, $new_changeset, $old_changeset));
    }
}
