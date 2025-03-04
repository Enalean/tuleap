<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance\Migration;

use Psr\Log\NullLogger;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\Project\Service\ServiceDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ServiceMediawikiSwitcherTest extends TestCase
{
    private const PROJECT_ID              = 101;
    private const LEGACY_SERVICE_ID       = 111;
    private const STANDALONE_SERVICE_ID   = 222;
    private const LEGACY_SERVICE_RANK     = 120;
    private const STANDALONE_SERVICE_RANK = 161;

    public function testSwitchToStandalone(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $dao     = $this->createMock(ServiceDao::class);
        $dao->method('searchByProjectAndShortNames')
            ->willReturnCallback(
                fn(\Project $project, array $allowed_shortnames) => match ($allowed_shortnames[0]) {
                    \MediaWikiPlugin::SERVICE_SHORTNAME => [
                        [
                            'service_id' => self::LEGACY_SERVICE_ID,
                            'rank' => self::LEGACY_SERVICE_RANK,
                        ],
                    ],
                    MediawikiStandaloneService::SERVICE_SHORTNAME => [
                        [
                            'service_id' => self::STANDALONE_SERVICE_ID,
                            'label' => 'label',
                            'icon' => '',
                            'description' => '',
                            'link' => null,
                            'rank' => self::STANDALONE_SERVICE_RANK,
                            'is_in_iframe' => 0,
                            'is_in_new_tab' => 0,
                        ],
                    ],
                },
            );


        $dao->expects(self::exactly(1))
            ->method('updateServiceUsageByShortName')
            ->with($project, \MediaWikiPlugin::SERVICE_SHORTNAME, 0);
        $dao->expects(self::once())
            ->method('saveBasicInformation')
            ->with(self::STANDALONE_SERVICE_ID, 'label', '', '', null, self::LEGACY_SERVICE_RANK, 0, 0);
        $dao->expects(self::once())
            ->method('updateServiceUsageByServiceID')
            ->with($project, self::STANDALONE_SERVICE_ID, 1);

        $switcher = new ServiceMediawikiSwitcher($dao, new NullLogger());

        $switcher->switchToStandalone($project);
    }

    public function testItDoesNotChangeTheRankWhenLegacyDoesNotExist(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $dao     = $this->createMock(ServiceDao::class);
        $dao->method('searchByProjectAndShortNames')
            ->willReturnCallback(
                fn(\Project $project, array $allowed_shortnames) => match ($allowed_shortnames[0]) {
                    \MediaWikiPlugin::SERVICE_SHORTNAME => [],
                    MediawikiStandaloneService::SERVICE_SHORTNAME => [
                        [
                            'service_id' => self::STANDALONE_SERVICE_ID,
                            'label' => 'label',
                            'icon' => '',
                            'description' => '',
                            'link' => null,
                            'rank' => self::STANDALONE_SERVICE_RANK,
                            'is_in_iframe' => 0,
                            'is_in_new_tab' => 0,
                        ],
                    ],
                },
            );


        $dao->expects(self::once())
            ->method('updateServiceUsageByServiceID')
            ->with($project, self::STANDALONE_SERVICE_ID, 1);

        $switcher = new ServiceMediawikiSwitcher($dao, new NullLogger());

        $switcher->switchToStandalone($project);
    }

    public function testItCreatesFromScratchTheStandaloneServiceWhenItDoesNotExistYet(): void
    {
        $dao = $this->createMock(ServiceDao::class);
        $dao->method('searchByProjectAndShortNames')
            ->willReturnCallback(
                fn(\Project $project, array $allowed_shortnames) => match ($allowed_shortnames[0]) {
                    \MediaWikiPlugin::SERVICE_SHORTNAME =>[],
                    MediawikiStandaloneService::SERVICE_SHORTNAME => [],
                },
            );

        $dao->expects(self::once())
            ->method('create')
            ->with(
                self::PROJECT_ID,
                'label',
                MediawikiStandaloneService::ICON_NAME,
                '',
                MediawikiStandaloneService::SERVICE_SHORTNAME,
                null,
                1,
                1,
                'system',
                self::STANDALONE_SERVICE_RANK,
                false
            );

        $switcher = new ServiceMediawikiSwitcher($dao, new NullLogger());

        $switcher->switchToStandalone(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build());
    }

    public function testItCreatesFromScratchTheStandaloneServiceWhenItDoesNotExistYetAndTakeTheRankOfTheLegacyOne(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $dao     = $this->createMock(ServiceDao::class);
        $dao->method('searchByProjectAndShortNames')
            ->willReturnCallback(
                fn(\Project $project, array $allowed_shortnames) => match ($allowed_shortnames[0]) {
                    \MediaWikiPlugin::SERVICE_SHORTNAME => [[
                        'service_id' => self::LEGACY_SERVICE_ID,
                        'rank' => self::LEGACY_SERVICE_RANK,
                    ],
                    ],
                    MediawikiStandaloneService::SERVICE_SHORTNAME => [],
                },
            );

        $dao->expects(self::exactly(1))
            ->method('updateServiceUsageByShortName')
            ->with($project, \MediaWikiPlugin::SERVICE_SHORTNAME, 0);
        $dao->expects(self::once())
            ->method('create')
            ->with(
                self::PROJECT_ID,
                'label',
                MediawikiStandaloneService::ICON_NAME,
                '',
                MediawikiStandaloneService::SERVICE_SHORTNAME,
                null,
                1,
                1,
                'system',
                self::LEGACY_SERVICE_RANK,
                false
            );

        $switcher = new ServiceMediawikiSwitcher($dao, new NullLogger());


        $switcher->switchToStandalone($project);
    }
}
