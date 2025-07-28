<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Document\Field\SuitableFieldRetriever;
use Tuleap\Artidoc\Document\Tracker\TrackerNotFoundFault;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Field\ArtifactSectionField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldDisplayTypeIsUnknownFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldDoesNotBelongToTrackerFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldNotFoundFault;
use Tuleap\Artidoc\Domain\Document\UserCannotWriteDocumentFault;
use Tuleap\Artidoc\Stubs\Document\SaveConfigurationStub;
use Tuleap\Artidoc\Stubs\Document\Tracker\CheckTrackerIsSuitableForDocumentStub;
use Tuleap\Artidoc\Stubs\Domain\Document\RetrieveArtidocWithContextStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Test\Stub\Semantic\Description\RetrieveSemanticDescriptionFieldStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PUTConfigurationHandlerTest extends TestCase
{
    private const ARTIDOC_ID         = 1;
    private const PROJECT_ID         = 101;
    private const TRACKER_ID         = 1001;
    private const ANOTHER_TRACKER_ID = 1002;
    private const FIELD_1_ID         = 201;
    private const FIELD_2_ID         = 202;

    private SaveConfigurationStub $saver;
    private \Tuleap\Tracker\Tracker $tracker;
    /**
     * @psalm-var list<ConfiguredFieldRepresentation>
     */
    private array $input_fields;
    private \PFUser $user;
    private RetrieveArtidocWithContextStub $retrieve_artidoc;
    private RetrieveTrackerStub $retrieve_tracker;
    private CheckTrackerIsSuitableForDocumentStub $tracker_checker;
    private RetrieveUsedFieldsStub $field_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()
            ->withUserCanView(true)
            ->withId(self::TRACKER_ID)
            ->build();

        $this->retrieve_artidoc = RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
            new ArtidocWithContext(
                new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
            ),
        );

        $this->field_retriever  = RetrieveUsedFieldsStub::withNoFields();
        $this->saver            = SaveConfigurationStub::noop();
        $this->retrieve_tracker = RetrieveTrackerStub::withTracker($this->tracker);
        $this->tracker_checker  = CheckTrackerIsSuitableForDocumentStub::withSuitableTrackers(
            $this->tracker
        );

        $this->input_fields = [];
        $this->user         = UserTestBuilder::buildWithDefaults();
    }

    private function handle(): Ok|Err
    {
        $handler = new PUTConfigurationHandler(
            $this->retrieve_artidoc,
            $this->saver,
            $this->retrieve_tracker,
            $this->tracker_checker,
            new SuitableFieldRetriever(
                $this->field_retriever,
                RetrieveSemanticDescriptionFieldStub::build(),
                RetrieveSemanticTitleFieldStub::build(),
            ),
        );

        return $handler->handle(
            self::ARTIDOC_ID,
            new PUTConfigurationRepresentation(
                [self::TRACKER_ID],
                $this->input_fields
            ),
            $this->user,
        );
    }

    public function testHappyPath(): void
    {
        $this->input_fields    = [
            new ConfiguredFieldRepresentation(self::FIELD_1_ID, 'column'),
            new ConfiguredFieldRepresentation(self::FIELD_2_ID, 'block'),
        ];
        $this->field_retriever = RetrieveUsedFieldsStub::withFields(
            StringFieldBuilder::aStringField(self::FIELD_1_ID)
                ->withReadPermission($this->user, true)
                ->inTracker($this->tracker)
                ->build(),
            StringFieldBuilder::aStringField(self::FIELD_2_ID)
                ->withReadPermission($this->user, true)
                ->inTracker($this->tracker)
                ->build(),
        );

        $was_saved   = false;
        $this->saver = SaveConfigurationStub::withCallback(
            static function (int $document_id, int $tracker_id, array $fields) use (&$was_saved) {
                $was_saved = true;
                self::assertSame(self::ARTIDOC_ID, $document_id);
                self::assertSame(self::TRACKER_ID, $tracker_id);
                self::assertEqualsCanonicalizing([
                    new ArtifactSectionField(self::FIELD_1_ID, DisplayType::COLUMN),
                    new ArtifactSectionField(self::FIELD_2_ID, DisplayType::BLOCK),
                ], $fields);
            }
        );

        $result = $this->handle();

        self::assertTrue(Result::isOk($result));
        self::assertTrue($was_saved);
    }

    public function testIgnoreMultipleSubmissionOfSameField(): void
    {
        $this->input_fields    = [
            new ConfiguredFieldRepresentation(self::FIELD_1_ID, 'column'),
            new ConfiguredFieldRepresentation(self::FIELD_1_ID, 'block'),
        ];
        $this->field_retriever = RetrieveUsedFieldsStub::withFields(
            StringFieldBuilder::aStringField(self::FIELD_1_ID)
                ->withReadPermission($this->user, true)
                ->inTracker($this->tracker)
                ->build(),
        );

        $was_saved   = false;
        $this->saver = SaveConfigurationStub::withCallback(
            static function (int $document_id, int $tracker_id, array $fields) use (&$was_saved) {
                $was_saved = true;
                self::assertSame(self::ARTIDOC_ID, $document_id);
                self::assertSame(self::TRACKER_ID, $tracker_id);
                self::assertEqualsCanonicalizing([
                    new ArtifactSectionField(self::FIELD_1_ID, DisplayType::BLOCK),
                ], $fields);
            }
        );

        $result = $this->handle();

        self::assertTrue(Result::isOk($result));
        self::assertTrue($was_saved);
    }

    private function assertNeverSaved(): never
    {
        self::fail('Expected NOT to save the configuration');
    }

    public function testFaultWhenFieldCannotBeRetrieved(): void
    {
        $this->input_fields    = [new ConfiguredFieldRepresentation(self::FIELD_1_ID, 'column')];
        $this->field_retriever = RetrieveUsedFieldsStub::withNoFields();

        $this->saver = SaveConfigurationStub::withCallback($this->assertNeverSaved(...));

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldNotFoundFault::class, $result->error);
    }

    public function testFaultWhenFieldBelongsToAnotherTracker(): void
    {
        $this->input_fields    = [new ConfiguredFieldRepresentation(self::FIELD_1_ID, 'column')];
        $another_tracker       = TrackerTestBuilder::aTracker()
            ->withUserCanView(true)
            ->withId(self::ANOTHER_TRACKER_ID)
            ->build();
        $this->field_retriever = RetrieveUsedFieldsStub::withFields(
            StringFieldBuilder::aStringField(self::FIELD_1_ID)
                ->withReadPermission($this->user, true)
                ->inTracker($another_tracker)
                ->build(),
        );

        $this->saver = SaveConfigurationStub::withCallback($this->assertNeverSaved(...));

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldDoesNotBelongToTrackerFault::class, $result->error);
    }

    public function testFaultWhenDisplayTypeIsUnknown(): void
    {
        $this->input_fields = [new ConfiguredFieldRepresentation(self::FIELD_1_ID, 'unknown')];

        $this->saver = SaveConfigurationStub::withCallback($this->assertNeverSaved(...));

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldDisplayTypeIsUnknownFault::class, $result->error);
    }

    public function testFaultWhenDocumentCannotBeRetrieved(): void
    {
        $this->retrieve_artidoc = RetrieveArtidocWithContextStub::withoutDocument();
        $this->tracker_checker  = CheckTrackerIsSuitableForDocumentStub::shouldNotBeCalled();

        $this->saver = SaveConfigurationStub::withCallback($this->assertNeverSaved(...));

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
    }

    public function testFaultWhenDocumentIsNotWritable(): void
    {
        $this->retrieve_artidoc = RetrieveArtidocWithContextStub::withDocumentUserCanRead(
            new ArtidocWithContext(
                new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
            ),
        );
        $this->tracker_checker  = CheckTrackerIsSuitableForDocumentStub::shouldNotBeCalled();

        $this->saver = SaveConfigurationStub::withCallback($this->assertNeverSaved(...));

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UserCannotWriteDocumentFault::class, $result->error);
    }

    public function testFaultWhenTrackerDoesNotExist(): void
    {
        $this->retrieve_tracker = RetrieveTrackerStub::withoutTracker();

        $this->saver = SaveConfigurationStub::withCallback($this->assertNeverSaved(...));

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(TrackerNotFoundFault::class, $result->error);
    }

    public function testFaultWhenTrackerIsNotSuitable(): void
    {
        $this->tracker_checker = CheckTrackerIsSuitableForDocumentStub::withoutSuitableTracker();

        $this->saver = SaveConfigurationStub::withCallback($this->assertNeverSaved(...));

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
    }

    public function testFaultWhenLinkFieldInColumnDisplayType(): void
    {
        $this->input_fields    = [
            new ConfiguredFieldRepresentation(self::FIELD_1_ID, 'column'),
        ];
        $this->field_retriever = RetrieveUsedFieldsStub::withFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::FIELD_1_ID)
                ->withReadPermission($this->user, true)
                ->inTracker($this->tracker)
                ->build(),
        );

        $this->saver = SaveConfigurationStub::withCallback($this->assertNeverSaved(...));

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
    }
}
