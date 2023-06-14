<?php
/**
 * Copyright (c) Enalean, 2017-present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\EqualExpression;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ParagonIE\EasyDB\EasyDB;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\EqualComparison\ForText;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereComparisonFieldBuilder;

final class ForTextTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItUsesTheComparisonInternalIdAsASuffixInOrderToBeAbleToHaveTheFieldSeveralTimesInTheQuery(): void
    {
        $comparison = new EqualComparison(new Field('field'), new SimpleValueWrapper('value'));
        $field_id   = 101;
        $field      = \Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $field->shouldReceive('getId')->andReturn($field_id);

        $db = $this->createMock(EasyDB::class);
        $db->method('escapeLikeValue')->willReturnArgument(0);

        $for_text   = new ForText(
            new FromWhereComparisonFieldBuilder(),
            $db,
        );
        $from_where = $for_text->getFromWhere($comparison, $field);

        $suffix = spl_object_hash($comparison);

        $this->assertMatchesRegularExpression("/tracker_changeset_value_text AS CVText_{$field_id}_{$suffix}/", $from_where->getFrom());
    }
}
