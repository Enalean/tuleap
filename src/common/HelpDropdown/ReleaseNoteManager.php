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

class ReleaseNoteManager
{
    /**
     * @var ReleaseLinkDao
     */
    private $release_note_dao;
    /**
     * @var string
     */
    private $tuleap_version;
    /**
     * @var \UserPreferencesDao
     */
    private $users_preferences_dao;

    public function __construct(ReleaseLinkDao $release_note_dao, \UserPreferencesDao $users_preferences_dao, string $tuleap_version)
    {
        $this->release_note_dao = $release_note_dao;
        $this->users_preferences_dao = $users_preferences_dao;
        $this->tuleap_version   = str_replace(".", "-", substr($tuleap_version, 0, 5));
    }

    public function getReleaseNoteLink(): string
    {
        $link = $this->release_note_dao->getReleaseLink();

        $actual_version_link = 'https://www.tuleap.org/resources/release-notes/tuleap-' . urlencode(
            $this->tuleap_version
        );


        if ($link === null) {
            $this->release_note_dao->createReleaseNoteLink($this->tuleap_version);
            $this->users_preferences_dao->deletePreferenceForAllUsers('has_release_note_been_seen');
            return $actual_version_link;
        }

        if ($link["tuleap_version"] !== $this->tuleap_version) {
            $this->release_note_dao->updateTuleapVersion($this->tuleap_version);
            $this->users_preferences_dao->deletePreferenceForAllUsers('has_release_note_been_seen');
            return $actual_version_link;
        }

        if ($link["actual_link"] === "" || $link["actual_link"] === null) {
            return $actual_version_link;
        }

        return $link["actual_link"];
    }
}
