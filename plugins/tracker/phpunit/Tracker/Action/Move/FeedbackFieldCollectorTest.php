<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Action\Move;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_FormElement_Field_ArtifactId;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElement_Field_SubmittedBy;
use Tracker_FormElement_Field_Text;

require_once __DIR__ . '/../../../bootstrap.php';

class FeedbackFieldCollectorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testReadOnlyFieldsAreNotAddedAsNotMigratedFields()
    {
        $collector = new FeedbackFieldCollector();

        $artifact_id_field = new Tracker_FormElement_Field_ArtifactId(
            1,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $submitted_field = new Tracker_FormElement_Field_SubmittedBy(
            2,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $text_field = new Tracker_FormElement_Field_Text(
            3,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $numeric_field = new Tracker_FormElement_Field_Integer(
            4,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getFormElementFields')->andReturn([
            $artifact_id_field,
            $submitted_field,
            $text_field,
            $numeric_field
        ]);

        $collector->initAllTrackerFieldAsNotMigrated($tracker);

        $fields_not_migrated = $collector->getFieldsNotMigrated();
        $this->assertCount(2, $fields_not_migrated);
        $this->assertEquals($fields_not_migrated[3], $text_field);
        $this->assertEquals($fields_not_migrated[4], $numeric_field);
    }
}
