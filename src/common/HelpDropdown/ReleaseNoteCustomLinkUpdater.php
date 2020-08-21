<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\HelpDropdown;

use Tuleap\DB\DBTransactionExecutor;
use UserPreferencesDao;

class ReleaseNoteCustomLinkUpdater
{
    /**
     * @var ReleaseLinkDao
     */
    private $release_note_dao;
    /**
     * @var UserPreferencesDao
     */
    private $users_preferences_dao;

    /**
     * @var VersionNumberExtractor
     */
    private $version_number_extractor;

    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        ReleaseLinkDao $release_note_dao,
        UserPreferencesDao $users_preferences_dao,
        VersionNumberExtractor $version_number_extractor,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->release_note_dao         = $release_note_dao;
        $this->users_preferences_dao    = $users_preferences_dao;
        $this->version_number_extractor = $version_number_extractor;
        $this->transaction_executor     = $transaction_executor;
    }

    public function updateReleaseNoteLink(string $new_link, string $tuleap_version): void
    {
        $extracted_tuleap_version = $this->version_number_extractor->extractReleaseNoteTuleapVersion($tuleap_version);

        $this->transaction_executor->execute(
            function () use ($new_link, $extracted_tuleap_version) {
                $this->release_note_dao->updateReleaseNoteLink($new_link, $extracted_tuleap_version);
                $this->users_preferences_dao->deletePreferenceForAllUsers('has_release_note_been_seen');
            }
        );
    }
}
