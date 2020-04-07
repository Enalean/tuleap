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
use PFUser;
use PHPUnit\Framework\TestCase;
use TemplateRendererFactory;
use Tracker;
use Tracker_Artifact;
use Tracker_FormElement_Field_File;
use Tracker_FormElementFactory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Templating\TemplateCache;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;

class RichTextareaProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var MockInterface|Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var MockInterface|TemplateRendererFactory
     */
    private $template_renderer_factory;
    /**
     * @var MockInterface|FrozenFieldDetector
     */
    private $frozen_field_detector;
    /**
     * @var RichTextareaProvider
     */
    private $provider;
    /**
     * @var MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var MockInterface|PFUser
     */
    private $user;
    /**
     * @var MockInterface|Tracker_Artifact
     */
    private $artifact;

    public function setUp(): void
    {
        $this->tracker  = Mockery::mock(Tracker::class);
        $this->user     = Mockery::mock(PFUser::class);
        $this->artifact = Mockery::mock(Tracker_Artifact::class);

        $this->form_element_factory  = Mockery::mock(Tracker_FormElementFactory::class);
        $this->frozen_field_detector = Mockery::mock(FrozenFieldDetector::class);

        $template_cache = Mockery::mock(TemplateCache::class);
        $template_cache->shouldReceive('getPath')->andReturn(null);

        $this->template_renderer_factory = new TemplateRendererFactory($template_cache);

        $this->provider = new RichTextareaProvider(
            $this->template_renderer_factory,
            new UploadDataAttributesForRichTextEditorBuilder(
                $this->form_element_factory,
                $this->frozen_field_detector
            )
        );

        ForgeConfig::set('sys_max_size_upload', 1024);
    }

    public function testItPassesArgumentsAsPresenterToTheTemplate(): void
    {
        $this->form_element_factory->shouldReceive('getUsedFileFields')->andReturn([]);

        $textarea = $this->provider->getTextarea(
            $this->tracker,
            null,
            $this->user,
            'input-id',
            'input-name',
            8,
            80,
            'input-value',
            true,
            [
                [
                    'name'  => 'artifact-id',
                    'value' => 123
                ]
            ]
        );
        $this->assertEquals(
            <<<EOL
<textarea
        id="input-id"
        class="user-mention"
        wrap="soft"
        rows="8"
        cols="80"
        name="input-name"
        required
            data-artifact-id="123"
>input-value</textarea>
<div class="muted tracker-richtexteditor-help" id="input-id-help"></div>

EOL
            ,
            $textarea
        );
    }

    public function testItIncludesUploadOptionsIfAFileFieldIsUpdatable(): void
    {
        $field1 = Mockery::mock(Tracker_FormElement_Field_File::class);
        $field1->shouldReceive('userCanUpdate')->with($this->user)->andReturn(false);
        $field2 = Mockery::mock(Tracker_FormElement_Field_File::class);
        $field2->shouldReceive('userCanUpdate')->with($this->user)->andReturn(true);
        $field2->shouldReceive('getId')->andReturn(1002);

        $this->form_element_factory->shouldReceive('getUsedFileFields')->andReturn([$field1, $field2]);

        $textarea = $this->provider->getTextarea(
            $this->tracker,
            null,
            $this->user,
            'input-id',
            'input-name',
            8,
            80,
            'input-value',
            false,
            [
                [
                    'name'  => 'artifact-id',
                    'value' => 123
                ]
            ]
        );
        $this->assertEquals(
            <<<EOL
<textarea
        id="input-id"
        class="user-mention"
        wrap="soft"
        rows="8"
        cols="80"
        name="input-name"
        \n            data-upload-url="/api/v1/tracker_fields/1002/files"
            data-upload-field-name="artifact[1002][][tus-uploaded-id]"
            data-upload-max-size="1024"
            data-artifact-id="123"
            data-help-id="input-id-help"
>input-value</textarea>
<div class="muted tracker-richtexteditor-help" id="input-id-help"></div>

EOL
            ,
            $textarea
        );
    }

    public function testItDoesNotIncludeUploadOptionsIfFieldIsFrozen(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_File::class);
        $field->shouldReceive('userCanUpdate')->with($this->user)->andReturn(true);
        $field->shouldReceive('getId')->andReturn(1002);

        $this->form_element_factory->shouldReceive('getUsedFileFields')->andReturn([$field]);
        $this->frozen_field_detector->shouldReceive('isFieldFrozen')->with($this->artifact, $field)->andReturn(true);

        $textarea = $this->provider->getTextarea(
            $this->tracker,
            $this->artifact,
            $this->user,
            'input-id',
            'input-name',
            8,
            80,
            'input-value',
            false,
            [
                [
                    'name'  => 'artifact-id',
                    'value' => 123
                ]
            ]
        );
        $this->assertEquals(
            <<<EOL
<textarea
        id="input-id"
        class="user-mention"
        wrap="soft"
        rows="8"
        cols="80"
        name="input-name"
        \n            data-artifact-id="123"
>input-value</textarea>
<div class="muted tracker-richtexteditor-help" id="input-id-help"></div>

EOL
            ,
            $textarea
        );
    }

    public function testItIncludesUploadOptionsIfAFileFieldIsUpdatableAndNotFrozen(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_File::class);
        $field->shouldReceive('userCanUpdate')->with($this->user)->andReturn(true);
        $field->shouldReceive('getId')->andReturn(1002);

        $this->form_element_factory->shouldReceive('getUsedFileFields')->andReturn([$field]);
        $this->frozen_field_detector->shouldReceive('isFieldFrozen')->with($this->artifact, $field)->andReturn(false);

        $textarea = $this->provider->getTextarea(
            $this->tracker,
            $this->artifact,
            $this->user,
            'input-id',
            'input-name',
            8,
            80,
            'input-value',
            false,
            [
                [
                    'name'  => 'artifact-id',
                    'value' => 123
                ]
            ]
        );
        $this->assertEquals(
            <<<EOL
<textarea
        id="input-id"
        class="user-mention"
        wrap="soft"
        rows="8"
        cols="80"
        name="input-name"
        \n            data-upload-url="/api/v1/tracker_fields/1002/files"
            data-upload-field-name="artifact[1002][][tus-uploaded-id]"
            data-upload-max-size="1024"
            data-artifact-id="123"
            data-help-id="input-id-help"
>input-value</textarea>
<div class="muted tracker-richtexteditor-help" id="input-id-help"></div>

EOL
            ,
            $textarea
        );
    }
}
