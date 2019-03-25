<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Document;

use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DocumentUsageRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DocumentUsageRetriever
     */
    public $retriever;
    /**
     * @var \Docman_MetadataFactory|\Mockery\MockInterface
     */
    private $metadata_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metadata_factory = \Mockery::mock(\Docman_MetadataFactory::class);
        $this->retriever        = new DocumentUsageRetriever($this->metadata_factory);

        ForgeConfig::store();
        ForgeConfig::set('sys_project_blacklist_which_uses_legacy_ui_by_default', []);
    }

    protected function tearDown(): void
    {
        ForgeConfig::restore();

        parent::tearDown();
    }

    public function testItShouldUseLegacyUIIfUserHasLegacyUIPreferences(): void
    {
        $user    = \Mockery::mock(\PFUser::class);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(102);
        $user->shouldReceive('getPreference')->with('plugin_docman_display_legacy_ui_102')->andReturn(true);

        $this->assertFalse($this->retriever->shouldUseDocument($user, $project));
    }

    public function testItShouldUseNewUIIfUserHasNewUIPreferences(): void
    {
        $user    = \Mockery::mock(\PFUser::class);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(102);
        $user->shouldReceive('getPreference')->with('plugin_docman_display_legacy_ui_102')->andReturn(false);
        $user->shouldReceive('getPreference')->with('plugin_docman_display_new_ui_102')->andReturn(true);

        $this->metadata_factory->shouldReceive('getRealMetadataList')->never();

        $this->assertTrue($this->retriever->shouldUseDocument($user, $project));
    }

    public function testItShouldUseOldUIIfUserIsUndefined(): void
    {
        $user    = null;
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(102);

        $this->metadata_factory->shouldReceive('getRealMetadataList')->never();

        $this->assertFalse($this->retriever->shouldUseDocument($user, $project));
    }

    public function testItShouldUseOldUIWhenUserDoesNotHavePreferencesAndWhenProjectUsesRequiredMetadata(): void
    {
        $user    = \Mockery::mock(\PFUser::class);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(102);
        $user->shouldReceive('getPreference')->with('plugin_docman_display_legacy_ui_102')->andReturn(false);
        $user->shouldReceive('getPreference')->with('plugin_docman_display_new_ui_102')->andReturn(false);

        $metadata = \Mockery::mock(\Docman_Metadata::class);
        $metadata->shouldReceive('isEmptyAllowed')->andReturn(false);

        $this->metadata_factory->shouldReceive('getRealMetadataList')->andReturn([$metadata]);
        $this->assertFalse($this->retriever->shouldUseDocument($user, $project));
    }

    public function testItShouldUseLegacyUIWhenProjectIsInBlackListForDefaultPreferences(): void
    {
        $user    = \Mockery::mock(\PFUser::class);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(102);
        $user->shouldReceive('getPreference')->with('plugin_docman_display_legacy_ui_102')->andReturn(false);
        $user->shouldReceive('getPreference')->with('plugin_docman_display_new_ui_102')->andReturn(false);

        $metadata = \Mockery::mock(\Docman_Metadata::class);
        $metadata->shouldReceive('isEmptyAllowed')->andReturn(true);

        ForgeConfig::set('sys_project_blacklist_which_uses_legacy_ui_by_default', [102]);

        $this->metadata_factory->shouldReceive('getRealMetadataList')->andReturn([$metadata]);
        $this->assertFalse($this->retriever->shouldUseDocument($user, $project));
    }

    public function testItShouldUseNewUIWhenUserDoesNotHavePreferencesAndWhenProjectHasNoRequiredMetadata(): void
    {
        $user    = \Mockery::mock(\PFUser::class);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(102);
        $user->shouldReceive('getPreference')->with('plugin_docman_display_legacy_ui_102')->andReturn(false);
        $user->shouldReceive('getPreference')->with('plugin_docman_display_new_ui_102')->andReturn(false);

        $metadata = \Mockery::mock(\Docman_Metadata::class);
        $metadata->shouldReceive('isEmptyAllowed')->andReturn(true);

        $this->metadata_factory->shouldReceive('getRealMetadataList')->andReturn([$metadata]);
        $this->assertTrue($this->retriever->shouldUseDocument($user, $project));
    }
}
