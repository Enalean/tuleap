<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use ProjectHistoryDao;
use TroveCatDao;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\ProjectCreation\ProjectCategoriesDisplayController;
use Tuleap\Admin\ProjectCreation\ProjectFieldsDisplayController;
use Tuleap\Admin\ProjectCreation\ProjectFieldsUpdateController;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigDisplayController;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigManager;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigUpdateController;
use Tuleap\Admin\ProjectCreation\WebhooksDisplayController;
use Tuleap\Admin\ProjectCreation\WebhooksUpdateController;
use Tuleap\Admin\ProjectCreationModerationDisplayController;
use Tuleap\Admin\ProjectCreationModerationUpdateController;
use Tuleap\Admin\ProjectTemplatesController;
use Tuleap\Core\RSS\News\LatestNewsController;
use Tuleap\Core\RSS\Project\LatestProjectController;
use Tuleap\Core\RSS\Project\LatestProjectDao;
use Tuleap\Error\PermissionDeniedMailSender;
use Tuleap\Error\PlaceHolderBuilder;
use Tuleap\FRS\FileDownloadController;
use Tuleap\Layout\SiteHomepageController;
use Tuleap\News\NewsDao;
use Tuleap\Password\Administration\PasswordPolicyDisplayController;
use Tuleap\Password\Administration\PasswordPolicyUpdateController;
use Tuleap\Password\Configuration\PasswordConfigurationDAO;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;
use Tuleap\Password\Configuration\PasswordConfigurationSaver;
use Tuleap\Project\Admin\Categories;
use Tuleap\Project\Admin\Categories\ProjectCategoriesUpdater;
use Tuleap\Project\Home;
use Tuleap\Trove\TroveCatListController;
use Tuleap\User\AccessKey\AccessKeyCreationController;
use Tuleap\User\AccessKey\AccessKeyRevocationController;
use Tuleap\User\Account\ChangeAvatarController;
use Tuleap\User\Account\LogoutController;
use Tuleap\User\Account\UserAvatarSaver;
use Tuleap\User\Profile\AvatarController;
use Tuleap\User\Profile\ProfileController;
use Tuleap\User\Profile\ProfilePresenterBuilder;

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

    public static function postProjectCreationFields()
    {
        return new ProjectFieldsUpdateController();
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
        return new PermissionDeniedMailSender(
            new PlaceHolderBuilder(\ProjectManager::instance()),
            new \CSRFSynchronizerToken("/join-private-project-mail/")
        );
    }

    public static function postJoinRestrictedUserMail()
    {
        return new PermissionDeniedMailSender(
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
                new TroveCatDao(),
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

    public static function getFileDownload()
    {
        return new FileDownloadController();
    }

    public static function getRssLatestProjects()
    {
        return new LatestProjectController(new LatestProjectDao(), \ProjectManager::instance(), Codendi_HTMLPurifier::instance());
    }

    public static function getRssLatestNews()
    {
        return new LatestNewsController(new NewsDao(), Codendi_HTMLPurifier::instance());
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
        $r->get('/', [__CLASS__, 'getSlash']);

        $r->get('/contact.php', $this->getLegacyControllerHandler(__DIR__.'/../../core/contact.php'));
        $r->addRoute(['GET', 'POST'], '/goto[.php]', $this->getLegacyControllerHandler(__DIR__.'/../../core/goto.php'));
        $r->get('/info.php', $this->getLegacyControllerHandler(__DIR__.'/../../core/info.php'));
        $r->get('/robots.txt', $this->getLegacyControllerHandler(__DIR__.'/../../core/robots.php'));
        $r->post('/make_links.php', $this->getLegacyControllerHandler(__DIR__.'/../../core/make_links.php'));
        $r->post('/sparklines.php', $this->getLegacyControllerHandler(__DIR__.'/../../core/sparklines.php'));
        $r->get('/toggler.php', $this->getLegacyControllerHandler(__DIR__.'/../../core/toggler.php'));

        $r->addGroup('/project/{id:\d+}/admin', function (FastRoute\RouteCollector $r) {
            $r->get('/categories', [__CLASS__, 'getProjectAdminIndexCategories']);
            $r->post('/categories', [__CLASS__, 'getProjectAdminUpdateCategories']);
        });

        $r->addRoute(['GET', 'POST'], '/projects/{name}[/]', [__CLASS__, 'getOrPostProjectHome']);

        $r->addGroup('/admin', function (FastRoute\RouteCollector $r) {
            $r->get('/password_policy/', [__CLASS__, 'getAdminPasswordPolicy']);
            $r->post('/password_policy/', [__CLASS__, 'postAdminPasswordPolicy']);

            $r->get('/project-creation/moderation', [__CLASS__, 'getProjectCreationModeration']);
            $r->post('/project-creation/moderation', [__CLASS__, 'postProjectCreationModeration']);

            $r->get('/project-creation/templates', [__CLASS__, 'getProjectCreationTemplates']);

            $r->get('/project-creation/webhooks', [__CLASS__, 'getProjectCreationWebhooks']);
            $r->post('/project-creation/webhooks', [__CLASS__, 'postProjectCreationWebhooks']);

            $r->get('/project-creation/fields', [__CLASS__, 'getProjectCreationFields']);
            $r->post('/project-creation/fields', [__CLASS__, 'postProjectCreationFields']);

            $r->get('/project-creation/categories', [__CLASS__, 'getProjectCreationCategories']);
            $r->post('/project-creation/categories', [__CLASS__, 'postProjectCreationCategories']);

            $r->get('/project-creation/visibility', [__CLASS__, 'getProjectCreationVisibility']);
            $r->post('/project-creation/visibility', [__CLASS__, 'postProjectCreationVisibility']);
        });

        $r->addGroup('/account', function (FastRoute\RouteCollector $r) {
            $r->post('/access_key/create', [__CLASS__, 'postAccountAccessKeyCreate']);
            $r->post('/access_key/revoke', [__CLASS__, 'postAccountAccessKeyRevoke']);
            $r->post('/avatar', [__CLASS__, 'postAccountAvatar']);
            $r->post('/logout', [__CLASS__, 'postLogoutAccount']);
        });


        $r->addGroup('/users', function (FastRoute\RouteCollector $r) {
            $r->get('/{name}[/]', [__CLASS__, 'getUsersName']);
            $r->get('/{name}/avatar.png', [__CLASS__, 'getUsersNameAvatar']);
            $r->get('/{name}/avatar-{hash}.png', [__CLASS__, 'getUsersNameAvatarHash']);
        });

        $r->post('/join-private-project-mail/', [__CLASS__, 'postJoinPrivateProjectMail']);
        $r->post('/join-project-restricted-user-mail/', [__CLASS__, 'postJoinRestrictedUserMail']);

        $r->get('/svn/viewvc.php[/{path:.*}]', [__CLASS__, 'getSvnViewVC']);
        $r->get('/cvs/viewvc.php[/{path:.*}]', [__CLASS__, 'getCVSViewVC']);
        $r->get('/file/download.php/{group_id:\d+}/{file_id:\d+}[/{filename:.*}]', [__CLASS__, 'getFileDownload']);

        $r->get('/export/rss_sfprojects.php', [__CLASS__, 'getRssLatestProjects']);
        $r->get('/export/rss_sfnews.php', [__CLASS__, 'getRssLatestNews']);

        $collect_routes = new CollectRoutesEvent($r);
        $this->event_manager->processEvent($collect_routes);
    }
}
