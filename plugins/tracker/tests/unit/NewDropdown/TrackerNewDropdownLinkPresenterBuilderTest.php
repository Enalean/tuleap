<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\NewDropdown;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Layout\NewDropdown\DataAttributePresenter;

class TrackerNewDropdownLinkPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBuild(): void
    {
        $tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive(
                [
                    'getId'        => 102,
                    'getSubmitUrl' => '/path/to/102',
                    'getItemName'  => 'bug',
                ]
            )
            ->getMock();

        $builder   = new TrackerNewDropdownLinkPresenterBuilder();
        $presenter = $builder->build($tracker);

        self::assertEquals('New bug', $presenter->label);
        self::assertEquals('/path/to/102', $presenter->url);
        self::assertEquals('fa-plus', $presenter->icon);
        self::assertCount(1, $presenter->data_attributes);
        self::assertEquals('tracker-id', $presenter->data_attributes[0]->name);
        self::assertEquals('102', $presenter->data_attributes[0]->value);
    }

    public function testBuildWithAdditionalDataAttributes(): void
    {
        $tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive(
                [
                    'getId'        => 102,
                    'getSubmitUrl' => '/path/to/102',
                    'getItemName'  => 'bug',
                ]
            )
            ->getMock();

        $builder   = new TrackerNewDropdownLinkPresenterBuilder();
        $presenter = $builder->buildWithAdditionalDataAttributes(
            $tracker,
            [new DataAttributePresenter('additional-name', 'additional-value')]
        );

        self::assertEquals('New bug', $presenter->label);
        self::assertEquals('/path/to/102', $presenter->url);
        self::assertEquals('fa-plus', $presenter->icon);
        self::assertCount(2, $presenter->data_attributes);
        self::assertEquals('tracker-id', $presenter->data_attributes[0]->name);
        self::assertEquals('102', $presenter->data_attributes[0]->value);
        self::assertEquals('additional-name', $presenter->data_attributes[1]->name);
        self::assertEquals('additional-value', $presenter->data_attributes[1]->value);
    }

    public function testBuildWithAdditionalUrlParameters(): void
    {
        $tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive(
                [
                    'getId'       => 102,
                    'getItemName' => 'bug',
                ]
            )
            ->getMock();
        $tracker->shouldReceive('getSubmitUrlWithParameters')
            ->with(['link-to-milestone' => '1'])
            ->andReturn('/path/to/102?link-to-milestone=1');

        $builder   = new TrackerNewDropdownLinkPresenterBuilder();
        $presenter = $builder->buildWithAdditionalUrlParameters($tracker, ['link-to-milestone' => 1]);

        self::assertEquals('New bug', $presenter->label);
        self::assertEquals('/path/to/102?link-to-milestone=1', $presenter->url);
        self::assertEquals('fa-plus', $presenter->icon);
        self::assertCount(1, $presenter->data_attributes);
        self::assertEquals('tracker-id', $presenter->data_attributes[0]->name);
        self::assertEquals('102', $presenter->data_attributes[0]->value);
    }
}
