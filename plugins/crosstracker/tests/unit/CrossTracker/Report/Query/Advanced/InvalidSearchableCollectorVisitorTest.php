<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Field\FieldUsageChecker;
use Tuleap\CrossTracker\SearchOnDuckTypedFieldsConfig;
use Tuleap\CrossTracker\Tests\Builders\InvalidSearchableCollectorParametersBuilder;
use Tuleap\CrossTracker\Tests\Stub\MetadataCheckerStub;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Searchable;
use Tuleap\Tracker\Test\Builders\TrackerExternalFormElementBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementFloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementIntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

final class InvalidSearchableCollectorVisitorTest extends TestCase
{
    use ForgeConfigSandbox;

    private const FIELD_NAME = 'a_field';
    private MetadataCheckerStub $metadata_checker;
    private InvalidSearchableCollectorParameters $parameters;
    private Searchable $searchable;
    private \PFUser $user;
    private \Tracker $first_tracker;
    private RetrieveUsedFieldsStub $fields_retriever;

    protected function setUp(): void
    {
        $this->first_tracker = TrackerTestBuilder::aTracker()->withId(67)->build();
        $second_tracker      = TrackerTestBuilder::aTracker()->withId(21)->build();
        $this->user          = UserTestBuilder::buildWithId(443);

        $this->metadata_checker = MetadataCheckerStub::withValidMetadata();
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerFormElementIntFieldBuilder::anIntField(628)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementFloatFieldBuilder::aFloatField(274)
                ->withName(self::FIELD_NAME)
                ->inTracker($second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );

        $this->parameters = InvalidSearchableCollectorParametersBuilder::aParameter()
            ->withUser($this->user)
            ->onTrackers($this->first_tracker, $second_tracker)
            ->build();
        $this->searchable = new Field(self::FIELD_NAME);
    }

    private function check(): void
    {
        $visitor = new InvalidSearchableCollectorVisitor(
            $this->metadata_checker,
            new FieldUsageChecker($this->fields_retriever, RetrieveFieldTypeStub::withDetectionOfType())
        );
        $this->searchable->acceptSearchableVisitor($visitor, $this->parameters);
    }

    public function testItAddsFieldToInvalidCollectionWhenFFIsOff(): void
    {
        $this->check();
        self::assertNotEmpty(
            $this->parameters
                ->getInvalidSearchablesCollectorParameters()
                ->getInvalidSearchablesCollection()
                ->getNonexistentSearchables()
        );
    }

    public function testItAddsNotSupportedFieldToInvalidCollection(): void
    {
        \ForgeConfig::set("feature_flag_" . SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS, '1');
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerExternalFormElementBuilder::anExternalField(900)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );

        $this->check();
        self::assertNotEmpty(
            $this->parameters
                ->getInvalidSearchablesCollectorParameters()
                ->getInvalidSearchablesCollection()
                ->getNonexistentSearchables()
        );
    }

    public function testItAddsFieldNotFoundToInvalidCollection(): void
    {
        \ForgeConfig::set("feature_flag_" . SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS, '1');
        $this->fields_retriever = RetrieveUsedFieldsStub::withNoFields();

        $this->check();
        self::assertNotEmpty(
            $this->parameters
                ->getInvalidSearchablesCollectorParameters()
                ->getInvalidSearchablesCollection()
                ->getNonexistentSearchables()
        );
    }

    public function testItAddsFieldUserCanNotReadToInvalidCollection(): void
    {
        \ForgeConfig::set("feature_flag_" . SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS, '1');
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerFormElementIntFieldBuilder::anIntField(628)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, false)
                ->build()
        );

        $this->check();
        self::assertNotEmpty(
            $this->parameters
                ->getInvalidSearchablesCollectorParameters()
                ->getInvalidSearchablesCollection()
                ->getNonexistentSearchables()
        );
    }

    public function testItChecksFieldIsValid(): void
    {
        \ForgeConfig::set("feature_flag_" . SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS, '1');

        $this->check();
        self::assertFalse(
            $this->parameters
                ->getInvalidSearchablesCollectorParameters()
                ->getInvalidSearchablesCollection()
                ->hasInvalidSearchable()
        );
    }

    public function testItAddsUnknownMetadataToInvalidCollection(): void
    {
        $this->searchable = new Metadata("unknown");

        $this->check();
        self::assertNotEmpty(
            $this->parameters
                ->getInvalidSearchablesCollectorParameters()
                ->getInvalidSearchablesCollection()
                ->getNonexistentSearchables()
        );
    }

    public function testItAllowsValidMetadata(): void
    {
        $this->searchable = new Metadata("title");

        $this->check();
        self::assertFalse(
            $this->parameters
                ->getInvalidSearchablesCollectorParameters()
                ->getInvalidSearchablesCollection()
                ->hasInvalidSearchable()
        );
    }

    public function testItAddsInvalidMetadataToCollection(): void
    {
        $this->searchable       = new Metadata("title");
        $this->metadata_checker = MetadataCheckerStub::withInvalidMetadata();

        $this->check();
        self::assertTrue(
            $this->parameters
                ->getInvalidSearchablesCollectorParameters()
                ->getInvalidSearchablesCollection()
                ->hasInvalidSearchable()
        );
    }
}
