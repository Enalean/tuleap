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
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\CheckMetadataUsage;
use Tuleap\CrossTracker\SearchOnDuckTypedFieldsConfig;
use Tuleap\CrossTracker\Tests\Builders\InvalidSearchableCollectorParametersBuilder;
use Tuleap\CrossTracker\Tests\Stub\MetadataCheckerStub;
use Tuleap\CrossTracker\Tests\Stub\SearchFieldTypesStub;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final class InvalidSearchableCollectorVisitorTest extends TestCase
{
    use ForgeConfigSandbox;

    private CheckMetadataUsage $metadata_checker;
    private FieldUsageChecker $field_checker;
    private InvalidSearchableCollectorParameters $parameters;

    protected function setUp(): void
    {
        $this->metadata_checker = MetadataCheckerStub::withValidMetadata();
        $this->field_checker    = new FieldUsageChecker(SearchFieldTypesStub::withTypes('int'));
        $this->parameters       = InvalidSearchableCollectorParametersBuilder::aParameter()->build();
    }

    public function testItAddsFieldToInvalidCollectionWhenFFIsOff(): void
    {
        $field   = new Field("a_field");
        $visitor = new InvalidSearchableCollectorVisitor(
            $this->metadata_checker,
            $this->field_checker
        );

        $visitor->visitField($field, $this->parameters);
        self::assertTrue($this->parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->hasInvalidSearchable());
    }

    public function testItAddsInvalidFieldIntoCollection(): void
    {
        \ForgeConfig::set("feature_flag_" . SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS, '1');

        $invalid_field_checker = new FieldUsageChecker(SearchFieldTypesStub::withTypes('invalid'));
        $field                 = new Field("a_field");
        $visitor               = new InvalidSearchableCollectorVisitor(
            $this->metadata_checker,
            $invalid_field_checker
        );

        $visitor->visitField($field, $this->parameters);
        self::assertTrue($this->parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->hasInvalidSearchable());
    }

    public function testItChecksFieldIsValid(): void
    {
        \ForgeConfig::set("feature_flag_" . SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS, '1');

        $field   = new Field("a_field");
        $visitor = new InvalidSearchableCollectorVisitor(
            $this->metadata_checker,
            $this->field_checker
        );

        $visitor->visitField($field, $this->parameters);
        self::assertFalse($this->parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->hasInvalidSearchable());
    }

    public function testItAddsUnknownMetadataToInvalidCollection(): void
    {
        $metadata = new Metadata("unknown");
        $visitor  = new InvalidSearchableCollectorVisitor(
            $this->metadata_checker,
            $this->field_checker
        );

        $visitor->visitMetadata($metadata, $this->parameters);
        self::assertTrue($this->parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->hasInvalidSearchable());
    }

    public function testItAllowsValidMetadata(): void
    {
        $metadata = new Metadata("title");
        $visitor  = new InvalidSearchableCollectorVisitor(
            $this->metadata_checker,
            $this->field_checker
        );

        $visitor->visitMetadata($metadata, $this->parameters);
        self::assertFalse($this->parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->hasInvalidSearchable());
    }

    public function testItAddsInvalidMetadataToCollection(): void
    {
        $invalid_metadata_checker = MetadataCheckerStub::withInvalidMetadata();
        $metadata                 = new Metadata("title");

        $visitor = new InvalidSearchableCollectorVisitor(
            $invalid_metadata_checker,
            $this->field_checker
        );

        $visitor->visitMetadata($metadata, $this->parameters);
        self::assertTrue($this->parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->hasInvalidSearchable());
    }
}
