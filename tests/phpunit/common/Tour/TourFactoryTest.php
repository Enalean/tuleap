<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Tour;

use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use ProjectManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap_Tour_WelcomeTour;
use Tuleap_TourFactory;

final class TourFactoryTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    /**
     * @var Tuleap_TourFactory
     */
    private $factory;

    /**
     * @var string
     */
    private $fixtures_dir;

    /** @var ProjectManager */
    private $project_manager;

    /** @var PFUser */
    private $user;

    protected function setUp(): void
    {
        $this->fixtures_dir    = __DIR__ . '/_fixtures';
        $this->project_manager = \Mockery::spy(\ProjectManager::class);
        $this->factory         = new Tuleap_TourFactory($this->project_manager, \Mockery::spy(\URL::class));
        $this->user            = \Mockery::spy(\PFUser::class);

        ForgeConfig::set('sys_custom_incdir', $this->fixtures_dir);
    }

    public function testItReturnsTheWelcomeTour(): void
    {
        $tour = $this->factory->getTour($this->user, Tuleap_Tour_WelcomeTour::TOUR_NAME);
        $this->assertInstanceOf(\Tuleap_Tour_WelcomeTour::class, $tour);
    }

    public function testItReturnsACustomTour(): void
    {
        $this->user->shouldReceive('getLocale')->andReturns('en_US');

        $tour = $this->factory->getTour($this->user, 'lala_tour');
        $this->assertInstanceOf(\Tuleap_Tour::class, $tour);
    }

    public function testItThrowsExceptionIfTourIsNotFound(): void
    {
        $this->user->shouldReceive('getLocale')->andReturns('fr_US');

        $this->expectException(\Tuleap_UnknownTourException::class);

        $this->factory->getTour($this->user, 'woofwoof_tour');
    }
}
