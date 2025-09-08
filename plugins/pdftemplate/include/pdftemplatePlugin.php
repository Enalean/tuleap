<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Log\LoggerInterface;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Export\Pdf\Template\GetPdfTemplatesEvent;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\PdfTemplate\Admin\AdministrationCSRFTokenProvider;
use Tuleap\PdfTemplate\Admin\AdminPageRenderer;
use Tuleap\PdfTemplate\Admin\BuildUpdateTemplateRequestMiddleware;
use Tuleap\PdfTemplate\Admin\CheckCSRFMiddleware;
use Tuleap\PdfTemplate\Admin\CreatePdfTemplateController;
use Tuleap\PdfTemplate\Admin\DeletePdfTemplateController;
use Tuleap\PdfTemplate\Admin\DisplayPdfTemplateCreationFormController;
use Tuleap\PdfTemplate\Admin\DisplayPdfTemplateDuplicateFormController;
use Tuleap\PdfTemplate\Admin\DisplayPdfTemplateUpdateFormController;
use Tuleap\PdfTemplate\Admin\Image\DeleteImageController;
use Tuleap\PdfTemplate\Admin\Image\IndexImagesController;
use Tuleap\PdfTemplate\Admin\Image\UploadImageController;
use Tuleap\PdfTemplate\Admin\Image\UsageDetector;
use Tuleap\PdfTemplate\Admin\IndexPdfTemplateController;
use Tuleap\PdfTemplate\Admin\ManagePdfTemplates;
use Tuleap\PdfTemplate\Admin\RejectNonNonPdfTemplateManagerMiddleware;
use Tuleap\PdfTemplate\Admin\UpdatePdfTemplateController;
use Tuleap\PdfTemplate\Admin\UserCanManageTemplatesChecker;
use Tuleap\PdfTemplate\Image\Identifier\PdfTemplateImageIdentifierFactory;
use Tuleap\PdfTemplate\Image\PdfTemplateImageDao;
use Tuleap\PdfTemplate\Image\PdfTemplateImageDisplayController;
use Tuleap\PdfTemplate\Image\PdfTemplateImageHrefBuilder;
use Tuleap\PdfTemplate\Image\PdfTemplateImageStorage;
use Tuleap\PdfTemplate\Image\RejectAnonymousMiddleware;
use Tuleap\PdfTemplate\Image\RetrieveImageMiddleware;
use Tuleap\PdfTemplate\PdfTemplateDao;
use Tuleap\PdfTemplate\PdfTemplateForUserRetriever;
use Tuleap\PdfTemplate\Variable\VariableMisusageCollector;
use Tuleap\PdfTemplate\Variable\VariableMisusageInTemplateDetector;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Plugin\ListeningToEventName;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use Tuleap\User\User_ForgeUserGroupPermissionsFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PdfTemplatePlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-pdftemplate', __DIR__ . '/../site-content');
    }

    #[\Override]
    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-pdftemplate', 'PDF Template'),
                    dgettext('tuleap-pdftemplate', 'Allow to define templates for PDF export'),
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    #[ListeningToEventClass]
    public function getPdfTemplatesEvent(GetPdfTemplatesEvent $event): void
    {
        (new PdfTemplateForUserRetriever($this->getPdfTemplateDao()))
            ->injectTemplates($event);
    }

    #[ListeningToEventClass]
    public function siteAdministrationAddOption(SiteAdministrationAddOption $event): void
    {
        $event->addPluginOption(
            SiteAdministrationPluginOption::withShortname(
                dgettext('tuleap-pdftemplate', 'PDF Template'),
                IndexPdfTemplateController::ROUTE,
                'pdftemplate',
            )
        );
    }

    #[ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->get(
            IndexImagesController::ROUTE,
            $this->getRouteHandler('indexAdminImagesController'),
        );
        $event->getRouteCollector()->get(
            IndexPdfTemplateController::ROUTE,
            $this->getRouteHandler('indexAdminController'),
        );
        $event->getRouteCollector()->get(
            DisplayPdfTemplateCreationFormController::ROUTE,
            $this->getRouteHandler('displayCreateAdminController'),
        );
        $event->getRouteCollector()->post(
            DisplayPdfTemplateCreationFormController::ROUTE,
            $this->getRouteHandler('createAdminController'),
        );
        $event->getRouteCollector()->post(
            DeletePdfTemplateController::ROUTE,
            $this->getRouteHandler('deleteAdminController'),
        );
        $event->getRouteCollector()->get(
            DisplayPdfTemplateUpdateFormController::ROUTE . '/{id:[A-Fa-f0-9-]+}',
            $this->getRouteHandler('displayUpdateAdminController'),
        );
        $event->getRouteCollector()->post(
            DisplayPdfTemplateUpdateFormController::ROUTE . '/{id:[A-Fa-f0-9-]+}',
            $this->getRouteHandler('updateAdminController'),
        );
        $event->getRouteCollector()->get(
            DisplayPdfTemplateDuplicateFormController::ROUTE . '/{id:[A-Fa-f0-9-]+}',
            $this->getRouteHandler('displayDuplicateAdminController'),
        );
        $event->getRouteCollector()->post(
            UploadImageController::ROUTE,
            $this->getRouteHandler('uploadImageController'),
        );
        $event->getRouteCollector()->post(
            DeleteImageController::ROUTE . '/{id:[A-Fa-f0-9-]+}',
            $this->getRouteHandler('deleteImageController'),
        );
        $event->getRouteCollector()->get(
            PdfTemplateImageDisplayController::ROUTE . '/{id:[A-Fa-f0-9-]+}',
            $this->getRouteHandler('displayImageController'),
        );
    }

    public function displayImageController(): DispatchableWithRequest
    {
        return new PdfTemplateImageDisplayController(
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new PdfTemplateImageStorage(),
            new SapiEmitter(),
            new RejectAnonymousMiddleware(UserManager::instance()),
            new RetrieveImageMiddleware($this->getImageIdentifierFactory(), $this->getImageDao()),
        );
    }

    public function uploadImageController(): DispatchableWithRequest
    {
        return new UploadImageController(
            new RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new FeedbackSerializer(new FeedbackDao()),
            ),
            $this->getImageDao(),
            $this->getImageIdentifierFactory(),
            new PdfTemplateImageStorage(),
            $this->getLogger(),
            new SapiEmitter(),
            new RejectNonNonPdfTemplateManagerMiddleware(
                UserManager::instance(),
                $this->getUserCanManageTemplatesChecker(),
            ),
            new CheckCSRFMiddleware(new AdministrationCSRFTokenProvider()),
        );
    }

    public function deleteImageController(): DispatchableWithRequest
    {
        return new DeleteImageController(
            new RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new FeedbackSerializer(new FeedbackDao()),
            ),
            new PdfTemplateImageStorage(),
            $this->getImageDao(),
            $this->getLogger(),
            new SapiEmitter(),
            new RejectNonNonPdfTemplateManagerMiddleware(
                UserManager::instance(),
                $this->getUserCanManageTemplatesChecker(),
            ),
            new CheckCSRFMiddleware(new AdministrationCSRFTokenProvider()),
            new RetrieveImageMiddleware($this->getImageIdentifierFactory(), $this->getImageDao()),
        );
    }

    public function indexAdminImagesController(): DispatchableWithRequest
    {
        return new IndexImagesController(
            new AdminPageRenderer(),
            $this->getUserCanManageTemplatesChecker(),
            new AdministrationCSRFTokenProvider(),
            $this->getImageDao(),
            new UsageDetector(
                $this->getPdfTemplateDao(),
                new PdfTemplateImageHrefBuilder(),
            ),
            new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
        );
    }

    public function indexAdminController(): DispatchableWithRequest
    {
        return new IndexPdfTemplateController(
            new AdminPageRenderer(),
            $this->getUserCanManageTemplatesChecker(),
            $this->getPdfTemplateDao(),
            new AdministrationCSRFTokenProvider(),
            new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
        );
    }

    public function displayCreateAdminController(): DispatchableWithRequest
    {
        return new DisplayPdfTemplateCreationFormController(
            new AdminPageRenderer(),
            $this->getUserCanManageTemplatesChecker(),
            new AdministrationCSRFTokenProvider(),
            $this->getImageDao(),
            new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
        );
    }

    public function displayUpdateAdminController(): DispatchableWithRequest
    {
        return new DisplayPdfTemplateUpdateFormController(
            new AdminPageRenderer(),
            $this->getUserCanManageTemplatesChecker(),
            $this->getPdfTemplateIdentifierFactory(),
            $this->getPdfTemplateDao(),
            new AdministrationCSRFTokenProvider(),
            $this->getImageDao(),
            new VariableMisusageInTemplateDetector(new VariableMisusageCollector()),
            new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
        );
    }

    public function displayDuplicateAdminController(): DispatchableWithRequest
    {
        return new DisplayPdfTemplateDuplicateFormController(
            new AdminPageRenderer(),
            $this->getUserCanManageTemplatesChecker(),
            $this->getPdfTemplateIdentifierFactory(),
            $this->getPdfTemplateDao(),
            new AdministrationCSRFTokenProvider(),
            $this->getImageDao(),
            new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
        );
    }

    public function createAdminController(): DispatchableWithRequest
    {
        return new CreatePdfTemplateController(
            new RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new FeedbackSerializer(new FeedbackDao()),
            ),
            $this->getLogger(),
            $this->getPdfTemplateDao(),
            new VariableMisusageInTemplateDetector(new VariableMisusageCollector()),
            new SapiEmitter(),
            new RejectNonNonPdfTemplateManagerMiddleware(
                UserManager::instance(),
                $this->getUserCanManageTemplatesChecker(),
            ),
            new CheckCSRFMiddleware(new AdministrationCSRFTokenProvider()),
        );
    }

    public function updateAdminController(): DispatchableWithRequest
    {
        $redirect_with_feedback_factory = new RedirectWithFeedbackFactory(
            HTTPFactoryBuilder::responseFactory(),
            new FeedbackSerializer(new FeedbackDao()),
        );

        return new UpdatePdfTemplateController(
            $redirect_with_feedback_factory,
            $this->getLogger(),
            $this->getPdfTemplateDao(),
            new VariableMisusageInTemplateDetector(new VariableMisusageCollector()),
            new SapiEmitter(),
            new RejectNonNonPdfTemplateManagerMiddleware(
                UserManager::instance(),
                $this->getUserCanManageTemplatesChecker(),
            ),
            new BuildUpdateTemplateRequestMiddleware(
                $redirect_with_feedback_factory,
                $this->getPdfTemplateIdentifierFactory(),
                $this->getPdfTemplateDao(),
                new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
            ),
            new CheckCSRFMiddleware(new AdministrationCSRFTokenProvider()),
        );
    }

    public function deleteAdminController(): DispatchableWithRequest
    {
        return new DeletePdfTemplateController(
            new RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new FeedbackSerializer(new FeedbackDao()),
            ),
            $this->getLogger(),
            $this->getPdfTemplateDao(),
            $this->getPdfTemplateIdentifierFactory(),
            new SapiEmitter(),
            new RejectNonNonPdfTemplateManagerMiddleware(
                UserManager::instance(),
                $this->getUserCanManageTemplatesChecker(),
            ),
            new CheckCSRFMiddleware(new AdministrationCSRFTokenProvider()),
        );
    }

    #[ListeningToEventName(User_ForgeUserGroupPermissionsFactory::GET_PERMISSION_DELEGATION)]
    public function getPermissionDelegation(array $params): void
    {
        $params['plugins_permission'][ManagePdfTemplates::ID] = new ManagePdfTemplates();
    }

    private function getUserCanManageTemplatesChecker(): UserCanManageTemplatesChecker
    {
        return new UserCanManageTemplatesChecker(
            new User_ForgeUserGroupPermissionsManager(
                new User_ForgeUserGroupPermissionsDao(),
            ),
        );
    }

    private function getPdfTemplateDao(): PdfTemplateDao
    {
        return new PdfTemplateDao($this->getPdfTemplateIdentifierFactory(), UserManager::instance());
    }

    private function getPdfTemplateIdentifierFactory(): PdfTemplateIdentifierFactory
    {
        return new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory());
    }

    private function getImageDao(): PdfTemplateImageDao
    {
        return new PdfTemplateImageDao($this->getImageIdentifierFactory(), UserManager::instance());
    }

    private function getImageIdentifierFactory(): PdfTemplateImageIdentifierFactory
    {
        return new PdfTemplateImageIdentifierFactory(new DatabaseUUIDV7Factory());
    }

    private function getLogger(): LoggerInterface
    {
        return BackendLogger::getDefaultLogger('pdftemplate_syslog');
    }
}
