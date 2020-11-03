<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Request;

use Codendi_HTMLPurifier;
use ConfigDao;
use EventManager;
use FastRoute;
use FRSFileFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use MailManager;
use SVN_TokenHandler;
use TemplateRendererFactory;
use ThemeVariant;
use TroveCatDao;
use TroveCatFactory;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\admin\HelpDropdown\AdminReleaseNoteLinkController;
use Tuleap\admin\HelpDropdown\PostAdminReleaseNoteLinkController;
use Tuleap\Admin\ProjectCreation\ProjectCategoriesDisplayController;
use Tuleap\Admin\ProjectCreation\ProjectFieldsDisplayController;
use Tuleap\Admin\ProjectCreation\ProjectFieldsUpdateController;
use Tuleap\Admin\ProjectCreation\ProjectsFieldDescriptionUpdater;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigDisplayController;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigManager;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigUpdateController;
use Tuleap\Admin\ProjectCreation\WebhooksDisplayController;
use Tuleap\Admin\ProjectCreation\WebhooksUpdateController;
use Tuleap\Admin\ProjectCreationModerationDisplayController;
use Tuleap\Admin\ProjectCreationModerationUpdateController;
use Tuleap\Admin\ProjectTemplatesController;
use Tuleap\Admin\ProjectWidgetsConfigurationDisplayController;
use Tuleap\Admin\ProjectWidgetsConfigurationPOSTDisableController;
use Tuleap\Admin\ProjectWidgetsConfigurationPOSTEnableController;
use Tuleap\admin\SiteContentCustomisationController;
use Tuleap\Core\RSS\News\LatestNewsController;
use Tuleap\Core\RSS\Project\LatestProjectController;
use Tuleap\Core\RSS\Project\LatestProjectDao;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsDao;
use Tuleap\date\Admin\RelativeDatesDisplayController;
use Tuleap\date\Admin\RelativeDatesDisplaySaveController;
use Tuleap\date\SelectedDateDisplayPreferenceValidator;
use Tuleap\Error\PermissionDeniedPrivateProjectMailSender;
use Tuleap\Error\PermissionDeniedRestrictedMemberMailSender;
use Tuleap\Error\PlaceHolderBuilder;
use Tuleap\FRS\FRSFileDownloadController;
use Tuleap\FRS\FRSFileDownloadOldURLRedirectionController;
use Tuleap\FRS\LicenseAgreement\Admin\AddLicenseAgreementController;
use Tuleap\FRS\LicenseAgreement\Admin\EditLicenseAgreementController;
use Tuleap\FRS\LicenseAgreement\Admin\ListLicenseAgreementsController;
use Tuleap\FRS\LicenseAgreement\Admin\SaveLicenseAgreementController;
use Tuleap\FRS\LicenseAgreement\Admin\SetDefaultLicenseAgreementController;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\SessionWriteCloseMiddleware;
use Tuleap\InviteBuddy\Admin\InviteBuddyAdminController;
use Tuleap\InviteBuddy\Admin\InviteBuddyAdminUpdateController;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\SiteHomepageController;
use Tuleap\News\NewsDao;
use Tuleap\News\PermissionsPerGroup;
use Tuleap\Password\Administration\PasswordPolicyDisplayController;
use Tuleap\Password\Administration\PasswordPolicyUpdateController;
use Tuleap\Password\Configuration\PasswordConfigurationDAO;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;
use Tuleap\Password\Configuration\PasswordConfigurationSaver;
use Tuleap\Platform\Banner\BannerDao;
use Tuleap\Platform\Banner\BannerRetriever;
use Tuleap\Platform\Banner\PlatformBannerAdministrationController;
use Tuleap\Project\Admin\Categories;
use Tuleap\Project\Admin\ProjectMembers\ProjectMembersController;
use Tuleap\Project\Admin\ProjectUGroup\MemberAdditionController;
use Tuleap\Project\Admin\ProjectUGroup\MemberRemovalController;
use Tuleap\Project\Admin\ProjectUGroup\SynchronizedProjectMembership\ActivationController;
use Tuleap\Project\Banner\BannerAdministrationController;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\DescriptionFieldsDao;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Home;
use Tuleap\Project\ProjectBackground\ProjectBackgroundAdministrationController;
use Tuleap\Project\Registration\ProjectRegistrationController;
use Tuleap\Project\Registration\ProjectRegistrationPresenterBuilder;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Service\AddController;
use Tuleap\Project\Service\DeleteController;
use Tuleap\Project\Service\EditController;
use Tuleap\Project\Service\IndexController;
use Tuleap\REST\BasicAuthentication;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Tuleap\REST\TuleapRESTCORSMiddleware;
use Tuleap\REST\UserManager;
use Tuleap\Trove\TroveCatListController;
use Tuleap\User\AccessKey\AccessKeyCreationController;
use Tuleap\User\AccessKey\AccessKeyRevocationController;
use Tuleap\User\Account\AccessKeyPresenterBuilder;
use Tuleap\User\Account\Appearance\AppearancePresenterBuilder;
use Tuleap\User\Account\Appearance\LanguagePresenterBuilder;
use Tuleap\User\Account\Appearance\ThemeColorPresenterBuilder;
use Tuleap\User\Account\ChangeAvatarController;
use Tuleap\User\Account\ConfirmNewEmailController;
use Tuleap\User\Account\DisplayAccountInformationController;
use Tuleap\User\Account\DisplayAppearanceController;
use Tuleap\User\Account\DisplayEditionController;
use Tuleap\User\Account\DisplayExperimentalController;
use Tuleap\User\Account\DisplayKeysTokensController;
use Tuleap\User\Account\DisplayNotificationsController;
use Tuleap\User\Account\DisplaySecurityController;
use Tuleap\User\Account\LogoutController;
use Tuleap\User\Account\SVNTokensPresenterBuilder;
use Tuleap\User\Account\UpdateAccountInformationController;
use Tuleap\User\Account\UpdateAppearancePreferences;
use Tuleap\User\Account\UpdateEditionController;
use Tuleap\User\Account\UpdateExperimentalPreferences;
use Tuleap\User\Account\UpdateNotificationsPreferences;
use Tuleap\User\Account\UpdatePasswordController;
use Tuleap\User\Account\UpdateSessionPreferencesController;
use Tuleap\User\Account\UserAvatarSaver;
use Tuleap\User\Account\UserWellKnownChangePasswordController;
use Tuleap\User\Profile\AvatarController;
use Tuleap\User\Profile\AvatarGenerator;
use Tuleap\User\Profile\ProfileAsJSONForTooltipController;
use Tuleap\User\Profile\ProfileController;
use Tuleap\User\Profile\ProfilePresenterBuilder;
use Tuleap\User\SSHKey\SSHKeyCreateController;
use Tuleap\User\SSHKey\SSHKeyDeleteController;
use Tuleap\User\SVNToken\SVNTokenCreateController;
use Tuleap\User\SVNToken\SVNTokenRevokeController;
use Tuleap\Widget\WidgetFactory;
use URLVerification;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;

class RouteCollector
{
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    public static function getSlash()
    {
        return new SiteHomepageController(
            new \Admin_Homepage_Dao(),
            \ProjectManager::instance(),
            \UserManager::instance(),
            EventManager::instance()
        );
    }

    public static function getOrPostProjectHome()
    {
        return new Home();
    }

    public static function getAdminSiteContentCustomisation()
    {
        return new SiteContentCustomisationController(
            new AdminPageRenderer(),
            TemplateRendererFactory::build(),
            new \BaseLanguageFactory()
        );
    }

    public static function getAdminPasswordPolicy()
    {
        return new PasswordPolicyDisplayController(
            new AdminPageRenderer(),
            TemplateRendererFactory::build(),
            new PasswordConfigurationRetriever(new PasswordConfigurationDAO())
        );
    }

    public static function postAdminPasswordPolicy()
    {
        return new PasswordPolicyUpdateController(
            new PasswordConfigurationSaver(new PasswordConfigurationDAO())
        );
    }

    public static function getProjectCreationModeration()
    {
        return new ProjectCreationModerationDisplayController();
    }

    public static function postProjectCreationModeration()
    {
        return new ProjectCreationModerationUpdateController();
    }

    public static function getProjectCreationTemplates()
    {
        return new ProjectTemplatesController();
    }

    public static function getProjectCreationWebhooks()
    {
        return new WebhooksDisplayController();
    }

    public static function postProjectCreationWebhooks()
    {
        return new WebhooksUpdateController();
    }

    public static function getProjectCreationFields()
    {
        return new ProjectFieldsDisplayController();
    }

    public static function postProjectCreationFields(): ProjectFieldsUpdateController
    {
        return new ProjectFieldsUpdateController(
            new ProjectsFieldDescriptionUpdater(
                new \Project_CustomDescription_CustomDescriptionDao(),
                new ConfigDao()
            )
        );
    }

    public static function getProjectCreationCategories()
    {
        return new ProjectCategoriesDisplayController();
    }

    public static function postProjectCreationCategories()
    {
        return new TroveCatListController();
    }

    public static function getProjectCreationVisibility()
    {
        return new ProjectVisibilityConfigDisplayController();
    }

    public static function getProjectConfigurationWidgets(): ProjectWidgetsConfigurationDisplayController
    {
        return new ProjectWidgetsConfigurationDisplayController(
            self::getWidgetFactory()
        );
    }

    public static function getProjectConfigurationPOSTWidgetsEnable(): ProjectWidgetsConfigurationPOSTEnableController
    {
        return new ProjectWidgetsConfigurationPOSTEnableController(
            self::getWidgetFactory(),
            new DisabledProjectWidgetsDao()
        );
    }

    public static function getProjectConfigurationPOSTWidgetsDisable(): ProjectWidgetsConfigurationPOSTDisableController
    {
        return new ProjectWidgetsConfigurationPOSTDisableController(
            self::getWidgetFactory(),
            new DisabledProjectWidgetsDao()
        );
    }

    private static function getWidgetFactory(): WidgetFactory
    {
        return new WidgetFactory(
            \UserManager::instance(),
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            EventManager::instance()
        );
    }

    public static function postProjectCreationVisibility()
    {
        return new ProjectVisibilityConfigUpdateController(
            new ProjectVisibilityConfigManager(
                new ConfigDao()
            )
        );
    }

    public static function getAccountToken(): DispatchableWithRequest
    {
        return new DisplayKeysTokensController(
            EventManager::instance(),
            TemplateRendererFactory::build(),
            DisplayKeysTokensController::getCSRFToken(),
            AccessKeyPresenterBuilder::build(),
            SVNTokensPresenterBuilder::build(),
        );
    }

    public static function getEditionController(): DispatchableWithRequest
    {
        return new DisplayEditionController(
            EventManager::instance(),
            TemplateRendererFactory::build(),
            DisplayEditionController::getCSRFToken()
        );
    }

    public static function postEditionController(): DispatchableWithRequest
    {
        return new UpdateEditionController(
            DisplayEditionController::getCSRFToken()
        );
    }

    public static function getAppearanceController(): DispatchableWithRequest
    {
        return new DisplayAppearanceController(
            EventManager::instance(),
            TemplateRendererFactory::build(),
            DisplayAppearanceController::getCSRFToken(),
            new AppearancePresenterBuilder(
                new LanguagePresenterBuilder(new \BaseLanguageFactory()),
                new ThemeColorPresenterBuilder(new \ThemeVariant())
            )
        );
    }

    public static function postAppearanceController(): DispatchableWithRequest
    {
        return new UpdateAppearancePreferences(
            DisplayAppearanceController::getCSRFToken(),
            \UserManager::instance(),
            $GLOBALS['Language'],
            new ThemeVariant(),
            new SelectedDateDisplayPreferenceValidator()
        );
    }

    public static function getExperimentalController(): DispatchableWithRequest
    {
        return new DisplayExperimentalController(
            EventManager::instance(),
            TemplateRendererFactory::build(),
            DisplayExperimentalController::getCSRFToken()
        );
    }

    public static function postExperimentalController(): DispatchableWithRequest
    {
        return new UpdateExperimentalPreferences(DisplayExperimentalController::getCSRFToken());
    }

    public static function getAccountSecurity(): DispatchableWithRequest
    {
        return DisplaySecurityController::buildSelf();
    }

    public function postAccountSecuritySession(): DispatchableWithRequest
    {
        return new UpdateSessionPreferencesController(
            DisplaySecurityController::getCSRFToken(),
            \UserManager::instance(),
        );
    }

    public function postAccountSecurityPassword(): DispatchableWithRequest
    {
        return UpdatePasswordController::buildSelf();
    }

    public function getWellKnownUrlChangePassword(): UserWellKnownChangePasswordController
    {
        return new UserWellKnownChangePasswordController(
            \UserManager::instance(),
            EventManager::instance(),
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            new SapiEmitter()
        );
    }

    public static function postAccountSSHKeyCreate(): DispatchableWithRequest
    {
        return new SSHKeyCreateController(DisplayKeysTokensController::getCSRFToken(), \UserManager::instance());
    }

    public static function postAccountSSHKeyDelete(): DispatchableWithRequest
    {
        return new SSHKeyDeleteController(DisplayKeysTokensController::getCSRFToken(), \UserManager::instance());
    }

    public static function postAccountAccessKeyCreate(): DispatchableWithRequest
    {
        return new AccessKeyCreationController(DisplayKeysTokensController::getCSRFToken());
    }

    public static function postAccountAccessKeyRevoke(): DispatchableWithRequest
    {
        return new AccessKeyRevocationController(DisplayKeysTokensController::getCSRFToken());
    }

    public static function postAccountSVNTokenCreate(): DispatchableWithRequest
    {
        return new SVNTokenCreateController(DisplayKeysTokensController::getCSRFToken(), SVN_TokenHandler::build(), new KeyFactory());
    }

    public static function postAccountSVNTokenRevoke(): DispatchableWithRequest
    {
        return new SVNTokenRevokeController(DisplayKeysTokensController::getCSRFToken(), SVN_TokenHandler::build());
    }

    public static function getAccountPreferences(): DispatchableWithRequest
    {
        return DisplayAccountInformationController::buildSelf();
    }

    public static function postAccountInformation(): DispatchableWithRequest
    {
        return UpdateAccountInformationController::buildSelf();
    }

    public static function getEmailConfirm(): DispatchableWithRequest
    {
        return ConfirmNewEmailController::buildSelf();
    }

    public static function getAccountNotifications(): DispatchableWithRequest
    {
        return new DisplayNotificationsController(
            EventManager::instance(),
            TemplateRendererFactory::build(),
            DisplayNotificationsController::getCSRFToken(),
            new MailManager(),
        );
    }

    public static function postAccountNotifications(): DispatchableWithRequest
    {
        return new UpdateNotificationsPreferences(
            DisplayNotificationsController::getCSRFToken(),
            \UserManager::instance(),
        );
    }

    public static function postAccountAvatar()
    {
        $user_manager = \UserManager::instance();

        return new ChangeAvatarController(
            DisplayAccountInformationController::getCSRFToken(),
            new UserAvatarSaver($user_manager),
            $user_manager
        );
    }

    public static function postLogoutAccount(): LogoutController
    {
        return new LogoutController(\UserManager::instance());
    }

    public static function getUsersName()
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();

        return new ProfileController(
            new ProfilePresenterBuilder(EventManager::instance(), Codendi_HTMLPurifier::instance()),
            new ProfileAsJSONForTooltipController(
                new JSONResponseBuilder(
                    $response_factory,
                    HTTPFactoryBuilder::streamFactory()
                ),
                new SapiEmitter(),
                $response_factory,
                TemplateRendererFactory::build()
            ),
        );
    }

    public static function getUsersNameAvatar()
    {
        return new AvatarController(new AvatarGenerator());
    }

    public static function getUsersNameAvatarHash()
    {
        return new AvatarController(new AvatarGenerator(), ['expires' => 'never']);
    }

    public static function postJoinPrivateProjectMail()
    {
        return new PermissionDeniedPrivateProjectMailSender(
            new PlaceHolderBuilder(\ProjectManager::instance()),
            new \CSRFSynchronizerToken("/join-private-project-mail/")
        );
    }

    public static function postJoinRestrictedUserMail()
    {
        return new PermissionDeniedRestrictedMemberMailSender(
            new PlaceHolderBuilder(\ProjectManager::instance()),
            new \CSRFSynchronizerToken("/join-project-restricted-user-mail/")
        );
    }

    public static function getProjectAdminIndexCategories()
    {
        return Categories\IndexController::buildSelf();
    }

    public static function getProjectAdminUpdateCategories()
    {
        return Categories\UpdateController::buildSelf();
    }

    public static function getSvnViewVC()
    {
        return new \Tuleap\SvnCore\ViewVC\ViewVCController();
    }

    public static function getCVSViewVC()
    {
        return new \Tuleap\ConcurrentVersionsSystem\ViewVC\ViewVCController();
    }

    public static function getOldFileDownloadURLRedirection(): FRSFileDownloadOldURLRedirectionController
    {
        return new FRSFileDownloadOldURLRedirectionController(HTTPFactoryBuilder::responseFactory(), new SapiEmitter());
    }

    public static function getFileDownload(): FRSFileDownloadController
    {
        return new FRSFileDownloadController(
            new URLVerification(),
            new FRSFileFactory(),
            new BinaryFileResponseBuilder(
                HTTPFactoryBuilder::responseFactory(),
                HTTPFactoryBuilder::streamFactory()
            ),
            new SapiStreamEmitter(),
            new SessionWriteCloseMiddleware(),
            new RESTCurrentUserMiddleware(UserManager::build(), new BasicAuthentication()),
            new TuleapRESTCORSMiddleware()
        );
    }

    public static function getFileDownloadAgreementAdminList(): DispatchableWithRequest
    {
        return ListLicenseAgreementsController::buildSelf();
    }

    public static function getFileDownloadAgreementAdminAdd(): DispatchableWithRequest
    {
        return AddLicenseAgreementController::buildSelf();
    }

    public static function getFileDownloadAgreementAdminEdit(): DispatchableWithRequest
    {
        return EditLicenseAgreementController::buildSelf();
    }

    public static function getFileDownloadAgreementAdminSave(): DispatchableWithRequest
    {
        return SaveLicenseAgreementController::buildSelf();
    }

    public static function getFileDownloadAgreementAdminSetDefault(): DispatchableWithRequest
    {
        return SetDefaultLicenseAgreementController::buildSelf();
    }

    public static function getRssLatestProjects()
    {
        return new LatestProjectController(new LatestProjectDao(), \ProjectManager::instance(), Codendi_HTMLPurifier::instance());
    }

    public static function getRssLatestNews()
    {
        return new LatestNewsController(new NewsDao(), Codendi_HTMLPurifier::instance());
    }

    public static function getNewsPermissionsPerGroup(): DispatchableWithRequest
    {
        return new PermissionsPerGroup();
    }

    public static function getProjectAdminMembersController(): DispatchableWithRequest
    {
        return ProjectMembersController::buildSelf();
    }

    public static function getAdminHelpDropdownController(): AdminReleaseNoteLinkController
    {
        return AdminReleaseNoteLinkController::buildSelf();
    }

    public static function postAdminHelpDropdownController(): PostAdminReleaseNoteLinkController
    {
        return PostAdminReleaseNoteLinkController::buildSelf();
    }

    public static function getPostUserGroupIdAdd(): DispatchableWithRequest
    {
        return MemberAdditionController::buildSelf();
    }

    public static function getPostUserGroupIdRemove(): DispatchableWithRequest
    {
        return MemberRemovalController::buildSelf();
    }

    public static function getPostSynchronizedMembershipActivation(): DispatchableWithRequest
    {
        return ActivationController::buildSelf();
    }

    public static function getGetServices(): DispatchableWithRequest
    {
        return IndexController::buildSelf();
    }

    public static function getPostServicesAdd(): DispatchableWithRequest
    {
        return AddController::buildSelf();
    }

    public static function getPostServicesEdit(): DispatchableWithRequest
    {
        return EditController::buildSelf();
    }

    public static function getPostServicesDelete(): DispatchableWithRequest
    {
        return DeleteController::buildSelf();
    }

    public static function getGetProjectBannerAdministration(): DispatchableWithRequest
    {
        return BannerAdministrationController::buildSelf();
    }

    public static function getGetPlatformBannerAdministration(): DispatchableWithRequest
    {
        return new PlatformBannerAdministrationController(
            new AdminPageRenderer(),
            new IncludeAssets(__DIR__ . '/../../www/assets/core', '/assets/core'),
            new BannerRetriever(new BannerDao())
        );
    }

    public static function getGetProjectBackgroundAdministration(): DispatchableWithRequest
    {
        return ProjectBackgroundAdministrationController::buildSelf();
    }

    public static function getProjectRegistrationController(): ProjectRegistrationController
    {
        $core_assets = new IncludeAssets(__DIR__ . '/../../www/assets/core', '/assets/core');
        return new ProjectRegistrationController(
            TemplateRendererFactory::build(),
            $core_assets,
            new ProjectRegistrationUserPermissionChecker(
                new \ProjectDao()
            ),
            new ProjectRegistrationPresenterBuilder(
                TemplateFactory::build(),
                new DefaultProjectVisibilityRetriever(),
                new TroveCatFactory(new TroveCatDao()),
                new DescriptionFieldsFactory(new DescriptionFieldsDao())
            )
        );
    }

    public function getLegacyController(string $path)
    {
        return new LegacyRoutesController($path);
    }

    public function getAdminDatesDisplay(): RelativeDatesDisplayController
    {
        return new RelativeDatesDisplayController(
            new AdminPageRenderer(),
            RelativeDatesDisplayController::buildCSRFToken()
        );
    }

    public function postAdminDatesDisplay(): RelativeDatesDisplaySaveController
    {
        return new RelativeDatesDisplaySaveController(
            RelativeDatesDisplayController::buildCSRFToken(),
            new SelectedDateDisplayPreferenceValidator(),
            new ConfigDao(),
            new \UserPreferencesDao()
        );
    }

    public function getAdminInvitationsController(): InviteBuddyAdminController
    {
        return InviteBuddyAdminController::buildSelf();
    }

    public function getAdminInvitationsUpdateController(): InviteBuddyAdminUpdateController
    {
        return InviteBuddyAdminUpdateController::buildSelf();
    }


    private function getLegacyControllerHandler(string $path): array
    {
        return [
            'core' => true,
            'handler' => 'getLegacyController',
            'params' => [$path]
        ];
    }

    public function collect(FastRoute\RouteCollector $r)
    {
        $r->get('/', [self::class, 'getSlash']);

        $r->get('/contact.php', $this->getLegacyControllerHandler(__DIR__ . '/../../core/contact.php'));
        $r->addRoute(['GET', 'POST'], '/goto[.php]', $this->getLegacyControllerHandler(__DIR__ . '/../../core/goto.php'));
        $r->get('/info.php', $this->getLegacyControllerHandler(__DIR__ . '/../../core/info.php'));
        $r->get('/robots.txt', $this->getLegacyControllerHandler(__DIR__ . '/../../core/robots.php'));
        $r->post('/make_links.php', $this->getLegacyControllerHandler(__DIR__ . '/../../core/make_links.php'));
        $r->post('/sparklines.php', $this->getLegacyControllerHandler(__DIR__ . '/../../core/sparklines.php'));
        $r->get('/toggler.php', $this->getLegacyControllerHandler(__DIR__ . '/../../core/toggler.php'));

        $r->addGroup('/project/{id:\d+}/admin', function (FastRoute\RouteCollector $r) {
            $r->get('/categories', [self::class, 'getProjectAdminIndexCategories']);
            $r->post('/categories', [self::class, 'getProjectAdminUpdateCategories']);

            $r->addRoute(['GET', 'POST'], '/members', [self::class, 'getProjectAdminMembersController']);

            $r->post('/change-synchronized-project-membership', [self::class, 'getPostSynchronizedMembershipActivation']);
            $r->post('/user-group/{user-group-id:\d+}/add', [self::class, 'getPostUserGroupIdAdd']);
            $r->post('/user-group/{user-group-id:\d+}/remove', [self::class, 'getPostUserGroupIdRemove']);

            $r->get('/services', [self::class, 'getGetServices']);
            $r->post('/services/add', [self::class, 'getPostServicesAdd']);
            $r->post('/services/edit', [self::class, 'getPostServicesEdit']);
            $r->post('/services/delete', [self::class, 'getPostServicesDelete']);
            $r->get('/banner', [self::class, 'getGetProjectBannerAdministration']);
            $r->get('/background', [self::class, 'getGetProjectBackgroundAdministration']);
        });

        $r->addRoute(['GET', 'POST'], '/projects/{name}[/]', [self::class, 'getOrPostProjectHome']);

        $r->addGroup('/admin', function (FastRoute\RouteCollector $r) {
            $r->get('/invitations/', [self::class, 'getAdminInvitationsController']);
            $r->post('/invitations/', [self::class, 'getAdminInvitationsUpdateController']);

            $r->get('/release-note/', [self::class, 'getAdminHelpDropdownController']);
            $r->post('/release-note/', [self::class, 'postAdminHelpDropdownController']);

            $r->get('/password_policy/', [self::class, 'getAdminPasswordPolicy']);
            $r->post('/password_policy/', [self::class, 'postAdminPasswordPolicy']);

            $r->get('/project-creation/moderation', [self::class, 'getProjectCreationModeration']);
            $r->post('/project-creation/moderation', [self::class, 'postProjectCreationModeration']);

            $r->get('/project-creation/templates', [self::class, 'getProjectCreationTemplates']);

            $r->get('/project-creation/webhooks', [self::class, 'getProjectCreationWebhooks']);
            $r->post('/project-creation/webhooks', [self::class, 'postProjectCreationWebhooks']);

            $r->get('/project-creation/fields', [self::class, 'getProjectCreationFields']);
            $r->post('/project-creation/fields', [self::class, 'postProjectCreationFields']);

            $r->get('/project-creation/categories', [self::class, 'getProjectCreationCategories']);
            $r->post('/project-creation/categories', [self::class, 'postProjectCreationCategories']);

            $r->get('/project-creation/visibility', [self::class, 'getProjectCreationVisibility']);
            $r->post('/project-creation/visibility', [self::class, 'postProjectCreationVisibility']);

            $r->get('/project-creation/widgets', [self::class, 'getProjectConfigurationWidgets']);
            $r->post('/project-creation/widgets/{widget-id}/enable', [self::class, 'getProjectConfigurationPOSTWidgetsEnable']);
            $r->post('/project-creation/widgets/{widget-id}/disable', [self::class, 'getProjectConfigurationPOSTWidgetsDisable']);

            $r->get('/site-content-customisations', [self::class, 'getAdminSiteContentCustomisation']);

            $r->get('/dates-display', [self::class, 'getAdminDatesDisplay']);
            $r->post('/dates-display', [self::class, 'postAdminDatesDisplay']);

            $r->get('/banner', [self::class, 'getGetPlatformBannerAdministration']);
        });

        $r->addGroup('/account', static function (FastRoute\RouteCollector $r) {
            $r->get('/information', [self::class, 'getAccountPreferences']);
            $r->post('/information', [self::class, 'postAccountInformation']);

            $r->get('/confirm-new-email', [self::class, 'getEmailConfirm']);

            $r->get('/notifications', [self::class, 'getAccountNotifications']);
            $r->post('/notifications', [self::class, 'postAccountNotifications']);

            $r->get('/keys-tokens', [self::class, 'getAccountToken']);
            $r->post('/ssh_key/create', [self::class, 'postAccountSSHKeyCreate']);
            $r->post('/ssh_key/delete', [self::class, 'postAccountSSHKeyDelete']);
            $r->post('/access_key/create', [self::class, 'postAccountAccessKeyCreate']);
            $r->post('/access_key/revoke', [self::class, 'postAccountAccessKeyRevoke']);
            $r->post('/svn_token/create', [self::class, 'postAccountSVNTokenCreate']);
            $r->post('/svn_token/revoke', [self::class, 'postAccountSVNTokenRevoke']);

            $r->get('/edition', [self::class, 'getEditionController']);
            $r->post('/edition', [self::class, 'postEditionController']);

            $r->get('/appearance', [self::class, 'getAppearanceController']);
            $r->post('/appearance', [self::class, 'postAppearanceController']);

            $r->get('/experimental', [self::class, 'getExperimentalController']);
            $r->post('/experimental', [self::class, 'postExperimentalController']);

            $r->get('/security', [self::class, 'getAccountSecurity']);
            $r->post('/security/session', [self::class, 'postAccountSecuritySession']);
            $r->post('/security/password', [self::class, 'postAccountSecurityPassword']);

            $r->post('/avatar', [self::class, 'postAccountAvatar']);
            $r->post('/logout', [self::class, 'postLogoutAccount']);
        });
        $r->get('/.well-known/change-password', [self::class, 'getWellKnownUrlChangePassword']);

        $r->addGroup('/users', function (FastRoute\RouteCollector $r) {
            $r->get('/{name}[/]', [self::class, 'getUsersName']);
            $r->get('/{name}/avatar.png', [self::class, 'getUsersNameAvatar']);
            $r->get('/{name}/avatar-{hash}.png', [self::class, 'getUsersNameAvatarHash']);
        });

        $r->post('/join-private-project-mail/', [self::class, 'postJoinPrivateProjectMail']);
        $r->post('/join-project-restricted-user-mail/', [self::class, 'postJoinRestrictedUserMail']);

        $r->get('/svn/viewvc.php[/{path:.*}]', [self::class, 'getSvnViewVC']);
        $r->get('/cvs/viewvc.php[/{path:.*}]', [self::class, 'getCVSViewVC']);

        $r->addGroup('/file', function (FastRoute\RouteCollector $r) {
            $r->get('/download.php/{group_id:\d+}/{file_id:\d+}[/{filename:.*}]', [self::class, 'getOldFileDownloadURLRedirection']);
            $r->get('/download/{file_id:\d+}', [self::class, 'getFileDownload']);
            $r->get('/{project_id:\d+}/admin/license-agreements', [self::class, 'getFileDownloadAgreementAdminList']);
            $r->get('/{project_id:\d+}/admin/license-agreements/add', [self::class, 'getFileDownloadAgreementAdminAdd']);
            $r->get('/{project_id:\d+}/admin/license-agreements/{id:\d+}', [self::class, 'getFileDownloadAgreementAdminEdit']);
            $r->post('/{project_id:\d+}/admin/license-agreements/save', [self::class, 'getFileDownloadAgreementAdminSave']);
            $r->post('/{project_id:\d+}/admin/license-agreements/set-default', [self::class, 'getFileDownloadAgreementAdminSetDefault']);
        });

        $r->get('/export/rss_sfprojects.php', [self::class, 'getRssLatestProjects']);
        $r->get('/export/rss_sfnews.php', [self::class, 'getRssLatestNews']);

        $r->get('/news/permissions-per-group', [self::class, 'getNewsPermissionsPerGroup']);

        $r->get('/project/new', [self::class, 'getProjectRegistrationController']);
        $r->get('/project/new-information', [self::class, 'getProjectRegistrationController']);
        $r->get('/project/approval', [self::class, 'getProjectRegistrationController']);

        $collect_routes = new CollectRoutesEvent($r);
        $this->event_manager->processEvent($collect_routes);
    }
}
