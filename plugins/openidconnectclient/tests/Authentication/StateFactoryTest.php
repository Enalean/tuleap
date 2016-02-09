<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\OpenIDConnectClient\Authentication\StateFactory;

require_once(__DIR__ . '/../bootstrap.php');

class StateFactoryTest extends TuleapTestCase {

    public function itKeepsSameKeyBetweenGeneration() {
        $key                     = 'Tuleap';
        $random_number_generator = mock('RandomNumberGenerator');
        $random_number_generator->setReturnValue('getNumber', $key);

        $state_factory = new StateFactory($random_number_generator);
        $state_1       = $state_factory->createState(1);
        $state_2       = $state_factory->createState(2);

        $this->assertEqual($key, $state_1->getSecretKey());
        $this->assertEqual($key, $state_2->getSecretKey());
    }

    public function itCreatesStateWithGivenParameters() {
        $value = 1234;

        $random_number_generator = new RandomNumberGenerator();
        $state_factory           = new StateFactory($random_number_generator);
        $state                   = $state_factory->createState($value);

        $this->assertEqual($value, $state->getProviderId());
    }
}