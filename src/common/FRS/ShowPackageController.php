<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use FileModuleMonitorFactory;
use FRSPackageFactory;
use FRSRelease;
use FRSReleaseFactory;
use Project;
use Tuleap\Date\RelativeDatesAssetsRetriever;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;

final readonly class ShowPackageController implements DispatchableWithBurningParrot, DispatchableWithRequest
{
    private function __construct(
        private ProjectRetriever $project_retriever,
        private FRSPermissionManager $permission_manager,
        private FRSPackageFactory $package_factory,
        private PackagePermissionManager $package_permission_manager,
        private ReleasePermissionManager $release_permission_manager,
        private FileModuleMonitorFactory $file_monitor_factory,
        private \TemplateRendererFactory $renderer_factory,
    ) {
    }

    public static function buildSelf(): self
    {
        $package_factory = FRSPackageFactory::instance();
        $release_factory = new FRSReleaseFactory();

        return new self(
            ProjectRetriever::buildSelf(),
            FRSPermissionManager::build(),
            $package_factory,
            new PackagePermissionManager($package_factory),
            new ReleasePermissionManager($release_factory),
            new FileModuleMonitorFactory(),
            \TemplateRendererFactory::build(),
        );
    }

    #[\Override]
    public function process(\Tuleap\HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $user = $request->getCurrentUser();

        $project = $this->project_retriever->getProjectFromId($variables['project_id']);
        if (! $this->permission_manager->userCanRead($project, $user)) {
            throw new NotFoundException();
        }

        $package = $this->package_factory->getFRSPackageFromDb($variables['package_id']);
        if (! $package) {
            throw new NotFoundException();
        }
        if (! $this->package_permission_manager->canUserSeePackage($user, $package)) {
            throw new NotFoundException();
        }

        $releases = array_map(
            static fn (FRSRelease $release) => ShowPackageReleasePresenter::fromRelease($release),
            array_values(
                array_filter(
                    $package->getReleases(),
                    fn (FRSRelease $release): bool => $this->release_permission_manager->canUserSeeRelease($user, $release),
                ),
            ),
        );

        $presenter = PackagePresenter::fromPackage(
            $package,
            $this->file_monitor_factory->isMonitoring($package->getPackageID(), $user, false),
            count($releases) > 0,
        );

        $layout->addJavascriptAsset(new JavascriptViteAsset(
            new IncludeViteAssets(
                __DIR__ . '/../../scripts/frs/frontend-assets',
                '/assets/core/frs',
            ),
            'src/frs.ts',
        ));
        $layout->addJavascriptAsset(RelativeDatesAssetsRetriever::getAsJavascriptAssets());

        $this->getFileService($project)->displayFRSHeader($project, $presenter->name, new BreadCrumbCollection());
        $this->renderer_factory
            ->getRenderer(__DIR__)
            ->renderToPage(
                'show-package',
                new ShowPackagePresenter(
                    $project,
                    $presenter,
                    $releases,
                    $this->permission_manager->isAdmin($project, $user),
                    new \CSRFSynchronizerToken('/file/?group_id=' . urlencode((string) $project->getID())),
                )
            );
        $layout->footer([]);
    }

    private function getFileService(Project $project): \ServiceFile
    {
        $file_service = $project->getService(\Service::FILE);
        if (! $file_service instanceof \ServiceFile) {
            throw new NotFoundException('Service is not active for this project');
        }

        return $file_service;
    }
}
