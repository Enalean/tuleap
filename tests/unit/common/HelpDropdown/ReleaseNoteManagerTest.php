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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use UserPreferencesDao;

class ReleaseNoteManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const BASE_URL = "https://www.tuleap.org/resources/release-notes/tuleap-11-17/?utm_source=tuleap&utm_medium=forge&utm_campaign=tuleap-forge-icon-help-RN-link";

    /**
     * @var ReleaseNoteManager
     */
    private $release_note_manager;
    /**
     * @var ReleaseLinkDao&MockObject
     */
    private $release_note_dao;
    /**
     * @var UserPreferencesDao&MockObject
     */
    private $user_preferences_dao;

    protected function setUp(): void
    {
        $this->release_note_dao     = $this->createMock(ReleaseLinkDao::class);
        $this->user_preferences_dao = $this->createMock(UserPreferencesDao::class);
        $this->release_note_manager = new ReleaseNoteManager(
            $this->release_note_dao,
            $this->user_preferences_dao,
            new VersionNumberExtractor(),
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testGetReleaseNoteLink(): void
    {
        $dao_links = [
            "actual_link" => "",
            "tuleap_version" => "11-17",
        ];

        $this->release_note_dao->method("getReleaseLink")->willReturn($dao_links);
        $this->user_preferences_dao->expects(self::never())->method("deletePreferenceForAllUsers");

        self::assertEquals(self::BASE_URL, $this->release_note_manager->getReleaseNoteLink("11.17.99.666"));
    }

    public function testGetReleaseNoteLinkWithCustomLink(): void
    {
        $expected_result = "https://whatever.com";

        $dao_links = [
            "actual_link" => "https://whatever.com",
            "tuleap_version" => "11-17",
        ];

        $this->release_note_dao->method("getReleaseLink")->willReturn($dao_links);
        $this->user_preferences_dao->expects(self::never())->method("deletePreferenceForAllUsers");

        self::assertEquals($expected_result, $this->release_note_manager->getReleaseNoteLink("11.17.99.666"));
    }

    public function testGetReleaseNoteLinkWithNullLink(): void
    {
        $dao_links = [
            "actual_link" => null,
            "tuleap_version" => "11-17",
        ];

        $this->release_note_dao->method("getReleaseLink")->willReturn($dao_links);
        $this->user_preferences_dao->expects(self::never())->method("deletePreferenceForAllUsers");

        self::assertEquals(self::BASE_URL, $this->release_note_manager->getReleaseNoteLink("11.17.99.666"));
    }

    public function testGetReleaseNoteLinkShouldChangeIfVersionIsUpgraded(): void
    {
        $dao_old_links = [
            "actual_link" => "https://whatever.com",
            "tuleap_version" => "11-16",
        ];

        $this->release_note_dao->expects(self::once())->method("getReleaseLink")->willReturn($dao_old_links);
        $this->release_note_dao->method("updateReleaseNoteLink");
        $this->user_preferences_dao->expects(self::once())->method("deletePreferenceForAllUsers");

        self::assertEquals(self::BASE_URL, $this->release_note_manager->getReleaseNoteLink("11.17.99.666"));
    }

    public function testGetReleaseNoteLinkIfNotLinkInDatabase(): void
    {
        $this->release_note_dao->expects(self::once())->method("getReleaseLink")->willReturn(null);
        $this->release_note_dao->method("createReleaseNoteLink");
        $this->user_preferences_dao->expects(self::once())->method("deletePreferenceForAllUsers");

        self::assertEquals(self::BASE_URL, $this->release_note_manager->getReleaseNoteLink("11.17.99.666"));
    }
}
