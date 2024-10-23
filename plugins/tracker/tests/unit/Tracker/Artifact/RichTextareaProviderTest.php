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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use TemplateRendererFactory;
use Tracker;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Templating\TemplateCache;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class RichTextareaProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var MockInterface|TemplateRendererFactory
     */
    private $template_renderer_factory;
    /**
     * @var RichTextareaProvider
     */
    private $provider;
    /**
     * @var Mockery\LegacyMockInterface|MockInterface|FileUploadDataProvider
     */
    private $first_usable_field_data_getter;

    public function setUp(): void
    {
        $this->first_usable_field_data_getter = Mockery::mock(FileUploadDataProvider::class);

        $template_cache = Mockery::mock(TemplateCache::class);
        $template_cache->shouldReceive('getPath')->andReturn(null);

        $this->template_renderer_factory = new TemplateRendererFactory($template_cache);

        $this->provider = new RichTextareaProvider(
            $this->template_renderer_factory,
            new UploadDataAttributesForRichTextEditorBuilder($this->first_usable_field_data_getter)
        );

        ForgeConfig::set('sys_max_size_upload', 1024);
    }

    public function testItPassesArgumentsAsPresenterToTheTemplate(): void
    {
        $tracker = $this->buildTracker(7);
        $this->first_usable_field_data_getter->shouldReceive('getFileUploadData')->andReturn(null);

        $textarea = $this->provider->getTextarea(
            $tracker,
            null,
            UserTestBuilder::aUser()->build(),
            'input-id',
            'input-name',
            8,
            80,
            'input-value',
            true,
            false
        );
        self::assertEquals(
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
        $tracker = $this->buildTracker(7);
        $field1  = Mockery::mock(FileUploadData::class);
        $field1->shouldReceive('getUploadUrl')->andReturn('/api/v1/tracker_fields/1002/files');
        $field1->shouldReceive('getUploadFileName')->andReturn('artifact[1002][][tus-uploaded-id]');
        $field1->shouldReceive('getUploadMaxSize')->andReturn(1024);

        $this->first_usable_field_data_getter->shouldReceive('getFileUploadData')->andReturn($field1);

        $textarea = $this->provider->getTextarea(
            $tracker,
            null,
            UserTestBuilder::aUser()->build(),
            'input-id',
            'input-name',
            8,
            80,
            'input-value',
            true,
            true
        );
        self::assertEquals(
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
        $tracker = $this->buildTracker(7);
        $field1  = $this->createMock(FileUploadData::class);
        $field1->method('getUploadUrl')->willReturn('/api/v1/tracker_fields/1002/files');
        $field1->method('getUploadFileName')->willReturn('artifact[1002][][tus-uploaded-id]');
        $field1->method('getUploadMaxSize')->willReturn(1024);

        $this->first_usable_field_data_getter->shouldReceive('getFileUploadData')->andReturn($field1);

        $textarea = $this->provider->getTextarea(
            $tracker,
            null,
            UserTestBuilder::aUser()->build(),
            'input-id',
            'input-name',
            8,
            80,
            'input-value',
            false,
            false
        );
        self::assertEquals(
            <<<EOL
<textarea
        id="input-id"
        class="user-mention"
        wrap="soft"
        rows="8"
        cols="80"
        name="input-name"
        maxlength="65535"
        \n            data-upload-url="/api/v1/tracker_fields/1002/files"
            data-upload-field-name="artifact[1002][][tus-uploaded-id]"
            data-upload-max-size="1024"
            data-project-id="7"
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
