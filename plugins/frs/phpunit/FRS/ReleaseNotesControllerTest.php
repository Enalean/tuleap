<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace TuleapCodingStandard\Tuleap\FRS;

use FRSRelease;
use FRSReleaseFactory;
use HTTPRequest;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TemplateRenderer;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementInterface;
use Tuleap\FRS\Link\Retriever;
use Tuleap\FRS\ReleaseNotesController;
use Tuleap\FRS\ReleasePresenter;
use Tuleap\FRS\REST\v1\ReleasePermissionsForGroupsBuilder;
use Tuleap\FRS\UploadedLinksRetriever;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\NotFoundException;

final class ReleaseNotesControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /** @var ReleaseNotesController */
    private $release_notes_controller;
    /** @var FRSReleaseFactory */
    private $release_factory;
    /** @var LicenseAgreementFactory */
    private $license_agreement_factory;
    /** @var ReleasePermissionsForGroupsBuilder */
    private $permissions_for_groups_builder;
    /** @var Retriever */
    private $link_retriever;
    /** @var UploadedLinksRetriever */
    private $uploaded_links_retriever;
    /** @var FRSPermissionManager */
    private $permission_manager;
    /** @var TemplateRenderer */
    private $renderer;
    /** @var IncludeAssets */
    private $script_assets;
    /** @var IncludeAssets */
    private $theme_assets;

    protected function setUp(): void
    {
        $this->release_factory                = M::mock(FRSReleaseFactory::class);
        $this->license_agreement_factory      = M::mock(LicenseAgreementFactory::class);
        $this->permissions_for_groups_builder = M::mock(ReleasePermissionsForGroupsBuilder::class);
        $this->link_retriever                 = M::mock(Retriever::class);
        $this->uploaded_links_retriever       = M::mock(UploadedLinksRetriever::class);
        $this->permission_manager             = M::mock(FRSPermissionManager::class);
        $this->renderer                       = M::mock(TemplateRenderer::class);
        $this->script_assets                  = M::mock(IncludeAssets::class);
        $this->theme_assets                   = M::mock(IncludeAssets::class);

        $this->release_notes_controller = new ReleaseNotesController(
            $this->release_factory,
            $this->license_agreement_factory,
            $this->permissions_for_groups_builder,
            $this->link_retriever,
            $this->uploaded_links_retriever,
            $this->permission_manager,
            $this->renderer,
            $this->script_assets,
            $this->theme_assets
        );
    }

    public function testProcessThrowsNotFoundWhenReleaseCantBeFound(): void
    {
        $variables = ['release_id' => 124];
        $layout    = M::mock(BaseLayout::class);
        $request   = M::mock(HTTPRequest::class);
        $this->release_factory->shouldReceive('getFRSReleaseFromDb')
            ->once()
            ->with(124)
            ->andReturnNull();

        $this->expectException(NotFoundException::class);
        $this->release_notes_controller->process($request, $layout, $variables);
    }

    public function testProcessRendersAReleasePresenter(): void
    {
        $variables    = ['release_id' => 124];
        $layout       = M::mock(BaseLayout::class);
        $current_user = M::mock(\PFUser::class)->shouldReceive('getShortLocale')
            ->andReturn('en')
            ->getMock();
        $request      = M::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($current_user);
        $project = M::spy(\Project::class)->shouldReceive('getID')
            ->andReturn(101)
            ->getMock();
        $package = M::spy(\FRSPackage::class);
        $release = M::spy(\FRSRelease::class);
        $release->shouldReceive('getProject')->andReturn($project);
        $release->shouldReceive('getPackage')->andReturn($package);
        $release->shouldReceive('getStatusID')
            ->once()
            ->andReturn(FRSRelease::STATUS_ACTIVE);
        $release->shouldReceive('getFiles')->andReturn([]);

        $this->release_factory->shouldReceive('getFRSReleaseFromDb')
            ->once()
            ->with(124)
            ->andReturn($release);
        $license_agreement = M::mock(LicenseAgreementInterface::class)
            ->shouldReceive('getAsJson')
            ->getMock();
        $this->license_agreement_factory->shouldReceive('getLicenseAgreementForPackage')
            ->once()
            ->with($package)
            ->andReturn($license_agreement);
        $this->link_retriever->shouldReceive('getLinkedArtifactId');
        $this->uploaded_links_retriever->shouldReceive('getLinksForRelease')->andReturn([]);
        $this->permissions_for_groups_builder->shouldReceive('getRepresentation');

        // assets
        $script_url = 'https://example.com/tuleap-frs.js';
        $this->script_assets->shouldReceive('getFileURL')
            ->once()
            ->with('tuleap-frs.js')
            ->andReturn($script_url);
        $layout->shouldReceive('includeFooterJavascriptFile')
            ->once()
            ->with($script_url);
        $layout->shouldReceive('addCssAsset')
            ->once()
            ->with(M::type(CssAsset::class));
        // toolbar
        $this->permission_manager->shouldReceive('isAdmin')
            ->once()
            ->with($project, $current_user)
            ->andReturnTrue();
        $layout->shouldReceive('addToolbarItem')
            ->twice();
        // layout
        $layout->shouldReceive('header')->once();
        $this->renderer->shouldReceive('renderToPage')
            ->once()
            ->with('release', M::type(ReleasePresenter::class));
        $layout->shouldReceive('footer')
            ->once()
            ->with([]);

        $this->release_notes_controller->process($request, $layout, $variables);
    }
}
