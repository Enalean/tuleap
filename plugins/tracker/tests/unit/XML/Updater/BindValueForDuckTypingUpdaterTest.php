<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\XML\Updater;

use SimpleXMLElement;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveMatchingValueByDuckTypingStub;
use Tuleap\Tracker\Tracker\XML\Updater\BindValueForDuckTypingUpdater;
use XML_SimpleXMLCDATAFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BindValueForDuckTypingUpdaterTest extends TestCase
{
    private \Tuleap\Tracker\FormElement\Field\List\SelectboxField $source_field;
    private \Tuleap\Tracker\FormElement\Field\List\SelectboxField $target_field;

    protected function setUp(): void
    {
        $this->source_field = SelectboxFieldBuilder::aSelectboxField(1)->build();
        $this->target_field = SelectboxFieldBuilder::aSelectboxField(1)->build();
    }

    public function testItDoesNotSetBindValueWhenXmlValueIsZero(): void
    {
        ListStaticBindBuilder::aStaticBind($this->target_field)->build();
        ListStaticBindBuilder::aStaticBind($this->source_field)->build();
        $xml                 = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';
        $changeset_xml       = new SimpleXMLElement($xml);
        $field_change        = $changeset_xml->addChild('field_change');
        $field_change->value = 0;

        $field_value_matcher = RetrieveMatchingValueByDuckTypingStub::withMatchingValues([0 => 0]);
        $updater             = new BindValueForDuckTypingUpdater($field_value_matcher, new MoveChangesetXMLUpdater(), new XML_SimpleXMLCDATAFactory(), ProvideAndRetrieveUserStub::build(UserTestBuilder::aUser()->build()));

        $updater->updateValueForDuckTypingMove($changeset_xml, $this->source_field, $this->target_field, 0);
        self::assertSame('0', (string) $changeset_xml->field_change[0]->value);
    }

    public function testItSetBindValueForSingleValueSelect(): void
    {
        ListStaticBindBuilder::aStaticBind($this->target_field)->build();
        ListStaticBindBuilder::aStaticBind($this->source_field)->build();
        $xml                 = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';
        $changeset_xml       = new SimpleXMLElement($xml);
        $field_change        = $changeset_xml->addChild('field_change');
        $field_change->value = 101;

        $field_value_matcher = RetrieveMatchingValueByDuckTypingStub::withMatchingValues([101 => 309]);
        $updater             = new BindValueForDuckTypingUpdater($field_value_matcher, new MoveChangesetXMLUpdater(), new XML_SimpleXMLCDATAFactory(), ProvideAndRetrieveUserStub::build(UserTestBuilder::aUser()->build()));

        $updater->updateValueForDuckTypingMove($changeset_xml, $this->source_field, $this->target_field, 0);
        self::assertSame('309', (string) $changeset_xml->field_change[0]->value);
    }

    public function testItSetBindValueForMultipleValuesSelect(): void
    {
        ListStaticBindBuilder::aStaticBind($this->target_field)->build();
        ListStaticBindBuilder::aStaticBind($this->source_field)->build();
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<changeset>'
            . '    <field_change field_name="plop" type="list" bind="static">'
            . '        <value format="id">101</value>'
            . '        <value format="id">102</value>'
            . '    </field_change>'
            . '</changeset>';
        $changeset_xml = new SimpleXMLElement($xml);

        $field_value_matcher = RetrieveMatchingValueByDuckTypingStub::withMatchingValues([102 => 190]);
        $updater             = new BindValueForDuckTypingUpdater($field_value_matcher, new MoveChangesetXMLUpdater(), new XML_SimpleXMLCDATAFactory(), ProvideAndRetrieveUserStub::build(UserTestBuilder::aUser()->build()));

        $updater->updateValueForDuckTypingMove($changeset_xml, $this->source_field, $this->target_field, 0);
        self::assertSame('190', (string) $changeset_xml->field_change[0]->value);
    }

    public function testItIgnoresDuplicates(): void
    {
        ListStaticBindBuilder::aStaticBind($this->target_field)->build();
        ListStaticBindBuilder::aStaticBind($this->source_field)->build();
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<changeset>'
            . '    <field_change field_name="plop" type="list" bind="static">'
            . '        <value format="id">101</value>'
            . '        <value format="id">102</value>'
            . '    </field_change>'
            . '</changeset>';
        $changeset_xml = new SimpleXMLElement($xml);

        $field_value_matcher = RetrieveMatchingValueByDuckTypingStub::withMatchingValues([
            101 => 190,
            102 => 190,
        ]);

        $cdata_factory = new XML_SimpleXMLCDATAFactory();
        $updater       = new BindValueForDuckTypingUpdater($field_value_matcher, new MoveChangesetXMLUpdater(), $cdata_factory, ProvideAndRetrieveUserStub::build(UserTestBuilder::aUser()->build()));

        $updater->updateValueForDuckTypingMove($changeset_xml, $this->source_field, $this->target_field, 0);
        $this->addToAssertionCount(1);
    }

    public function testItBindsValueWithAUserInUserNameFormat(): void
    {
        ListUserBindBuilder::aUserBind($this->target_field)->build();
        ListUserBindBuilder::aUserBind($this->source_field)->build();

        $user          = UserTestBuilder::aUser()->withId(102)->withUserName('my-user-name')->build();
        $retrieve_user = ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithDefaults())->withUsers([$user]);
        $xml           = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<changeset>'
            . '    <field_change field_name="plop" type="list" bind="users">'
            . '        <value format="username">' . $user->getUserName() . '</value>'
            . '    </field_change>'
            . '</changeset>';
        $changeset_xml = new SimpleXMLElement($xml);

        $field_value_matcher = RetrieveMatchingValueByDuckTypingStub::withMatchingValues([102 => 190]);

        $updater = new BindValueForDuckTypingUpdater(
            $field_value_matcher,
            new MoveChangesetXMLUpdater(),
            new XML_SimpleXMLCDATAFactory(),
            $retrieve_user
        );

        $updater->updateValueForDuckTypingMove($changeset_xml, $this->source_field, $this->target_field, 0);
        self::assertSame(190, (int) $changeset_xml->field_change[0]->value);
    }
}
