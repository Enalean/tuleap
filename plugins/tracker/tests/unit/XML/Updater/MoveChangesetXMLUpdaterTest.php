<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\XML\Updater;

use SimpleXMLElement;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class MoveChangesetXMLUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MoveChangesetXMLUpdater $updater;

    protected function setUp(): void
    {
        $this->updater = new MoveChangesetXMLUpdater();
    }

    public function testChangeIsNotDeletableWhenAtLeastAFieldHasChanged(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
        <changesets>
            <changeset>
                <comments/>
                <field_change field_name="status" type="list" bind="static">
                  <value format="id">320</value>
                </field_change>
                <field_change field_name="customer" type="list" bind="static">
                  <value/>
                </field_change>
            </changeset>
            <changeset>
                <comments/>
                <field_change field_name="status" type="list" bind="static">
                  <value format="id">123</value>
                </field_change>
            </changeset>
            <changeset>
                <comments/>
                <external_field_change field_name="some_field" type="some_type">
                  <value format="id">123</value>
                </external_field_change>
            </changeset>
            </changesets>';
        $changeset_xml = new SimpleXMLElement($xml);

        $this->assertFalse($this->updater->isChangesetNodeDeletable($changeset_xml, 1));
        $this->assertFalse($this->updater->isChangesetNodeDeletable($changeset_xml, 2));
    }

    public function testChangesIsNotDeletableWhenCommentHasChanged(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
            <changesets>
                <changeset>
                    <comments><comment>A comment</comment><field-change /></comments>
                </changeset>
                <changeset>
                    <comments><comment>A comment</comment><field-change /></comments>
                </changeset>
            </changesets>';
        $changeset_xml = new SimpleXMLElement($xml);

        $this->assertFalse($this->updater->isChangesetNodeDeletable($changeset_xml, 1));
    }

    public function testFirstChangeIsNotDeletable(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
            <changesets><changeset></changeset></changesets>';
        $changeset_xml = new SimpleXMLElement($xml);

        $this->assertFalse($this->updater->isChangesetNodeDeletable($changeset_xml, 0));
    }

    public function testChangeIsDeletable(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
            <changesets><changeset><field_change /><comments /></changeset><changeset></changeset></changesets>';
        $changeset_xml = new SimpleXMLElement($xml);

        $this->assertTrue($this->updater->isChangesetNodeDeletable($changeset_xml, 1));
    }

    public function testItDeletesAChangesetNode(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
            <changesets>
                <changeset><comments/></changeset>
            </changesets>';
        $changeset_xml = new SimpleXMLElement($xml);

        $this->updater->deleteChangesetNode($changeset_xml, 0);
        $this->assertSame('', trim((string) $changeset_xml));
    }

    public function testItDeletesAFieldChangeNode(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
            <changeset>
                <field_change>
                    <value/>
                </field_change>
            </changeset>';
        $changeset_xml = new SimpleXMLElement($xml);

        $this->updater->deleteFieldChangeNode($changeset_xml, 0);
        $this->assertSame('', trim((string) $changeset_xml));
    }

    public function testItDeletesAnExternalFieldChangeNode(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
            <changeset>
                <external_field_change>
                    <value/>
                </external_field_change>
            </changeset>';
        $changeset_xml = new SimpleXMLElement($xml);

        $this->updater->deleteExternalFieldChangeNode($changeset_xml, 0);
        $this->assertSame('', trim((string) $changeset_xml));
    }

    public function testItDeletesAFieldChangeValueNode(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
            <changeset>
                <field_change>
                    <value/>
                </field_change>
            </changeset>';
        $changeset_xml = new SimpleXMLElement($xml);

        $this->updater->deleteFieldChangeValueNode($changeset_xml, 0);
        $this->assertSame('', trim((string) $changeset_xml->value));
    }

    public function testItdeletesASpecificValueInAGivenFieldChange(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
            <changeset>
                <field_change>
                    <value>Value 1</value>
                    <value>Value 2</value>
                    <value>Value 3</value>
                </field_change>
            </changeset>';
        $changeset_xml = new SimpleXMLElement($xml);

        $this->updater->deleteValueInFieldChangeAtIndex($changeset_xml, 0, 2);

        $this->assertCount(2, $changeset_xml[0]->field_change[0]->value);
        $this->assertSame('Value 1', (string) $changeset_xml[0]->field_change[0]->value[0]);
        $this->assertSame('Value 2', (string) $changeset_xml[0]->field_change[0]->value[1]);
    }

    public function testItDoesNotDeleteCommentsWhenThereIsAComment(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
                <changeset><comments><comment><body>A comment</body></comment></comments></changeset>';
        $changeset_xml = new SimpleXMLElement($xml);

        $this->updater->deleteEmptyCommentsNode($changeset_xml);
        $this->assertSame('A comment', (string) $changeset_xml->comments->comment->body);
    }

    public function testItDeletesCommentsAnEmptyCommentNode(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
                <changeset><comments><comment></comment></comments></changeset>';
        $changeset_xml = new SimpleXMLElement($xml);

        $this->updater->deleteEmptyCommentsNode($changeset_xml);
        $this->assertSame('', trim((string) $changeset_xml->comments->comment));
    }

    public function testItReturnsFalseWhenFieldChangesDoesNotBelongToGivenField(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
            <changeset>
                <comments/>
                <field_change field_name="status" type="list" bind="static">
                  <value format="id">320</value>
                </field_change>
            </changeset>';
        $changeset_xml = new SimpleXMLElement($xml);
        $field         = StringFieldBuilder::aStringField(1)->withName('a_field')->build();

        $this->assertFalse($this->updater->isFieldChangeCorrespondingToField($changeset_xml, $field, 0));
    }

    public function testItReturnsTrueWhenFieldChangesBelongToField(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
            <changeset>
                <comments/>
                <field_change field_name="status" type="list" bind="static">
                  <value format="id">320</value>
                </field_change>
            </changeset>';
        $changeset_xml = new SimpleXMLElement($xml);
        $field         = StringFieldBuilder::aStringField(1)->withName('status')->build();

        $this->assertTrue($this->updater->isFieldChangeCorrespondingToField($changeset_xml, $field, 0));
    }

    public function testItUsesTargetFieldName(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
            <changeset>
                <field_change field_name="status" type="list" bind="static">
                  <value format="id">320</value>
                </field_change>
            </changeset>';
        $changeset_xml = new SimpleXMLElement($xml);
        $field         = StringFieldBuilder::aStringField(1)->withName('a_field')->build();

        $this->updater->useTargetTrackerFieldName($changeset_xml, $field, 0);
        $this->assertSame('a_field', (string) $changeset_xml->field_change->attributes()->field_name);
    }

    public function testItAddsLastMovedChangesetComment(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
            <changesets>
            </changesets>';
        $changeset_xml = new SimpleXMLElement($xml);
        $project       = ProjectTestBuilder::aProject()->withId(209)->build();
        $tracker       = TrackerTestBuilder::aTracker()->withName('tracker')->withProject($project)->build();
        $user          = UserTestBuilder::anActiveUser()->withId(120)->build();

        $this->updater->addLastMovedChangesetComment(
            $user,
            $changeset_xml,
            $tracker,
            0
        );

        $this->assertSame((string) $user->getId(), (string) $changeset_xml->changeset->submitted_by);
        $this->assertSame('1970-01-01T01:00:00+01:00', (string) $changeset_xml->changeset->submitted_on);
        $this->assertSame(
            "Artifact was moved from 'tracker' tracker in 'The Test Project' project.",
            (string) $changeset_xml->changeset->comments->comment->body
        );
    }

    public function testItAddsSubmittedInformation(): void
    {
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>
            <changesets>
            </changesets>';
        $changeset_xml = new SimpleXMLElement($xml);
        $user          = UserTestBuilder::anActiveUser()->withId(120)->build();

        $this->updater->addSubmittedInformation($changeset_xml, $user, 123456789);

        $this->assertSame((string) $user->getId(), (string) $changeset_xml->submitted_by);
        $this->assertSame('1973-11-29T22:33:09+01:00', (string) $changeset_xml->submitted_on);
    }
}
