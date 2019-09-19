<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

class TourFactoryTest_getTour extends TuleapTestCase
{

    protected $factory;

    protected $fixtures_dir;

    /** @var ProjectManager */
    protected $project_manager;

    /** @var PFUser */
    protected $user;

    public function setUp()
    {
        parent::setUp();

        $this->fixtures_dir    = dirname(__FILE__) .'/_fixtures';
        $this->project_manager = mock('ProjectManager');
        $this->factory         = new Tuleap_TourFactory($this->project_manager, mock('Url'));
        $this->user            = mock('PFUser');

        ForgeConfig::set('sys_custom_incdir', $this->fixtures_dir);
    }

    public function itReturnsTheWelcomeTour()
    {
        $tour = $this->factory->getTour($this->user, Tuleap_Tour_WelcomeTour::TOUR_NAME);
        $this->assertIsA($tour, 'Tuleap_Tour_WelcomeTour');
    }

    public function itReturnsACustomTour()
    {
        stub($this->user)->getLocale()->returns('en_US');

        $tour = $this->factory->getTour($this->user, 'lala_tour');
        $this->assertIsA($tour, 'Tuleap_Tour');
    }

    public function itThrowsExceptionIfTourIsNotFound()
    {
        stub($this->user)->getLocale()->returns('fr_US');

        $this->expectException('Tuleap_UnknownTourException');

        $this->factory->getTour($this->user, 'woofwoof_tour');
    }
}
