<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

use Tuleap\BrowserDetection\DetectedBrowser;
use Tuleap\BurningParrotCompatiblePageDetector;
use Tuleap\Error\ErrorDependenciesInjector;
use Tuleap\Error\PermissionDeniedPrivateProjectController;
use Tuleap\Error\PermissionDeniedRestrictedAccountController;
use Tuleap\Error\PermissionDeniedRestrictedAccountProjectController;
use Tuleap\Error\PlaceHolderBuilder;
use Tuleap\Error\ProjectAccessSuspendedController;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\ErrorRendering;
use Tuleap\Project\AccessNotActiveException;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Project\Admin\ProjectMembers\UserCanManageProjectMembersChecker;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\ProjectAccessSuspendedException;
use Tuleap\Project\RestrictedUserCanAccessUrlOrProjectVerifier;
use Tuleap\Request\RequestInstrumentation;
use Tuleap\User\Account\DisplaySecurityController;
use Tuleap\User\Account\UpdatePasswordController;

/**
 * Check the URL validity (protocol, host name, query) regarding server constraints
 * (anonymous, user status, project privacy, ...) and manage redirection when needed
 */
class URLVerification // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    protected $urlChunks = null;

    /**
     * Returns an array containing data for the redirection URL
     *
     * @return Array
     */
    public function getUrlChunks()
    {
        return $this->urlChunks;
    }

    public function getCurrentUser(): \Tuleap\User\CurrentUserWithLoggedInInformation
    {
        return UserManager::instance()->getCurrentUserWithLoggedInInformation();
    }

    /**
     * Returns an instance of EventManager
     *
     * @return EventManager
     */
    public function getEventManager()
    {
        return EventManager::instance();
    }

    /**
     * Returns a instance of Url
     *
     * @return URL
     */
    protected function getUrl()
    {
        return new URL();
    }

    private function getForgeAccess(): ForgeAccess
    {
        return new ForgeAccess();
    }

    /**
     * Tests if the requested script name is allowed for anonymous or not
     *
     * @param Array $server
     *
     * @return bool
     */
    public function isScriptAllowedForAnonymous($server)
    {
        // Defaults
        $allowedAnonymous['/account/login.php']     = true;
        $allowedAnonymous['/account/register.php']  = true;
        $allowedAnonymous['/include/check_pw.php']  = true;
        $allowedAnonymous['/account/lostlogin.php'] = true;
        if (isset($allowedAnonymous[$server['SCRIPT_NAME']]) && $allowedAnonymous[$server['SCRIPT_NAME']] == true) {
            return true;
        }

        if ($server['REQUEST_URI'] === '/' && ForgeConfig::get(ForgeAccess::ANONYMOUS_CAN_SEE_SITE_HOMEPAGE) === '1') {
            return true;
        }

        if ($server['REQUEST_URI'] === '/contact.php' && ForgeConfig::get(ForgeAccess::ANONYMOUS_CAN_SEE_CONTACT) === '1') {
            return true;
        }

        // Plugins
        $anonymousAllowed = false;
        $params           = ['script_name' => $server['SCRIPT_NAME'], 'anonymous_allowed' => &$anonymousAllowed];
        $this->getEventManager()->processEvent('anonymous_access_to_script_allowed', $params);

        return $anonymousAllowed;
    }

    /**
     * Should we treat current request as an exception
     *
     * @param array $server
     */
    public function isException($server): bool
    {
        return preg_match('`^(?:/plugins/[^/]+)?/api/`', $server['SCRIPT_NAME']) === 1;
    }

    /**
     * Check if an URI is internal to the application or not. We reject all URLs
     * except /path/to/feature
     */
    public function isInternal(string $uri): bool
    {
        $url_decoded = urldecode($uri);
        return preg_match('/(?:^[\/?][[:alnum:]]+)|(?:^' . preg_quote(\Tuleap\ServerHostname::HTTPSUrl() . '/', '/') . ')/', $url_decoded) === 1;
    }

    /**
     * Returns the redirection URL from urlChunks
     *
     * This method returns the ideal URL to use to access a ressource. It doesn't
     * check if the URL is valid or not.
     * It conserves the same entree for protocol (i.e host or  request)  when it not has
     * been modified by one of the methods dedicated to verify its validity.
     *
     * @param Array $server
     *
     * @return String
     */
    public function getRedirectionURL($server)
    {
        $chunks = $this->getUrlChunks();

        $location = $this->getRedirectLocation();

        if (isset($chunks['script'])) {
            $location .= $chunks['script'];
        } else {
            $location .= $server['REQUEST_URI'];
        }
        return $location;
    }

    private function getRedirectLocation(): string
    {
        return \Tuleap\ServerHostname::HTTPSUrl();
    }

    /**
     * Check if anonymous is granted to access else redirect to login page
     *
     * @param Array $server
     *
     * @return void
     */
    public function verifyRequest($server)
    {
        $user = $this->getCurrentUser();

        if (
            $this->getForgeAccess()->doesPlatformRequireLogin() &&
            ! $user->is_logged_in &&
            ! $this->isScriptAllowedForAnonymous($server)
        ) {
            $redirect                  = new URLRedirect($this->getEventManager());
            $this->urlChunks['script'] = $redirect->buildReturnToLogin($server);
        }
    }

    /**
     * Checks that a restricted user can access the requested URL.
     *
     * @param Array $server
     *
     * @return void
     */
    public function checkRestrictedAccess($server)
    {
        $current_user = $this->getCurrentUser();
        if ($current_user->user->isRestricted()) {
            $url = $this->getUrl();
            if (! $this->restrictedUserCanAccessUrl($current_user->user, $url, $server['REQUEST_URI'], null)) {
                $this->displayRestrictedUserError($current_user);
            }
        }
    }

    /**
     * Test if given url is restricted for user
     *
     * @param Url $url
     * @param String $request_uri
     * @return bool False if user not allowed to see the content
     */
    protected function restrictedUserCanAccessUrl(PFUser $user, URL $url, string $request_uri, ?Project $project = null)
    {
        $verifier = new RestrictedUserCanAccessUrlOrProjectVerifier($this->getEventManager(), $url, $request_uri);

        return $verifier->isRestrictedUserAllowedToAccess($user, $project);
    }

    /**
     * Display error message for restricted user in a project
     *
     * @protected for test purpose
     *
     * @return void
     */
    protected function displayRestrictedUserProjectError(\Tuleap\User\CurrentUserWithLoggedInInformation $current_user, Project $project)
    {
        $GLOBALS['Response']->send401UnauthorizedHeader();
        $controller = new PermissionDeniedRestrictedAccountProjectController(
            $this->getThemeManager(),
            new ErrorDependenciesInjector(),
            new PlaceHolderBuilder(ProjectManager::instance())
        );
        $controller->displayError($current_user, $project);
        exit;
    }

    /**
     * Display error message for restricted user.
     *
     * @protected for test purpose
     *
     * @return void
     */
    protected function displayRestrictedUserError(\Tuleap\User\CurrentUserWithLoggedInInformation $user)
    {
        $GLOBALS['Response']->send401UnauthorizedHeader();
        $controller = new PermissionDeniedRestrictedAccountController(
            $this->getThemeManager(),
            new ErrorDependenciesInjector(),
            new PlaceHolderBuilder(ProjectManager::instance())
        );
        $controller->displayError($user);
        exit;
    }

    public function displayPrivateProjectError(\Tuleap\User\CurrentUserWithLoggedInInformation $current_user, ?Project $project = null)
    {
        $GLOBALS['Response']->send401UnauthorizedHeader();

        $this->checkUserIsLoggedIn($current_user);

        $sendMail = new PermissionDeniedPrivateProjectController(
            $this->getThemeManager(),
            new PlaceHolderBuilder(ProjectManager::instance()),
            new ErrorDependenciesInjector()
        );
        $sendMail->displayError($current_user, $project);
        exit;
    }

    public function displaySuspendedProjectError(\Tuleap\User\CurrentUserWithLoggedInInformation $current_user, Project $project)
    {
        $GLOBALS['Response']->send401UnauthorizedHeader();

        $this->checkUserIsLoggedIn($current_user);

        $suspended_project_controller = new ProjectAccessSuspendedController(
            $this->getThemeManager()
        );

        $suspended_project_controller->displayError($current_user);
        exit;
    }

    /**
     * Check URL is valid and redirect to the right host/url if needed.
     *
     * Force SSL mode if required except if request comes from localhost, or for api scripts
     *
     * Limit responsability of each method for sake of simplicity. For instance:
     * getRedirectionURL will not check all the server name or script name details
     * (localhost, api, etc). It only cares about generating the right URL.
     *
     * @param Array $server
     *
     * @return void
     */
    public function assertValidUrl($server, HTTPRequest $request, ?Project $project = null)
    {
        if (! $this->isException($server)) {
            $this->verifyRequest($server);
            $chunks = $this->getUrlChunks();
            if (isset($chunks)) {
                $location = $this->getRedirectionURL($server);
                $this->header($location);
            }

            $current_user = $this->getCurrentUser();
            $url          = $this->getUrl();
            try {
                if (! $current_user->user->isAnonymous()) {
                    $password_expiration_checker = new User_PasswordExpirationChecker();
                    $password_expiration_checker->checkPasswordLifetime($current_user->user);
                }

                if (! $project) {
                    $group_id = (isset($GLOBALS['group_id'])) ? $GLOBALS['group_id'] : $url->getGroupIdFromUrl($server['REQUEST_URI']);
                    if ($group_id) {
                        $project = $this->getProjectManager()->getProject($group_id);
                    }
                }
                if ($project) {
                    $this->userCanAccessProject($current_user->user, $project);
                } else {
                    $this->checkRestrictedAccess($server);
                }

                return true;
            } catch (Project_AccessRestrictedException $exception) {
                if (! isset($project)) {
                    $project = null;
                }
                $this->displayRestrictedUserProjectError($current_user, $project);
            } catch (Project_AccessPrivateException $exception) {
                if (! isset($project)) {
                    $project = null;
                }
                $this->displayPrivateProjectError($current_user, $project);
            } catch (Project_AccessProjectNotFoundException $exception) {
                $layout = $this->getThemeManager()->getBurningParrot($current_user);
                if ($layout === null) {
                    throw new \Exception("Could not load BurningParrot theme");
                }
                (new RequestInstrumentation(Prometheus::instance(), BackendLogger::getDefaultLogger()))->increment(404, DetectedBrowser::detectFromTuleapHTTPRequest($request));
                (new ErrorRendering())->rendersError(
                    $layout,
                    $request,
                    404,
                    _('Not found'),
                    $exception->getMessage()
                );
                exit;
            } catch (Project_AccessDeletedException | AccessNotActiveException $exception) {
                $this->exitError(
                    $GLOBALS['Language']->getText('include_session', 'insufficient_g_access'),
                    $exception->getMessage()
                );
            } catch (ProjectAccessSuspendedException $exception) {
                $this->displaySuspendedProjectError($current_user, $project);
            } catch (User_PasswordExpiredException $exception) {
                if ($server['REQUEST_URI'] === DisplaySecurityController::URL || $server['REQUEST_URI'] === UpdatePasswordController::URL) {
                    return;
                }
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('Please update your password first'));
                $GLOBALS['Response']->redirect(DisplaySecurityController::URL);
            }
        }
    }

    /**
     * Ensure given user can access given project
     *
     * @throws Project_AccessProjectNotFoundException
     * @throws Project_AccessDeletedException
     * @throws Project_AccessRestrictedException
     * @throws Project_AccessPrivateException
     * @throws ProjectAccessSuspendedException
     * @throws AccessNotActiveException
     */
    public function userCanAccessProject(PFUser $user, Project $project): true
    {
        $checker = new ProjectAccessChecker(
            new RestrictedUserCanAccessUrlOrProjectVerifier(
                $this->getEventManager(),
                $this->getUrl(),
                $_SERVER['REQUEST_URI']
            ),
            EventManager::instance()
        );

        $checker->checkUserCanAccessProject($user, $project);

        return true;
    }

    /**
     * Ensure given user can access given project and user is admin of the project
     *
     * @throws Project_AccessProjectNotFoundException
     * @throws Project_AccessDeletedException
     * @throws Project_AccessRestrictedException
     * @throws Project_AccessPrivateException
     * @throws Project_AccessNotAdminException
     * @throws ProjectAccessSuspendedException
     * @throws AccessNotActiveException
     */
    public function userCanAccessProjectAndIsProjectAdmin(PFUser $user, Project $project): void
    {
        if ($this->userCanAccessProject($user, $project)) {
            if (! $user->isAdmin($project->getId())) {
                throw new Project_AccessNotAdminException();
            }
            return;
        }
    }

    /**
     * @throws Project_AccessProjectNotFoundException
     * @throws Project_AccessDeletedException
     * @throws Project_AccessRestrictedException
     * @throws Project_AccessPrivateException
     * @throws Project_AccessNotAdminException
     * @throws ProjectAccessSuspendedException
     * @throws AccessNotActiveException
     */
    public function userCanManageProjectMembership(PFUser $user, Project $project): void
    {
        if ($this->userCanAccessProject($user, $project)) {
            $members_manager_checker = new UserCanManageProjectMembersChecker(
                new MembershipDelegationDao()
            );
            try {
                $members_manager_checker->checkUserCanManageProjectMembers($user, $project);
            } catch (\Tuleap\Project\Admin\ProjectMembers\UserIsNotAllowedToManageProjectMembersException $e) {
                throw new Project_AccessNotAdminException();
            }
        }
    }

    /**
     * Wrapper for tests
     *
     * @param String $title Title of the error message
     * @param String $text  Text of the error message
     *
     * @return Void
     */
    public function exitError($title, $text)
    {
        exit_error($title, $text);
    }

    /**
     * Wrapper for tests
     *
     * @return ProjectManager
     */
    public function getProjectManager()
    {
        return ProjectManager::instance();
    }

    /**
     * Wrapper of header method
     *
     * @param String $location
     *
     * @return void
     */
    public function header($location)
    {
        header('Location: ' . $location);
        exit;
    }

    private function checkUserIsLoggedIn(\Tuleap\User\CurrentUserWithLoggedInInformation $current_user)
    {
        if (! $current_user->is_logged_in) {
            $event_manager = EventManager::instance();
            $redirect      = new URLRedirect($event_manager);
            $redirect->redirectToLogin();
        }
    }

    /**
     * @return ThemeManager
     */
    private function getThemeManager()
    {
        return new ThemeManager(
            new BurningParrotCompatiblePageDetector(
                new Tuleap\Request\CurrentPage(),
                new \User_ForgeUserGroupPermissionsManager(
                    new \User_ForgeUserGroupPermissionsDao()
                )
            )
        );
    }
}
