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
use Tracker;
use Tuleap\ForgeConfigSandbox;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class RichTextareaProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private FileUploadDataProvider & Stub $first_usable_field_data_getter;

    public function setUp(): void
    {
        $this->first_usable_field_data_getter = $this->createStub(FileUploadDataProvider::class);

        ForgeConfig::set('sys_max_size_upload', 1024);
    }

    private function renderTextarea(
        Tracker $tracker,
        ?Artifact $artifact,
        string $field_id,
        string $field_name,
        int $number_of_rows,
        int $number_of_cols,
        string $field_value,
        bool $is_required,
        bool $is_artifact_copy,
    ): string {
        $template_renderer_factory = TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build();

        $provider = new RichTextareaProvider(
            $template_renderer_factory,
            new UploadDataAttributesForRichTextEditorBuilder($this->first_usable_field_data_getter)
        );

        return $provider->getTextarea(
            $tracker,
            $artifact,
            UserTestBuilder::aUser()->build(),
            $field_id,
            $field_name,
            $number_of_rows,
            $number_of_cols,
            $field_value,
            $is_required,
            $is_artifact_copy
        );
    }

    public function testItPassesArgumentsAsPresenterToTheTemplate(): void
    {
        $tracker = $this->buildTracker(7);
        $this->first_usable_field_data_getter->method('getFileUploadData')->willReturn(null);

        $textarea = $this->renderTextarea(
            $tracker,
            null,
            'input-id',
            'input-name',
            8,
            80,
            'input-value',
            true,
            false
        );
        self::assertSame(
            <<<EOL
<textarea
        id="input-id"
        class="user-mention"
        wrap="soft"
        rows="8"
        cols="80"
        name="input-name"
        maxlength="65535"
        required
            data-project-id="7"
        data-test="input-name"
>input-value</textarea>
<div class="muted tracker-richtexteditor-help" id="input-id-help"></div>

EOL
            ,
            $textarea
        );
    }

    public function testItDoesNotAllowDragAndDropDuringArtifactCopy(): void
    {
        $tracker     = $this->buildTracker(7);
        $upload_data = new FileUploadData(FileFieldBuilder::aFileField(1002)->build());

        $this->first_usable_field_data_getter->method('getFileUploadData')->willReturn($upload_data);

        $textarea = $this->renderTextarea(
            $tracker,
            null,
            'input-id',
            'input-name',
            8,
            80,
            'input-value',
            true,
            true
        );
        self::assertSame(
            <<<EOL
<textarea
        id="input-id"
        class="user-mention"
        wrap="soft"
        rows="8"
        cols="80"
        name="input-name"
        maxlength="65535"
        required
            data-project-id="7"
        data-test="input-name"
>input-value</textarea>
<div class="muted tracker-richtexteditor-help" id="input-id-help"></div>

EOL
            ,
            $textarea
        );
    }

    public function testItIncludesUploadOptionsIfAFileFieldIsUpdatable(): void
    {
        $tracker     = $this->buildTracker(7);
        $upload_data = new FileUploadData(FileFieldBuilder::aFileField(1002)->build());

        $this->first_usable_field_data_getter->method('getFileUploadData')->willReturn($upload_data);

        $textarea = $this->renderTextarea(
            $tracker,
            null,
            'input-id',
            'input-name',
            8,
            80,
            'input-value',
            false,
            false
        );
        self::assertSame(
            <<<EOL
<textarea
        id="input-id"
        class="user-mention"
        wrap="soft"
        rows="8"
        cols="80"
        name="input-name"
        maxlength="65535"
        \n            data-project-id="7"
            data-upload-url="/api/v1/tracker_fields/1002/files"
            data-upload-field-name="artifact[1002][][tus-uploaded-id]"
            data-upload-max-size="1024"
            data-help-id="input-id-help"
        data-test="input-name"
>input-value</textarea>
<div class="muted tracker-richtexteditor-help" id="input-id-help"></div>

EOL
            ,
            $textarea
        );
    }

    private function buildTracker(int $project_id): Tracker
    {
        $project = ProjectTestBuilder::aProject()->withId($project_id)->build();
        return TrackerTestBuilder::aTracker()->withProject($project)->build();
    }
}
