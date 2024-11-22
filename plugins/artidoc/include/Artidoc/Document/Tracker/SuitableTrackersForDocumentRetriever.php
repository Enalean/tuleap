<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Tracker;

use Tracker;
use Tuleap\Artidoc\Document\ArtidocDocumentInformation;
use Tuleap\Tracker\RetrieveTrackersByProjectIdUserCanView;

final readonly class SuitableTrackersForDocumentRetriever
{
    public function __construct(
        private CheckTrackerIsSuitableForDocument $suitable_tracker_for_document_checker,
        private RetrieveTrackersByProjectIdUserCanView $tracker_factory,
    ) {
    }

    /**
     * @return list<Tracker>
     */
    public function getTrackers(ArtidocDocumentInformation $document_information, \PFUser $user): array
    {
        return array_reduce(
            $this->tracker_factory->getTrackersByProjectIdUserCanView(
                $document_information->document_service->getProjectIdentifier(),
                $user,
            ),
            /**
             * @param list<Tracker> $suitable_trackers
             */
            function (array $suitable_trackers, Tracker $tracker) use ($document_information, $user) {
                $this->suitable_tracker_for_document_checker
                    ->checkTrackerIsSuitableForDocument($tracker, $document_information->document, $user)
                    ->match(
                        function (Tracker $tracker) use (&$suitable_trackers) {
                            $suitable_trackers[] = $tracker;
                        },
                        fn () => 'skip invalid trackers',
                    );

                return $suitable_trackers;
            },
            [],
        );
    }
}
