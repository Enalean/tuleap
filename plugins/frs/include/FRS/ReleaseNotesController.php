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

use Codendi_HTMLPurifier;
use FRSReleaseFactory;
use PermissionsManager;
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
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Semantic\Status\CachedSemanticStatusRetriever;
use Tuleap\Tracker\Semantic\Status\RetrieveSemanticStatus;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use UGroupManager;
use UserManager;

readonly class ReleaseNotesController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private FRSReleaseFactory $release_factory,
        private LicenseAgreementFactory $license_agreement_factory,
        private ReleasePermissionsForGroupsBuilder $permissions_for_groups_builder,
        private Retriever $link_retriever,
        private UploadedLinksRetriever $uploaded_links_retriever,
        private ContentInterpretor $interpreter,
        private TemplateRenderer $renderer,
        private IncludeAssets $assets,
        private ProvideUserAvatarUrl $provide_user_avatar_url,
        private RetrieveSemanticStatus $semantic_status_retriever,
    ) {
    }

    public static function buildSelf(): self
    {
        $frs_permission_manager = FRSPermissionManager::build();
        return new self(
            new FRSReleaseFactory(),
            new LicenseAgreementFactory(new LicenseAgreementDao()),
            new ReleasePermissionsForGroupsBuilder(
                $frs_permission_manager,
                PermissionsManager::instance(),
                new UGroupManager()
            ),
            new Retriever(new Dao()),
            new UploadedLinksRetriever(new UploadedLinksDao(), UserManager::instance()),
            CommonMarkInterpreter::build(
                Codendi_HTMLPurifier::instance()
            ),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates'),
            new IncludeAssets(
                __DIR__ . '/../../frontend-assets',
                '/assets/frs'
            ),
            new UserAvatarUrlProvider(new AvatarHashDao()),
            CachedSemanticStatusRetriever::instance(),
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function process(\Tuleap\HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $release = $this->release_factory->getFRSReleaseFromDb($variables['release_id']);
        $package = $release?->getPackage();

        $user = $request->getCurrentUser();

        if (
            $release === null || $package === null ||
            ! $this->release_factory->userCanRead($release, (string) $user->getId())
        ) {
            throw new NotFoundException(dgettext('tuleap-frs', 'Release not found.'));
        }

        $representation = new ReleaseRepresentation(
            $release,
            $this->link_retriever,
            $user,
            $this->uploaded_links_retriever,
            $this->permissions_for_groups_builder,
            $this->provide_user_avatar_url,
            $this->semantic_status_retriever,
        );

        $license_agreement = $this->license_agreement_factory->getLicenseAgreementForPackage($release->getPackage());
        $presenter         = new ReleasePresenter(
            $representation,
            $user->getShortLocale(),
            $license_agreement,
            $this->interpreter
        );

        $layout->includeFooterJavascriptFile($this->assets->getFileURL('tuleap-frs.js'));
        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->assets, 'frs-style'));

        $project = $release->getProject();
        $service = $project->getService(Service::FILE);

        if (! ($service instanceof \ServiceFile)) {
            exit_error(
                $GLOBALS['Language']->getText(
                    'project_service',
                    'service_not_used',
                    $GLOBALS['Language']->getText('project_admin_editservice', 'service_file_lbl_key')
                )
            );
        }

        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb(new BreadCrumb(
            new BreadCrumbLink($release->getPackage()->getName(), '/file/' . urlencode((string) $release->getProject()->getID()) . '/package/' . urlencode((string) $release->getPackage()->getPackageID())),
        ));
        $service->displayFRSHeader($project, $release->getName(), $breadcrumbs);
        $this->renderer->renderToPage($presenter->getTemplateName(), $presenter);
        $service->displayFooter();
    }
}
