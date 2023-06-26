<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

final class OrderValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var OrderValidator */
    private $order_validator;

    protected function setUp(): void
    {
        $this->order_validator = new OrderValidator(
            [
                115 => true,
                116 => true,
                117 => true,
                118 => true,
            ]
        );
    }

    public function testItDoesNotThrowWhenIdsAndComparedToIdAreInTheValidatorsIndex(): void
    {
        $order_representation = OrderRepresentation::build([115, 116], 'whatever', 118);

        $this->order_validator->validate($order_representation);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateThrowsWhenIdsAreNotPartOfTheValidatorsIndex(): void
    {
        $order_representation = OrderRepresentation::build([115, 235], 'whatever', 118);

        $this->expectException(OrderIdOutOfBoundException::class);
        $this->order_validator->validate($order_representation);
    }

    public function testValidateThrowsWhenComparedToIdIsNotPartOfTheValidatorsIndex(): void
    {
        $order_representation = OrderRepresentation::build([115, 116], 'whatever', 235);

        $this->expectException(OrderIdOutOfBoundException::class);
        $this->order_validator->validate($order_representation);
    }

    public function testValidateThrowsWhenIdsAreDuplicated(): void
    {
        $order_representation = OrderRepresentation::build([115, 116, 115, 117], 'whatever', 118);

        $this->expectException(IdsFromBodyAreNotUniqueException::class);
        $this->order_validator->validate($order_representation);
    }
}
