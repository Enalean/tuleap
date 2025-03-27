<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\Burndown;

use ColinODell\PsrTestLogger\TestLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use function PHPUnit\Framework\assertTrue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BurndownCommonDataBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private \Tuleap\Tracker\Artifact\Artifact $artifact;
    private \PFUser $user;
    private BurndownCommonDataBuilder $common_data_builder;
    private BurndownCacheGenerationChecker&MockObject $cache_checker;
    private TestLogger $logger;

    protected function setUp(): void
    {
        \ForgeConfig::set(ConfigurationVariables::SERVER_TIMEZONE, 'Europe/Paris');

        $this->artifact      = ArtifactTestBuilder::anArtifact(1)->build();
        $this->user          = UserTestBuilder::aUser()->build();
        $this->logger        = new TestLogger();
        $value_retriever     = $this->createMock(ChartConfigurationValueRetriever::class);
        $field_retriever     = $this->createMock(ChartConfigurationFieldRetriever::class);
        $this->cache_checker = $this->createMock(BurndownCacheGenerationChecker::class);

        $this->common_data_builder = new BurndownCommonDataBuilder(
            $this->logger,
            $field_retriever,
            $value_retriever,
            $this->cache_checker,
        );
    }

    protected function tearDown(): void
    {
        \ForgeConfig::restore();
    }

    public function testBurndownIsNotUnderCalculationWhenStartDateIsInFuture(): void
    {
        $date_period               = DatePeriodWithOpenDays::buildFromDuration(strtotime('tomorrow'), 10);
        $should_calculate_burndown = $this->common_data_builder->getBurndownCalculationStatus($this->artifact, $this->user, $date_period, 0, 'EN_en');

        self::assertTrue($this->logger->hasDebug('Cache is always valid when start date is in future'));
        self::assertFalse($should_calculate_burndown);
    }

    public function testBurndownIsNotUnderCalculationWhenDurationIsNotSet(): void
    {
        $date_period               = DatePeriodWithOpenDays::buildFromDuration(strtotime('2024-11-01'), 0);
        $should_calculate_burndown = $this->common_data_builder->getBurndownCalculationStatus($this->artifact, $this->user, $date_period, 0, 'EN_en');

        self::assertTrue($this->logger->hasDebug('Cache is always valid when burndown has no duration'));
        self::assertFalse($should_calculate_burndown);
    }

    public function testItReturnsIfBurndownIsUnderCalculation(): void
    {
        $date_period = DatePeriodWithOpenDays::buildFromDuration(strtotime('2024-11-01'), 5);

        $this->cache_checker->expects($this->once())->method('isBurndownUnderCalculationBasedOnServerTimezone')->willReturn(true);
        $should_calculate_burndown = $this->common_data_builder->getBurndownCalculationStatus($this->artifact, $this->user, $date_period, 0, 'EN_en');

        self:assertTrue($should_calculate_burndown);
    }
}
