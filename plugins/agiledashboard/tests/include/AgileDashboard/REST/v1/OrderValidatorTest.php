<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1;

use TuleapTestCase;

require_once dirname(__FILE__).'/../../../../bootstrap.php';

class OrderValidatorTest extends TuleapTestCase {

    /** @var OrderValidator */
    private $order_validator;

    public function setUp() {
        parent::setUp();

        $this->order_validator = new OrderValidator(
            array(
                115 => true,
                116 => true,
                117 => true,
                118 => true,
            )
        );
    }

    public function itAllowsToRankWhenItemsArePartOfMilestonesLinkedArtifacts() {
        $order_representation = new OrderRepresentation();
        $order_representation->ids = array(115, 116);
        $order_representation->compared_to = 118;

        $this->order_validator->validate($order_representation);
    }

    public function itDoesntAllowToRankItemsThatAreNotPartMilestonesLinkedArtifacts() {
        $order_representation = new OrderRepresentation();
        $order_representation->ids = array(115, 235);
        $order_representation->compared_to = 118;

        $this->expectException('Tuleap\AgileDashboard\REST\v1\OrderIdOutOfBoundException');
        $this->order_validator->validate($order_representation);
    }

    public function itDoesntAllowToRankItemsWithThatAreNotPartMilestonesLinkedArtifacts() {
        $order_representation = new OrderRepresentation();
        $order_representation->ids = array(115, 116);
        $order_representation->compared_to = 235;

        $this->expectException('Tuleap\AgileDashboard\REST\v1\OrderIdOutOfBoundException');
        $this->order_validator->validate($order_representation);
    }

    public function itDoesntAllowDuplicatedIds() {
        $order_representation = new OrderRepresentation();
        $order_representation->ids = array(115, 116, 115, 117);
        $order_representation->compared_to = 118;

        $this->expectException('Tuleap\AgileDashboard\REST\v1\IdsFromBodyAreNotUniqueException');
        $this->order_validator->validate($order_representation);
    }
}
