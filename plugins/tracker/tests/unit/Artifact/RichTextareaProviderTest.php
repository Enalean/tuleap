<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use ForgeConfig;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RichTextareaProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private const PROJECT_ID      = 196;
    private const UPLOAD_MAX_SIZE = 1024;

    private FileUploadDataProvider&Stub $first_usable_field_data_getter;

    public function setUp(): void
    {
        $this->first_usable_field_data_getter = $this->createStub(FileUploadDataProvider::class);

        ForgeConfig::set('sys_max_size_upload', self::UPLOAD_MAX_SIZE);
    }

    private function getTextarea(
        RichTextareaConfiguration $configuration,
        bool $is_artifact_copy,
    ): RichTextareaPresenter {
        $provider = new RichTextareaProvider(
            new UploadDataAttributesForRichTextEditorBuilder($this->first_usable_field_data_getter)
        );

        return $provider->getTextarea($configuration, $is_artifact_copy);
    }

    public function testItBuildsTextAreaForNewFollowupComment(): void
    {
        $this->first_usable_field_data_getter->method('getFileUploadData')->willReturn(null);

        $comment_body = 'input-value';
        $textarea     = $this->getTextarea(
            RichTextareaConfiguration::fromNewFollowUpComment(
                $this->buildTracker(),
                ArtifactTestBuilder::anArtifact(781)->build(),
                UserTestBuilder::buildWithDefaults(),
                $comment_body
            ),
            false
        );
        self::assertSame('tracker_followup_comment_new', $textarea->id);
        self::assertSame('artifact_followup_comment', $textarea->name);
        self::assertSame(8, $textarea->rows);
        self::assertSame(80, $textarea->cols);
        self::assertSame($comment_body, $textarea->value);
        self::assertFalse($textarea->is_required);
        self::assertSame('tracker_followup_comment_new-help', $textarea->help_id);
        self::assertFalse($textarea->is_dragndrop_allowed);
        self::assertEqualsCanonicalizing([
            ['name' => 'project-id', 'value' => self::PROJECT_ID],
        ], $textarea->data_attributes);
        self::assertSame(TextValueValidator::MAX_TEXT_SIZE, $textarea->maxlength);
    }

    public function testItDoesNotAllowImageUploadDuringArtifactCopy(): void
    {
        $upload_data = new FileUploadData(FileFieldBuilder::aFileField(1002)->build());

        $this->first_usable_field_data_getter->method('getFileUploadData')->willReturn($upload_data);

        $text_field_id     = 335;
        $number_of_rows    = 10;
        $number_of_columns = 200;
        $text_field_body   = 'input-value';

        $textarea = $this->getTextarea(
            RichTextareaConfiguration::fromTextField(
                $this->buildTracker(),
                ArtifactTestBuilder::anArtifact(211)->build(),
                UserTestBuilder::buildWithDefaults(),
                TextFieldBuilder::aTextField($text_field_id)->thatIsRequired()
                    ->withNumberOfRows($number_of_rows)
                    ->withNumberOfColumns($number_of_columns)
                    ->build(),
                $text_field_body
            ),
            true
        );
        self::assertSame("field_$text_field_id", $textarea->id);
        self::assertSame("artifact[$text_field_id][content]", $textarea->name);
        self::assertSame($number_of_rows, $textarea->rows);
        self::assertSame($number_of_columns, $textarea->cols);
        self::assertSame($text_field_body, $textarea->value);
        self::assertTrue($textarea->is_required);
        self::assertSame("field_$text_field_id-help", $textarea->help_id);
        self::assertFalse($textarea->is_dragndrop_allowed);
        self::assertEqualsCanonicalizing([
            ['name' => 'project-id', 'value' => self::PROJECT_ID],
        ], $textarea->data_attributes);
        self::assertSame(TextValueValidator::MAX_TEXT_SIZE, $textarea->maxlength);
    }

    public function testItIncludesUploadOptionsIfAFileFieldIsUpdatable(): void
    {
        $file_field_id = 1002;
        $upload_data   = new FileUploadData(FileFieldBuilder::aFileField($file_field_id)->build());

        $this->first_usable_field_data_getter->method('getFileUploadData')->willReturn($upload_data);

        $text_field_id = 489;
        $textarea      = $this->getTextarea(
            RichTextareaConfiguration::fromTextField(
                $this->buildTracker(),
                ArtifactTestBuilder::anArtifact(131)->build(),
                UserTestBuilder::buildWithDefaults(),
                TextFieldBuilder::aTextField($text_field_id)->withNumberOfRows(8)
                    ->withNumberOfColumns(80)
                    ->build(),
                'input-value',
            ),
            false
        );
        self::assertEqualsCanonicalizing([
            ['name' => 'project-id', 'value' => self::PROJECT_ID],
            ['name' => 'upload-url', 'value' => "/api/v1/tracker_fields/$file_field_id/files"],
            ['name' => 'upload-field-name', 'value' => "artifact[$file_field_id][][tus-uploaded-id]"],
            ['name' => 'upload-max-size', 'value' => self::UPLOAD_MAX_SIZE],
            ['name' => 'help-id', 'value' => $textarea->help_id],
        ], $textarea->data_attributes);
    }

    private function buildTracker(): Tracker
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        return TrackerTestBuilder::aTracker()->withProject($project)->build();
    }
}
