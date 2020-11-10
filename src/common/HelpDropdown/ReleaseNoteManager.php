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

class ReleaseNoteManager
{
    public const USER_PREFERENCE_NAME_RELEASE_NOTE_SEEN = 'has_release_note_been_seen';
    private const UTM_RELEASE_NOTE                       = '/?utm_source=tuleap&utm_medium=forge&utm_campaign=tuleap-forge-icon-help-RN-link';
    private const BASE_RELEASE_NOTE_URL                  = 'https://www.tuleap.org/resources/release-notes/tuleap-';

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

    public function getReleaseNoteLink(string $tuleap_version): string
    {
        $extracted_tuleap_version = $this->version_number_extractor->extractReleaseNoteTuleapVersion($tuleap_version);

        $actual_version_link = self::BASE_RELEASE_NOTE_URL . urlencode(
            $extracted_tuleap_version
        ) . self::UTM_RELEASE_NOTE;

        $release_note_link_row = $this->release_note_dao->getReleaseLink();

        if ($release_note_link_row === null) {
            $this->transaction_executor->execute(
                function () use ($actual_version_link, $extracted_tuleap_version) {
                    $this->release_note_dao->createReleaseNoteLink($extracted_tuleap_version);
                    $this->users_preferences_dao->deletePreferenceForAllUsers(self::USER_PREFERENCE_NAME_RELEASE_NOTE_SEEN);
                }
            );

            return $actual_version_link;
        }

        if ($release_note_link_row["tuleap_version"] !== $extracted_tuleap_version) {
            $this->transaction_executor->execute(
                function () use ($actual_version_link, $extracted_tuleap_version) {
                    $this->release_note_dao->updateReleaseNoteLink(null, $extracted_tuleap_version);
                    $this->users_preferences_dao->deletePreferenceForAllUsers(self::USER_PREFERENCE_NAME_RELEASE_NOTE_SEEN);
                }
            );

            return $actual_version_link;
        }

        if ($release_note_link_row["actual_link"] === "" || $release_note_link_row["actual_link"] === null) {
            return $actual_version_link;
        }

        return $release_note_link_row["actual_link"];
    }
}
