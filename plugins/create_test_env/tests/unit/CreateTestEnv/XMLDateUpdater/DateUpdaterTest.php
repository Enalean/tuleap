<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace XMLDateUpdater;

use PHPUnit\Framework\TestCase;
use Tuleap\CreateTestEnv\XMLDateUpdater\DateUpdater;

class DateUpdaterTest extends TestCase
{

    public function testItUpdatesTheDatesInTheXMLFileWithDateInArtifacts()
    {
        $xml      = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/project.xml'));

        $updater = new DateUpdater(
            new \DateTimeImmutable('2018-09-21'),
            new \DateTimeImmutable('2018-10-04')
        );

        $updater->updateDateValuesInXML($xml);

        $this->assertEquals(
            new \DateTimeImmutable('2018-09-23T15:02:16+02:00'),
            new \DateTimeImmutable((string) $xml->trackers->tracker->artifacts->artifact->changeset[0]->submitted_on)
        );

        $this->assertEquals(
            new \DateTimeImmutable("2018-10-04T00:00:00+02:00"),
            new \DateTimeImmutable($xml->trackers->tracker->artifacts->artifact->changeset[0]->field_change[1]->value)
        );

        $this->assertEquals(
            new \DateTimeImmutable("2018-10-07T15:02:36+02:00"),
            new \DateTimeImmutable($xml->trackers->tracker->artifacts->artifact->changeset[1]->submitted_on)
        );

        $this->assertEquals(
            new \DateTimeImmutable("2018-10-12T15:02:36+02:00"),
            new \DateTimeImmutable((string) $xml->trackers->tracker->artifacts->artifact->changeset[1]->comments->comment->submitted_on)
        );
    }

    public function testItUpdatesTheDatesInTheXMLFileWithDateInTrackerAndArtifacts()
    {
        $xml      = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/project4.xml'));

        $updater = new DateUpdater(
            new \DateTimeImmutable('2017-05-04'),
            new \DateTimeImmutable('2018-08-16')
        );

        $updater->updateDateValuesInXML($xml);

        $this->assertEquals(
            1534888800,
            (int) $xml->trackers->tracker->formElements->formElement[1]->formElements->formElement->formElements->formElement[0]->properties['default_value']
        );

        $this->assertEquals(
            new \DateTimeImmutable("2018-08-15T13:02:16+00:00"),
            new \DateTimeImmutable((string) $xml->trackers->tracker->artifacts->artifact->changeset[0]->submitted_on)
        );

        $this->assertEquals(
            new \DateTimeImmutable("2018-08-16T13:02:36+00:00"),
            new \DateTimeImmutable((string) $xml->trackers->tracker->artifacts->artifact->changeset[1]->submitted_on)
        );

        $this->assertEquals(
            new \DateTimeImmutable("2018-08-16T13:02:36+00:00"),
            new \DateTimeImmutable((string) $xml->trackers->tracker->artifacts->artifact->changeset[1]->comments->comment->submitted_on)
        );
    }
}
