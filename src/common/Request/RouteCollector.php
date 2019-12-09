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

use ArtifactTypeFactory;
use Codendi_HTMLPurifier;
use ConfigDao;
use EventManager;
use FastRoute;
use FRSFileFactory;
use ProjectHistoryDao;
use ProjectManager;
use ServiceDao;
use ServiceManager;
use TroveCatDao;
use TroveCatFactory;
use Tuleap\Admin\AdminPageRenderer;
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
use Tuleap\admin\SiteContentCustomisationController;
use Tuleap\Core\RSS\News\LatestNewsController;
use Tuleap\Core\RSS\Project\LatestProjectController;
use Tuleap\Core\RSS\Project\LatestProjectDao;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Error\PermissionDeniedPrivateProjectMailSender;
use Tuleap\Error\PermissionDeniedRestrictedMemberMailSender;
use Tuleap\Error\PlaceHolderBuilder;
use Tuleap\FRS\FRSFileDownloadController;
use Tuleap\FRS\FRSFileDownloadOldURLRedirectionController;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\Admin\AddLicenseAgreementController;
use Tuleap\FRS\LicenseAgreement\Admin\EditLicenseAgreementController;
use Tuleap\FRS\LicenseAgreement\Admin\LicenseAgreementControllersHelper;
use Tuleap\FRS\LicenseAgreement\Admin\ListLicenseAgreementsController;
use Tuleap\FRS\LicenseAgreement\Admin\SaveLicenseAgreementController;
use Tuleap\FRS\LicenseAgreement\Admin\SetDefaultLicenseAgreementController;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDao;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\SessionWriteCloseMiddleware;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\SiteHomepageController;
use Tuleap\News\NewsDao;
use Tuleap\News\PermissionsPerGroup;
use Tuleap\Password\Administration\PasswordPolicyDisplayController;
use Tuleap\Password\Administration\PasswordPolicyUpdateController;
use Tuleap\Password\Configuration\PasswordConfigurationDAO;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;
use Tuleap\Password\Configuration\PasswordConfigurationSaver;
use Tuleap\Project\Admin\Categories;
use Tuleap\Project\Admin\Categories\ProjectCategoriesUpdater;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\ProjectMembers\ProjectMembersController;
use Tuleap\Project\Admin\ProjectMembers\ProjectMembersDAO;
use Tuleap\Project\Admin\ProjectUGroup\MemberAdditionController;
use Tuleap\Project\Admin\ProjectUGroup\MemberRemovalController;
use Tuleap\Project\Admin\ProjectUGroup\SynchronizedProjectMembership\ActivationController;
use Tuleap\Project\Admin\ProjectUGroup\UGroupRouter;
use Tuleap\Project\Banner\BannerAdministrationController;
use Tuleap\Project\Banner\BannerDao;
use Tuleap\Project\Banner\BannerRetriever;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\DescriptionFieldsDao;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Home;
use Tuleap\Project\Registration\ProjectRegistrationController;
use Tuleap\Project\Registration\ProjectRegistrationPresenterBuilder;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Service\AddController;
use Tuleap\Project\Service\DeleteController;
use Tuleap\Project\Service\EditController;
use Tuleap\Project\Service\IndexController;
use Tuleap\Project\Service\ServiceCreator;
use Tuleap\Project\Service\ServicePOSTDataBuilder;
use Tuleap\Project\Service\ServicesPresenterBuilder;
use Tuleap\Project\Service\ServiceUpdator;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithStatusCheckAndNotifications;
use Tuleap\Project\UGroups\Membership\MemberAdder;
use Tuleap\Project\UGroups\Membership\MemberRemover;
use Tuleap\Project\UGroups\Membership\StaticUGroups\StaticMemberRemover;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;
use Tuleap\REST\BasicAuthentication;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Tuleap\REST\TuleapRESTCORSMiddleware;
use Tuleap\REST\UserManager;
use Tuleap\Trove\TroveCatListController;
use Tuleap\User\AccessKey\AccessKeyCreationController;
use Tuleap\User\AccessKey\AccessKeyRevocationController;
use Tuleap\User\Account\ChangeAvatarController;
use Tuleap\User\Account\DisableLegacyBrowsersWarningMessageController;
use Tuleap\User\Account\LogoutController;
use Tuleap\User\Account\UserAvatarSaver;
use Tuleap\User\Profile\AvatarController;
use Tuleap\User\Profile\ProfileController;
use Tuleap\User\Profile\ProfilePresenterBuilder;
use UGroupBinding;
use UGroupManager;
use UGroupUserDao;
use URLVerification;
use UserHelper;
use UserImport;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;

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
            new AdminPageRenderer,
            \TemplateRendererFactory::build(),
            new \BaseLanguageFactory()
        );
    }

    public static function getAdminPasswordPolicy()
    {
        return new PasswordPolicyDisplayController(
            new AdminPageRenderer,
            \TemplateRendererFactory::build(),
            new PasswordConfigurationRetriever(new PasswordConfigurationDAO)
        );
    }

    public static function postAdminPasswordPolicy()
    {
        return new PasswordPolicyUpdateController(
            new PasswordConfigurationSaver(new PasswordConfigurationDAO)
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

    public static function postProjectCreationVisibility()
    {
        return new ProjectVisibilityConfigUpdateController(
            new ProjectVisibilityConfigManager(
                new ConfigDao()
            )
        );
    }

    public static function postAccountAccessKeyCreate()
    {
        return new AccessKeyCreationController();
    }

    public static function postAccountAccessKeyRevoke()
    {
        return new AccessKeyRevocationController();
    }

    public static function postAccountAvatar()
    {
        $user_manager = \UserManager::instance();
        return new ChangeAvatarController($user_manager, new UserAvatarSaver($user_manager));
    }

    public static function postLogoutAccount() : LogoutController
    {
        return new LogoutController(\UserManager::instance());
    }

    public function postDisableLegacyBrowsersWarningMessage() : DisableLegacyBrowsersWarningMessageController
    {
        return new DisableLegacyBrowsersWarningMessageController();
    }

    public static function getUsersName()
    {
        return new ProfileController(
            new ProfilePresenterBuilder(EventManager::instance(), Codendi_HTMLPurifier::instance())
        );
    }

    public static function getUsersNameAvatar()
    {
        return new AvatarController();
    }

    public static function getUsersNameAvatarHash()
    {
        return new AvatarController(['expires' => 'never']);
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
        return new Categories\IndexController(new TroveCatDao());
    }

    public static function getProjectAdminUpdateCategories()
    {
        return new Categories\UpdateController(
            \ProjectManager::instance(),
            new ProjectCategoriesUpdater(
                new \TroveCatFactory(new TroveCatDao()),
                new ProjectHistoryDao(),
                new Categories\TroveSetNodeFacade()
            )
        );
    }

    public static function getSvnViewVC()
    {
        return new \Tuleap\SvnCore\ViewVC\ViewVCController();
    }

    public static function getCVSViewVC()
    {
        return new \Tuleap\CVS\ViewVC\ViewVCController();
    }

    public static function getOldFileDownloadURLRedirection() : FRSFileDownloadOldURLRedirectionController
    {
        return new FRSFileDownloadOldURLRedirectionController(HTTPFactoryBuilder::responseFactory(), new SapiEmitter());
    }

    public static function getFileDownload() : FRSFileDownloadController
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
        return new ListLicenseAgreementsController(
            ProjectManager::instance(),
            new LicenseAgreementControllersHelper(
                FRSPermissionManager::build(),
                \TemplateRendererFactory::build(),
            ),
            \TemplateRendererFactory::build(),
            new LicenseAgreementFactory(
                new LicenseAgreementDao()
            ),
            SetDefaultLicenseAgreementController::getCSRFTokenSynchronizer(),
        );
    }

    public static function getFileDownloadAgreementAdminAdd(): DispatchableWithRequest
    {
        return new AddLicenseAgreementController(
            ProjectManager::instance(),
            new LicenseAgreementControllersHelper(
                FRSPermissionManager::build(),
                \TemplateRendererFactory::build(),
            ),
            \TemplateRendererFactory::build(),
            SaveLicenseAgreementController::getCSRFTokenSynchronizer(),
            new IncludeAssets(__DIR__ . '/../../www/assets/', '/assets'),
        );
    }

    public static function getFileDownloadAgreementAdminEdit(): DispatchableWithRequest
    {
        return new EditLicenseAgreementController(
            ProjectManager::instance(),
            new LicenseAgreementControllersHelper(
                FRSPermissionManager::build(),
                \TemplateRendererFactory::build(),
            ),
            \TemplateRendererFactory::build(),
            new LicenseAgreementFactory(
                new LicenseAgreementDao()
            ),
            SaveLicenseAgreementController::getCSRFTokenSynchronizer(),
            new IncludeAssets(__DIR__ . '/../../www/assets/', '/assets'),
        );
    }

    public static function getFileDownloadAgreementAdminSave(): DispatchableWithRequest
    {
        return new SaveLicenseAgreementController(
            ProjectManager::instance(),
            new LicenseAgreementControllersHelper(
                FRSPermissionManager::build(),
                \TemplateRendererFactory::build(),
            ),
            new LicenseAgreementFactory(
                new LicenseAgreementDao()
            ),
            SaveLicenseAgreementController::getCSRFTokenSynchronizer(),
        );
    }

    public static function getFileDownloadAgreementAdminSetDefault(): DispatchableWithRequest
    {
        return new SetDefaultLicenseAgreementController(
            ProjectManager::instance(),
            new LicenseAgreementControllersHelper(
                FRSPermissionManager::build(),
                \TemplateRendererFactory::build(),
            ),
            new LicenseAgreementFactory(
                new LicenseAgreementDao()
            ),
            SetDefaultLicenseAgreementController::getCSRFTokenSynchronizer(),
        );
    }

    public static function getRssLatestProjects()
    {
        return new LatestProjectController(new LatestProjectDao(), \ProjectManager::instance(), Codendi_HTMLPurifier::instance());
    }

    public static function getRssLatestNews()
    {
        return new LatestNewsController(new NewsDao(), Codendi_HTMLPurifier::instance());
    }

    public static function getNewsPermissionsPerGroup() : DispatchableWithRequest
    {
        return new PermissionsPerGroup();
    }

    public static function getProjectAdminMembersController() : DispatchableWithRequest
    {
        $event_manager   = EventManager::instance();
        $user_manager    = \UserManager::instance();
        $user_helper     = new UserHelper();
        $ugroup_manager  = new UGroupManager();
        $project_manager = ProjectManager::instance();
        $ugroup_binding  = new UGroupBinding(
            new UGroupUserDao(),
            $ugroup_manager
        );

        return new ProjectMembersController(
            new ProjectMembersDAO(),
            $user_helper,
            $ugroup_binding,
            new UserRemover(
                $project_manager,
                $event_manager,
                new ArtifactTypeFactory(false),
                new UserRemoverDao(),
                $user_manager,
                new ProjectHistoryDao(),
                $ugroup_manager
            ),
            $event_manager,
            $ugroup_manager,
            new UserImport(
                $user_manager,
                $user_helper,
                ProjectMemberAdderWithStatusCheckAndNotifications::build()
            ),
            $project_manager,
            new SynchronizedProjectMembershipDetector(
                new SynchronizedProjectMembershipDao()
            )
        );
    }

    public static function getPostUserGroupIdAdd() : DispatchableWithRequest
    {
        $ugroup_manager = new UGroupManager();
        return new MemberAdditionController(
            ProjectManager::instance(),
            $ugroup_manager,
            \UserManager::instance(),
            MemberAdder::build(
                ProjectMemberAdderWithStatusCheckAndNotifications::build()
            ),
            UGroupRouter::getCSRFTokenSynchronizer()
        );
    }

    public static function getPostUserGroupIdRemove() : DispatchableWithRequest
    {
        $project_manager = ProjectManager::instance();
        $ugroup_manager  = new UGroupManager();
        $event_manager   = EventManager::instance();
        $user_manager    = \UserManager::instance();
        return new MemberRemovalController(
            $project_manager,
            $ugroup_manager,
            $user_manager,
            new MemberRemover(
                new DynamicUGroupMembersUpdater(
                    new UserPermissionsDao(),
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                    ProjectMemberAdderWithStatusCheckAndNotifications::build(),
                    EventManager::instance()
                ),
                new StaticMemberRemover()
            ),
            new UserRemover(
                $project_manager,
                $event_manager,
                new ArtifactTypeFactory(false),
                new UserRemoverDao(),
                $user_manager,
                new ProjectHistoryDao(),
                $ugroup_manager
            ),
            UGroupRouter::getCSRFTokenSynchronizer()
        );
    }

    public static function getPostSynchronizedMembershipActivation(): DispatchableWithRequest
    {
        return new ActivationController(
            ProjectManager::instance(),
            new SynchronizedProjectMembershipDao(),
            UGroupRouter::getCSRFTokenSynchronizer()
        );
    }

    public static function getGetServices(): DispatchableWithRequest
    {
        return new IndexController(
            new ServicesPresenterBuilder(ServiceManager::instance(), EventManager::instance()),
            new IncludeAssets(__DIR__ . '/../../www/assets', '/assets'),
            new HeaderNavigationDisplayer(),
            ProjectManager::instance()
        );
    }

    public static function getPostServicesAdd(): DispatchableWithRequest
    {
        return new AddController(
            new ServiceCreator(new ServiceDao(), ProjectManager::instance()),
            new ServicePOSTDataBuilder(EventManager::instance(), ServiceManager::instance()),
            ProjectManager::instance(),
            IndexController::getCSRFTokenSynchronizer()
        );
    }

    public static function getPostServicesEdit(): DispatchableWithRequest
    {
        return new EditController(
            new ServiceUpdator(new ServiceDao(), ProjectManager::instance(), ServiceManager::instance()),
            new ServicePOSTDataBuilder(EventManager::instance(), ServiceManager::instance()),
            ServiceManager::instance(),
            ProjectManager::instance(),
            IndexController::getCSRFTokenSynchronizer()
        );
    }

    public static function getPostServicesDelete(): DispatchableWithRequest
    {
        return new DeleteController(
            new ServiceDao(),
            ProjectManager::instance(),
            IndexController::getCSRFTokenSynchronizer(),
            ServiceManager::instance()
        );
    }

    public static function getGetProjectBannerAdministration() : DispatchableWithRequest
    {
        return new BannerAdministrationController(
            \TemplateRendererFactory::build(),
            new HeaderNavigationDisplayer(),
            new IncludeAssets(__DIR__ . '/../../www/assets/', '/assets'),
            ProjectManager::instance(),
            new BannerRetriever(new BannerDao())
        );
    }

    public static function getProjectRegistrationController(): ProjectRegistrationController
    {
        return new ProjectRegistrationController(
            \TemplateRendererFactory::build(),
            new IncludeAssets(__DIR__ . '/../../www/assets/project-registration/scripts', '/assets/project-registration/scripts'),
            new IncludeAssets(__DIR__ . '/../../www/assets/project-registration/themes', '/assets/project-registration/themes'),
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


    private function getLegacyControllerHandler(string $path) : array
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

        $r->get('/contact.php', $this->getLegacyControllerHandler(__DIR__.'/../../core/contact.php'));
        $r->addRoute(['GET', 'POST'], '/goto[.php]', $this->getLegacyControllerHandler(__DIR__.'/../../core/goto.php'));
        $r->get('/info.php', $this->getLegacyControllerHandler(__DIR__.'/../../core/info.php'));
        $r->get('/robots.txt', $this->getLegacyControllerHandler(__DIR__.'/../../core/robots.php'));
        $r->post('/make_links.php', $this->getLegacyControllerHandler(__DIR__.'/../../core/make_links.php'));
        $r->post('/sparklines.php', $this->getLegacyControllerHandler(__DIR__.'/../../core/sparklines.php'));
        $r->get('/toggler.php', $this->getLegacyControllerHandler(__DIR__.'/../../core/toggler.php'));

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
        });

        $r->addRoute(['GET', 'POST'], '/projects/{name}[/]', [self::class, 'getOrPostProjectHome']);

        $r->addGroup('/admin', function (FastRoute\RouteCollector $r) {
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

            $r->get('/site-content-customisations', [self::class, 'getAdminSiteContentCustomisation']);
        });

        $r->addGroup('/account', function (FastRoute\RouteCollector $r) {
            $r->post('/access_key/create', [self::class, 'postAccountAccessKeyCreate']);
            $r->post('/access_key/revoke', [self::class, 'postAccountAccessKeyRevoke']);
            $r->post('/avatar', [self::class, 'postAccountAvatar']);
            $r->post('/logout', [self::class, 'postLogoutAccount']);
            $r->post('/disable_legacy_browser_warning', [self::class, 'postDisableLegacyBrowsersWarningMessage']);
        });

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
