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
use PHPUnit\Framework\MockObject\Stub;
use TemplateRenderer;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementInterface;
use Tuleap\FRS\Link\Retriever;
use Tuleap\FRS\PackagePermissionManager;
use Tuleap\FRS\ReleaseNotesController;
use Tuleap\FRS\ReleasePresenter;
use Tuleap\FRS\REST\v1\ReleasePermissionsForGroupsBuilder;
use Tuleap\FRS\UploadedLinksRetriever;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Tracker\Test\Stub\Semantic\Status\RetrieveSemanticStatusStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReleaseNotesControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private ReleaseNotesController $release_notes_controller;
    /**
     * @var FRSReleaseFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $release_factory;
    /**
     * @var LicenseAgreementFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $license_agreement_factory;
    /**
     * @var ReleasePermissionsForGroupsBuilder&\PHPUnit\Framework\MockObject\MockObject
     */
    private $permissions_for_groups_builder;
    /**
     * @var Retriever&\PHPUnit\Framework\MockObject\MockObject
     */
    private $link_retriever;
    /**
     * @var UploadedLinksRetriever&\PHPUnit\Framework\MockObject\MockObject
     */
    private $uploaded_links_retriever;
    /**
     * @var FRSPermissionManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $permission_manager;
    /**
     * @var TemplateRenderer&\PHPUnit\Framework\MockObject\MockObject
     */
    private $renderer;
    /**
     * @var IncludeAssets&\PHPUnit\Framework\MockObject\MockObject
     */
    private $script_assets;
    private PackagePermissionManager&Stub $package_permission_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->release_factory                = $this->createMock(FRSReleaseFactory::class);
        $this->license_agreement_factory      = $this->createMock(LicenseAgreementFactory::class);
        $this->permissions_for_groups_builder = $this->createMock(ReleasePermissionsForGroupsBuilder::class);
        $this->link_retriever                 = $this->createMock(Retriever::class);
        $this->uploaded_links_retriever       = $this->createMock(UploadedLinksRetriever::class);
        $this->permission_manager             = $this->createMock(FRSPermissionManager::class);
        $this->package_permission_manager     = $this->createStub(PackagePermissionManager::class);
        $this->renderer                       = $this->createMock(TemplateRenderer::class);
        $this->script_assets                  = $this->createMock(IncludeAssets::class);
        $content_interpreter                  = new class implements ContentInterpretor {
            #[\Override]
            public function getInterpretedContent(string $content): string
            {
                return $content;
            }

            #[\Override]
            public function getInterpretedContentWithReferences(string $content, int $project_id): string
            {
                return $this->getInterpretedContent($content);
            }

            #[\Override]
            public function getContentStrippedOfTags(string $content): string
            {
                return $this->getInterpretedContent($content);
            }
        };

        $this->release_notes_controller = new ReleaseNotesController(
            $this->release_factory,
            $this->license_agreement_factory,
            $this->permissions_for_groups_builder,
            $this->link_retriever,
            $this->uploaded_links_retriever,
            $this->package_permission_manager,
            $this->permission_manager,
            $content_interpreter,
            $this->renderer,
            $this->script_assets,
            ProvideUserAvatarUrlStub::build(),
            RetrieveSemanticStatusStub::build(),
        );
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    public function testProcessThrowsNotFoundWhenReleaseCantBeFound(): void
    {
        $variables = ['release_id' => 124];
        $layout    = $this->createMock(BaseLayout::class);
        $request   = $this->createMock(HTTPRequest::class);
        $request->method('getCurrentUser')->willReturn(UserTestBuilder::aUser()->build());
        $this->release_factory->expects($this->once())
            ->method('getFRSReleaseFromDb')
            ->with(124)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->release_notes_controller->process($request, $layout, $variables);
    }

    public function testProcessThrowsNotFoundWhenReleaseCantBeRead(): void
    {
        $variables = ['release_id' => 124];
        $layout    = $this->createMock(BaseLayout::class);
        $request   = $this->createMock(HTTPRequest::class);
        $request->method('getCurrentUser')->willReturn(UserTestBuilder::aUser()->build());
        $release = $this->createStub(FRSRelease::class);
        $release->method('getPackage')->willReturn($this->createStub(\FRSPackage::class));
        $release->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $this->release_factory->expects($this->once())
            ->method('getFRSReleaseFromDb')
            ->with(124)
            ->willReturn($release);

        $this->package_permission_manager->method('canUserSeePackage')->willReturn(false);

        $this->expectException(NotFoundException::class);
        $this->release_notes_controller->process($request, $layout, $variables);
    }

    public function testProcessRendersAReleasePresenter(): void
    {
        $variables    = ['release_id' => 124];
        $layout       = $this->createMock(BaseLayout::class);
        $current_user = UserTestBuilder::aUser()->build();
        $request      = $this->createMock(HTTPRequest::class);
        $request->method('getCurrentUser')->willReturn($current_user);
        $project = ProjectTestBuilder::aProject()->build();
        $package = $this->createMock(\FRSPackage::class);
        $package->method('getPackageID')->willReturn(12);
        $package->method('getName')->willReturn('package01');
        $package->method('getApproveLicense');
        $release = $this->createMock(\FRSRelease::class);
        $release->method('getReleaseID')->willReturn(652);
        $release->method('getProject')->willReturn($project);
        $release->method('getNotes')->willReturn('Release notes');
        $release->expects($this->once())->method('getChanges')->willReturn('Change log');
        $release->method('getPackage')->willReturn($package);
        $release->expects($this->once())
            ->method('getStatusID')
            ->willReturn(FRSRelease::STATUS_ACTIVE);
        $release->method('getFiles')->willReturn([]);
        $release->method('getName')->willReturn('release01');

        $this->package_permission_manager->method('canUserSeePackage')->willReturn(true);

        $this->release_factory->expects($this->once())
            ->method('getFRSReleaseFromDb')
            ->with(124)
            ->willReturn($release);
        $license_agreement = $this->createMock(LicenseAgreementInterface::class);
        $license_agreement->method('getAsJson');
        $this->license_agreement_factory->expects($this->once())
            ->method('getLicenseAgreementForPackage')
            ->with($package)
            ->willReturn($license_agreement);
        $this->link_retriever->method('getLinkedArtifactId');
        $this->uploaded_links_retriever->method('getLinksForRelease')->willReturn([]);
        $this->permissions_for_groups_builder->method('getRepresentation');

        // assets
        $script_url = 'https://example.com/tuleap-frs.js';
        $this->script_assets->expects($this->once())
            ->method('getFileURL')
            ->with('tuleap-frs.js')
            ->willReturn($script_url);
        $layout->expects($this->once())
            ->method('includeFooterJavascriptFile')
            ->with($script_url);
        $layout->expects($this->once())
            ->method('addCssAsset')
            ->with(self::isInstanceOf(CssAsset::class));
        // toolbar
        $this->permission_manager->expects($this->once())
            ->method('isAdmin')
            ->with($project, $current_user)
            ->willReturn(true);
        $layout->expects($this->exactly(2))
            ->method('addToolbarItem');
        // layout
        $layout->expects($this->once())->method('header');
        $this->renderer->expects($this->once())
            ->method('renderToPage')
            ->with('release', self::isInstanceOf(ReleasePresenter::class));
        $layout->expects($this->once())
            ->method('footer')
            ->with([]);

        $this->release_notes_controller->process($request, $layout, $variables);
    }
}
