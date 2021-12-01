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

namespace Tuleap\Reference\ByNature\Forum;

use ProjectManager;
use Tuleap\Forum\ForumRetriever;
use Tuleap\Forum\MessageNotFoundException;
use Tuleap\Forum\MessageRetriever;
use Tuleap\Forum\PermissionToAccessForumException;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Reference\CrossReferenceSectionPresenter;

class CrossReferenceForumOrganizer
{
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var MessageRetriever
     */
    private $message_retriever;
    /**
     * @var ForumRetriever
     */
    private $forum_retriever;

    public function __construct(
        ProjectManager $project_manager,
        MessageRetriever $message_retriever,
        ForumRetriever $forum_retriever,
    ) {
        $this->project_manager   = $project_manager;
        $this->message_retriever = $message_retriever;
        $this->forum_retriever   = $forum_retriever;
    }

    public function organizeMessageReference(
        CrossReferencePresenter $cross_reference_presenter,
        CrossReferenceByNatureOrganizer $by_nature_organizer,
    ): void {
        try {
            $message = $this->message_retriever->getMessage((int) $cross_reference_presenter->target_value);
        } catch (PermissionToAccessForumException | MessageNotFoundException $e) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);

            return;
        }

        $by_nature_organizer->moveCrossReferenceToSection(
            $cross_reference_presenter->withTitle($message->getSubject(), null),
            CrossReferenceSectionPresenter::UNLABELLED,
        );
    }

    public function organizeForumReference(
        CrossReferencePresenter $cross_reference_presenter,
        CrossReferenceByNatureOrganizer $by_nature_organizer,
    ): void {
        $project = $this->project_manager->getProject($cross_reference_presenter->target_gid);

        $forum = $this->forum_retriever->getForumUserCanView(
            (int) $cross_reference_presenter->target_value,
            $project,
            $by_nature_organizer->getCurrentUser()
        );
        if (! $forum) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);

            return;
        }

        $by_nature_organizer->moveCrossReferenceToSection(
            $cross_reference_presenter->withTitle($forum->getName(), null),
            CrossReferenceSectionPresenter::UNLABELLED,
        );
    }
}
