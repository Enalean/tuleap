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

namespace Tuleap\FRS;

use FRSReleaseFactory;
use HTTPRequest;
use PermissionsManager;
use PFUser;
use Project;
use Service;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDao;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\Link\Dao;
use Tuleap\FRS\Link\Retriever;
use Tuleap\FRS\REST\v1\ReleasePermissionsForGroupsBuilder;
use Tuleap\FRS\REST\v1\ReleaseRepresentation;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use UGroupManager;
use UserManager;

class ReleaseNotesController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
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
    private $assets;

    public function __construct(
        FRSReleaseFactory $release_factory,
        LicenseAgreementFactory $license_agreement_factory,
        ReleasePermissionsForGroupsBuilder $permissions_for_groups_builder,
        Retriever $link_retriever,
        UploadedLinksRetriever $uploaded_links_retriever,
        FRSPermissionManager $permission_manager,
        TemplateRenderer $renderer,
        IncludeAssets $assets
    ) {
        $this->release_factory                = $release_factory;
        $this->license_agreement_factory      = $license_agreement_factory;
        $this->permissions_for_groups_builder = $permissions_for_groups_builder;
        $this->link_retriever                 = $link_retriever;
        $this->uploaded_links_retriever       = $uploaded_links_retriever;
        $this->permission_manager             = $permission_manager;
        $this->renderer                       = $renderer;
        $this->assets                         = $assets;
    }

    public static function buildSelf(): self
    {
        return new self(
            new FRSReleaseFactory(),
            new LicenseAgreementFactory(new LicenseAgreementDao()),
            new ReleasePermissionsForGroupsBuilder(
                FRSPermissionManager::build(),
                PermissionsManager::instance(),
                new UGroupManager()
            ),
            new Retriever(new Dao()),
            new UploadedLinksRetriever(new UploadedLinksDao(), UserManager::instance()),
            FRSPermissionManager::build(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates'),
            new IncludeAssets(
                __DIR__ . '/../../../../src/www/assets/frs',
                '/assets/frs'
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $release = $this->release_factory->getFRSReleaseFromDb($variables['release_id']);
        if ($release === null) {
            throw new NotFoundException(dgettext('tuleap-frs', "Release not found."));
        }
        $user = $request->getCurrentUser();

        $representation = new ReleaseRepresentation();
        $representation->build(
            $release,
            $this->link_retriever,
            $user,
            $this->uploaded_links_retriever,
            $this->permissions_for_groups_builder
        );

        $license_agreement = $this->license_agreement_factory->getLicenseAgreementForPackage($release->getPackage());
        $presenter         = new ReleasePresenter(
            $representation,
            $user->getShortLocale(),
            $license_agreement
        );

        $layout->includeFooterJavascriptFile($this->assets->getFileURL('tuleap-frs.js'));
        $layout->addCssAsset(new CssAsset($this->assets, 'frs'));

        $translated_title = sprintf(dgettext('tuleap-frs', 'Release %s - Release Notes'), $release->getName());
        $project          = $release->getProject();
        $this->buildLegacyToolbar($project, $user, $layout);
        $layout->header(
            [
                'title'        => $translated_title,
                'group'        => $project->getID(),
                'toptab'       => Service::FILE,
                'main_classes' => []
            ]
        );
        $this->renderer->renderToPage($presenter->getTemplateName(), $presenter);
        $layout->footer([]);
    }

    private function buildLegacyToolbar(Project $project, PFUser $user, BaseLayout $layout): void
    {
        if ($this->permission_manager->isAdmin($project, $user)) {
            $admin_title        = $GLOBALS['Language']->getText('file_file_utils', 'toolbar_admin');
            $admin_url          = '/file/admin/?' . http_build_query(
                ['group_id' => $project->getID(), 'action' => 'edit-permissions']
            );
            $admin_toolbar_item = '<a href="' . $admin_url . '">' . $admin_title . '</a>';
            $layout->addToolbarItem($admin_toolbar_item);
        }
        $help_title        = $GLOBALS['Language']->getText('file_file_utils', 'toolbar_help');
        $help_url          = "javascript:help_window('/doc/" . $user->getShortLocale(
        ) . "/user-guide/documents-and-files/frs.html')";
        $help_toolbar_item = '<a href="' . $help_url . '">' . $help_title . '</a>';
        $layout->addToolbarItem($help_toolbar_item);
    }
}
