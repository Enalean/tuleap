<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tracker\XML\Updater;

use SimpleXMLElement;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\Test\Builders\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Builders\TypePresenterBuilder;
use Tuleap\Tracker\Test\Stub\AllTypesRetrieverStub;
use Tuleap\Tracker\Test\Stub\RetrieveAllUsableTypesInProjectStub;

final class FieldChangeArtifactLinksUpdaterTest extends TestCase
{
    private const ARTIFACT_ID  = "101";
    private const USER_ID      = 201;
    private const SUBMITTED_ON = "123456789";

    private \Tracker_FormElement_Field_ArtifactLink $destination_link_field;
    private FieldChangeArtifactLinksUpdater $updater;
    private TypePresenter $system_type_1;
    private TypePresenter $custom_type_1;

    protected function setUp(): void
    {
        $this->system_type_1 = TypePresenterBuilder::aSystemType()->withShortname('system_1')->build();
        $system_type_2       = TypePresenterBuilder::aSystemType()->withShortname('system_2')->build();
        $this->custom_type_1 = TypePresenterBuilder::aCustomType()->withShortname('custom')->build();

        $destination_tracker = TrackerTestBuilder::aTracker()->withProject(
            ProjectTestBuilder::aProject()->build()
        )->build();

        $this->destination_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $this->destination_link_field->setTracker($destination_tracker);

        $this->updater = new FieldChangeArtifactLinksUpdater(
            AllTypesRetrieverStub::withTypes(
                $this->system_type_1,
                $system_type_2,
                $this->custom_type_1,
            ),
            RetrieveAllUsableTypesInProjectStub::withUsableTypes(
                $system_type_2,
                $this->custom_type_1
            ),
            \Codendi_HTMLPurifier::instance(),
            RetrieveUserByIdStub::withUser(UserTestBuilder::aUser()->withId(self::USER_ID)->withUserName("user")->build())
        );
    }

    public function testItResetsTheTypeNatureWhenItIsASystemType(): void
    {
        $changeset_xml = $this->getChangesetXMLWithLinkType($this->system_type_1);

        $this->updater->updateArtifactLinks(
            $changeset_xml,
            $this->destination_link_field,
            0
        );

        $this->assertSame(\Tracker_FormElement_Field_ArtifactLink::NO_TYPE, (string) $changeset_xml->field_change[0]->value->attributes()->nature);
        $this->assertSame(self::ARTIFACT_ID, (string) $changeset_xml->field_change[0]->value);
        $this->assertSame('The type "system_1" of the link to artifact #101 has been set to "no type"', (string) $changeset_xml->comments->comment->body);
    }

    public function testItResetsTheTypeNatureWhenItIsNotUsedInTheDestinationProject(): void
    {
        $changeset_xml = $this->getChangesetXMLWithLinkType(
            TypePresenterBuilder::aCustomType()->withShortname('my_custom_type')->build()
        );

        $this->updater->updateArtifactLinks(
            $changeset_xml,
            $this->destination_link_field,
            0
        );

        $this->assertSame(\Tracker_FormElement_Field_ArtifactLink::NO_TYPE, (string) $changeset_xml->field_change[0]->value->attributes()->nature);
        $this->assertSame(self::ARTIFACT_ID, (string) $changeset_xml->field_change[0]->value);
        $this->assertSame('The type "my_custom_type" of the link to artifact #101 has been set to "no type"', (string) $changeset_xml->comments->comment->body);
    }

    public function testItLeavesTheLinkAsItIsWhenItIsACustomNatureAndItIsUsedInTheDestinationProject(): void
    {
        $changeset_xml = $this->getChangesetXMLWithLinkType($this->custom_type_1);

        $this->updater->updateArtifactLinks(
            $changeset_xml,
            $this->destination_link_field,
            0
        );

        $this->assertSame($this->custom_type_1->shortname, (string) $changeset_xml->field_change[0]->value->attributes()->nature);
        $this->assertSame(self::ARTIFACT_ID, (string) $changeset_xml->field_change[0]->value);
        $this->assertSame('', (string) $changeset_xml->comments->comment->body);
    }

    private function getChangesetXMLWithLinkType(TypePresenter $type): SimpleXMLElement
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<changeset>'
            . '    <submitted_by format="id">' . self::USER_ID . '</submitted_by>'
            . '    <submitted_on format="ISO8601">' . self::SUBMITTED_ON . '</submitted_on>'
            . '    <field_change field_name="links" type="art_link">'
            . '        <value nature="' . $type->shortname . '">' . self::ARTIFACT_ID . '</value>'
            . '    </field_change>'
            . '</changeset>';

        return new SimpleXMLElement($xml);
    }
}
