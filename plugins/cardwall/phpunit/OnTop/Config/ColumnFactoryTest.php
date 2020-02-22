<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Cardwall_OnTop_Config_ColumnFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|Tracker_FormElement_Field_Selectbox
     */
    private $status_field;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var Cardwall_OnTop_ColumnDao|M\LegacyMockInterface|M\MockInterface
     */
    private $dao;
    /**
     * @var Cardwall_OnTop_Config_ColumnFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $new = M::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $new->shouldReceive('getId')
            ->andReturn(10);
        $new->shouldReceive('getLabel')
            ->andReturn('New');
        $verified = M::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $verified->shouldReceive('getId')
            ->andReturn(11);
        $verified->shouldReceive('getLabel')
            ->andReturn('Verified');
        $fixed = M::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $fixed->shouldReceive('getId')
            ->andReturn(12);
        $fixed->shouldReceive('getLabel')
            ->andReturn('Fixed');

        $this->status_field = M::mock(Tracker_FormElement_Field_Selectbox::class);
        $this->status_field->shouldReceive('getVisibleValuesPlusNoneIfAny')
            ->andReturn([$new, $verified, $fixed]);

        $this->tracker = M::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(42);
        $this->dao        = M::mock(Cardwall_OnTop_ColumnDao::class);
        $this->factory    = new Cardwall_OnTop_Config_ColumnFactory($this->dao);
    }

    public function testItBuildsColumnsFromTheDataStorage(): void
    {
        $this->dao->shouldReceive('searchColumnsByTrackerId')
            ->with(42)
            ->once()
            ->andReturn(
                [
                    [
                        'id'             => 1,
                        'label'          => 'Todo',
                        'bg_red'         => '123',
                        'bg_green'       => '12',
                        'bg_blue'        => '10',
                        'tlp_color_name' => null
                    ],
                    [
                        'id'             => 2,
                        'label'          => 'On Going',
                        'bg_red'         => null,
                        'bg_green'       => null,
                        'bg_blue'        => null,
                        'tlp_color_name' => null
                    ],
                    [
                        'id'             => 2,
                        'label'          => 'Review',
                        'bg_red'         => null,
                        'bg_green'       => null,
                        'bg_blue'        => null,
                        'tlp_color_name' => 'peggy-pink'
                    ]
                ]
            );
        $columns = $this->factory->getDashboardColumns($this->tracker);

        $this->assertInstanceOf(Cardwall_OnTop_Config_ColumnFreestyleCollection::class, $columns);
        $this->assertSame(3, count($columns));
        $this->assertSame('On Going', $columns[1]->getLabel());
        $this->assertSame('rgb(123, 12, 10)', $columns[0]->getHeadercolor());
        $this->assertSame('rgb(248,248,248)', $columns[1]->getHeadercolor());

        $this->assertSame('Review', $columns[2]->getLabel());
        $this->assertSame('peggy-pink', $columns[2]->getHeadercolor());
    }

    public function testItBuildsAnEmptyFreestyleCollection(): void
    {
        $this->dao->shouldReceive('searchColumnsByTrackerId')
            ->with(42)
            ->andReturn([]);
        $columns = $this->factory->getDashboardColumns($this->tracker);

        $this->assertInstanceOf(Cardwall_OnTop_Config_ColumnFreestyleCollection::class, $columns);
        $this->assertSame(0, count($columns));
    }

    public function testGetColumnByIdReturnsNullWhenColumnCantBeFound(): void
    {
        $this->dao->shouldReceive('searchByColumnId')
            ->with(79)
            ->once()
            ->andReturnFalse();
        $this->assertNull($this->factory->getColumnById(79));
    }

    public function testGetColumnByIdBuildsASingleColumn(): void
    {
        $column_row = [
            'id' => 79,
            'label' => 'Review',
            'bg_red' => null,
            'bg_green' => null,
            'bg_blue' => null,
            'tlp_color_name' => 'acid-green'
        ];
        $dar        = M::mock(DataAccessResult::class)
            ->shouldReceive(['getRow' => $column_row])
            ->getMock();
        $this->dao->shouldReceive('searchByColumnId')
            ->with(79)
            ->once()
            ->andReturn($dar);
        $column = $this->factory->getColumnById(79);
        $this->assertSame('Review', $column->getLabel());
        $this->assertSame('acid-green', $column->getHeadercolor());
    }
}
