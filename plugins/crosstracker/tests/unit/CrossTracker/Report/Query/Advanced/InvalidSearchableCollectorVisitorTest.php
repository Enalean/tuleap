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
use Tuleap\CrossTracker\Tests\Stub\SearchFieldTypesStub;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Searchable;

final class InvalidSearchableCollectorVisitorTest extends TestCase
{
    use ForgeConfigSandbox;

    private MetadataCheckerStub $metadata_checker;
    private InvalidSearchableCollectorParameters $parameters;
    private Searchable $searchable;
    private SearchFieldTypesStub $field_searcher;

    protected function setUp(): void
    {
        $this->metadata_checker = MetadataCheckerStub::withValidMetadata();
        $this->field_searcher   = SearchFieldTypesStub::withTypes('int');

        $this->parameters = InvalidSearchableCollectorParametersBuilder::aParameter()->build();
        $this->searchable = new Field("a_field");
    }

    private function check(): void
    {
        $visitor = new InvalidSearchableCollectorVisitor(
            $this->metadata_checker,
            new FieldUsageChecker($this->field_searcher)
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
        $this->field_searcher = SearchFieldTypesStub::withTypes('invalid');

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
        $this->field_searcher = SearchFieldTypesStub::withNoTypeFound();

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
