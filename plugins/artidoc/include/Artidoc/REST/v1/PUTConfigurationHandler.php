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

namespace Tuleap\Artidoc\REST\v1;

use Tuleap\Artidoc\Document\ArtidocDocumentInformation;
use Tuleap\Artidoc\Document\RetrieveArtidoc;
use Tuleap\Artidoc\Document\SaveConfiguredTracker;
use Tuleap\Artidoc\Document\Tracker\CheckTrackerIsSuitableForDocument;
use Tuleap\Artidoc\Document\Tracker\TrackerNotFoundFault;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\RetrieveTracker;

final readonly class PUTConfigurationHandler
{
    public function __construct(
        private RetrieveArtidoc $retrieve_artidoc,
        private SaveConfiguredTracker $save_configured_tracker,
        private RetrieveTracker $retrieve_tracker,
        private CheckTrackerIsSuitableForDocument $suitable_tracker_for_document_checker,
    ) {
    }

    /**
     * @return Ok<true>|Err<Fault>
     */
    public function handle(int $id, ArtidocPUTConfigurationRepresentation $configuration, \PFUser $user): Ok|Err
    {
        return $this->retrieve_artidoc
            ->retrieveArtidoc($id, $user)
            ->andThen(fn (ArtidocDocumentInformation $document_information) => $this->ensureThatUserCanWriteDocument($document_information, $user))
            ->andThen(fn (ArtidocDocumentInformation $document_information) => $this->saveConfiguration($document_information, $configuration, $user));
    }

    /**
     * @return Ok<ArtidocDocumentInformation>|Err<Fault>
     */
    private function ensureThatUserCanWriteDocument(ArtidocDocumentInformation $document_information, \PFUser $user): Ok|Err
    {
        $permissions_manager = \Docman_PermissionsManager::instance((int) $document_information->document->getGroupId());
        if (! $permissions_manager->userCanWrite($user, (int) $document_information->document->getId())) {
            return Result::err(UserCannotWriteDocumentFault::build());
        }

        return Result::ok($document_information);
    }

    /**
     * @return Ok<true>|Err<Fault>
     */
    private function saveConfiguration(
        ArtidocDocumentInformation $document_information,
        ArtidocPUTConfigurationRepresentation $configuration,
        \PFUser $user,
    ): Ok|Err {
        $tracker = $this->retrieve_tracker->getTrackerById($configuration->selected_tracker_ids[0]);
        if (! $tracker) {
            return Result::err(TrackerNotFoundFault::forDocument($document_information->document));
        }

        return $this->suitable_tracker_for_document_checker
            ->checkTrackerIsSuitableForDocument($tracker, $document_information->document, $user)
            ->andThen(function ($tracker) use ($document_information) {
                $this->save_configured_tracker->saveTracker(
                    (int) $document_information->document->getId(),
                    $tracker->getId()
                );

                return Result::ok(true);
            });
    }
}
