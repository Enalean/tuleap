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

namespace Tuleap\Tracker\Creation;

use Tracker_Reference;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\PromotedTrackerDao;

class PostCreationProcessor
{
    /**
     * @var \ReferenceManager
     */
    private $reference_manager;
    /**
     * @var PromotedTrackerDao
     */
    private $in_new_dropdown_dao;
    /**
     * @var TrackerPrivateCommentUGroupEnabledDao
     */
    private $private_comment_dao;

    public function __construct(
        \ReferenceManager $reference_manager,
        PromotedTrackerDao $in_new_dropdown_dao,
        TrackerPrivateCommentUGroupEnabledDao $private_comment_dao,
    ) {
        $this->reference_manager   = $reference_manager;
        $this->in_new_dropdown_dao = $in_new_dropdown_dao;
        $this->private_comment_dao = $private_comment_dao;
    }

    public static function build(): self
    {
        return new self(
            \ReferenceManager::instance(),
            new PromotedTrackerDao(),
            new TrackerPrivateCommentUGroupEnabledDao()
        );
    }

    public function postCreationProcess(\Tracker $tracker, TrackerCreationSettings $settings): void
    {
        $this->forceReferenceCreation($tracker);
        $this->addTrackerInNewDropDown($tracker, $settings);
        $this->addTrackerDoestNotUsePrivateComment($tracker, $settings);
    }

    private function forceReferenceCreation(\Tracker $tracker): void
    {
        $keyword   = strtolower($tracker->getItemName());
        $reference = new Tracker_Reference(
            $tracker,
            $keyword
        );

        // Force reference creation because default trackers use reserved keywords
        $this->reference_manager->createReference($reference, true);
    }

    private function addTrackerInNewDropDown(\Tracker $tracker, TrackerCreationSettings $settings): void
    {
        if ($settings->isDisplayedInNewDropdown() === true) {
            $this->in_new_dropdown_dao->insert($tracker->getId());
        }
    }

    private function addTrackerDoestNotUsePrivateComment(\Tracker $tracker, TrackerCreationSettings $settings): void
    {
        if ($settings->isPrivateCommentUsed() === false) {
            $this->private_comment_dao->disabledPrivateCommentOnTracker($tracker->getId());
        }
    }
}
