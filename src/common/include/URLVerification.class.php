<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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

use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Request\RestrictedUsersAreHandledByPluginEvent;

/**
 * Check the URL validity (protocol, host name, query) regarding server constraints
 * (anonymous, user status, project privacy, ...) and manage redirection when needed  
 */
class URLVerification {

    protected $urlChunks = null;

    /**
     * Returns an array containing data for the redirection URL
     *
     * @return Array
     */
    function getUrlChunks() {
        return $this->urlChunks;
    }

    /**
     * Returns the current user
     *
     * @return PFUser
     */
    function getCurrentUser() {
        return UserManager::instance()->getCurrentUser();
    }

    /**
     * Returns an instance of EventManager
     *
     * @return EventManager
     */
    public function getEventManager() {
        return EventManager::instance();
    }

    /**
     * Returns a instance of Url
     *
     * @return Url
     */
    function getUrl() {
        return new Url();
    }

    /**
     * @return PermissionsOverrider_PermissionsOverriderManager
     */
    protected function getPermissionsOverriderManager() {
        return PermissionsOverrider_PermissionsOverriderManager::instance();
    }

    /**
     * Tests if the requested script name is allowed for anonymous or not
     *
     * @param Array $server
     *
     * @return Boolean
     */
    function isScriptAllowedForAnonymous($server) {
        // Defaults
        $allowedAnonymous['/current_css.php']            = true;
        $allowedAnonymous['/account/login.php']          = true;
        $allowedAnonymous['/account/register.php']       = true;
        $allowedAnonymous['/account/change_pw.php']      = true;
        $allowedAnonymous['/include/check_pw.php']       = true;
        $allowedAnonymous['/account/lostpw.php']         = true;
        $allowedAnonymous['/account/lostlogin.php']      = true;
        $allowedAnonymous['/account/lostpw-confirm.php'] = true;
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
        $params = array('script_name' => $server['SCRIPT_NAME'], 'anonymous_allowed' => &$anonymousAllowed);
        $this->getEventManager()->processEvent('anonymous_access_to_script_allowed', $params);

        return $anonymousAllowed;
    }

    /**
     * Should we treat current request as an exception
     *
     * @param array $server
     *
     * @return Boolean
     */
    function isException($server) {
        return preg_match('`^(?:/plugins/[^/]+)?/(?:soap|api)/`', $server['SCRIPT_NAME']);
    }

    /**
     * Tests if the server name is valid or not
     *
     * @param Array $server
     * @param String $host
     *
     * @return Boolean
     */
    function isValidServerName($server, $host) {

        return ($server['HTTP_HOST'] == $host);
    }

    /**
     * Check if an URI is internal to the application or not. We reject all URLs
     * except /path/to/feature
     *
     * @return boolean
     */
    public function isInternal($uri)
    {
        $url_decoded = urldecode($uri);
        return preg_match('/^\/[[:alnum:]]+/', $url_decoded) === 1;
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
    function getRedirectionURL(HTTPRequest $request, $server) {
        $chunks   = $this->getUrlChunks($server);

        $location = $this->getRedirectLocation($request, $server, $chunks);

        if (isset($chunks['script'])) {
            $location .= $chunks['script'];
        } else {
            $location .= $server['REQUEST_URI'];
        }
        return $location;
    }

    private function getRedirectLocation(HTTPRequest $request, array $server, array $chunks) {
        if (isset($chunks['protocol']) || isset($chunks['host'])) {
            return $this->rewriteProtocol($request, $server, $chunks);
        }
        return '';
    }

    private function rewriteProtocol(HTTPRequest $request, array $server, array $chunks) {
        if (isset($chunks['protocol'])) {
            $location = $chunks['protocol']."://";
        } else {
            if ($request->isSecure()) {
                $location = "https://";
            } else {
                $location = "http://";
            }
        }

        if (isset($chunks['host'])) {
            $location .= $chunks['host'];
        } else {
            $location .= $server['HTTP_HOST'];
        }

        return $location;
    }

    /**
     * Modify the protocol entry if needed
     *
     * @param Array $server
     *
     * @return void
     */
    public function verifyProtocol(HTTPRequest $request) {
        if (! $request->isSecure() && ForgeConfig::get('sys_https_host')) {
            $this->urlChunks['protocol'] = 'https';
            $this->urlChunks['host']     = ForgeConfig::get('sys_https_host');
        }
    }

    /**
     * Check if anonymous is granted to access else redirect to login page
     *
     * @param Array $server
     *
     * @return void
     */
    public function verifyRequest($server) {
        $user = $this->getCurrentUser();

        if (
            $this->doesPlatformRequireLogin() &&
            $user->isAnonymous() &&
            ! $this->isScriptAllowedForAnonymous($server)
        ) {
            $redirect = new URLRedirect($this->getEventManager());
            $this->urlChunks['script']   = $redirect->buildReturnToLogin($server);
        }
    }

    public function doesPlatformRequireLogin() {
        $anonymous_user = new PFUser(array('user_id' => 0));
        if (ForgeConfig::areAnonymousAllowed() && ! $this->getPermissionsOverriderManager()->doesOverriderForceUsageOfAnonymous()) {
            return false;
        } elseif ($this->getPermissionsOverriderManager()->doesOverriderAllowUserToAccessPlatform($anonymous_user)) {
            return false;
        }
        return true;
    }

    /**
     * Checks that a restricted user can access the requested URL.
     *
     * @param Array $server
     *
     * @return void
     */
    function checkRestrictedAccess($server) {
        $user = $this->getCurrentUser();
        if ($user->isRestricted()) {
            $url = $this->getUrl();
            if (!$this->restrictedUserCanAccessUrl($user, $url, $server['REQUEST_URI'], null)) {
                $this->displayRestrictedUserError($url, $user);
            }
        }
    }

    /**
     * Test if given url is restricted for user
     *
     * @param PFUser $user
     * @param Url $url
     * @param String $request_uri
     * @return Boolean False if user not allowed to see the content
     */
    protected function restrictedUserCanAccessUrl($user, $url, $request_uri, Project $project = null) {
        // This assume that we already checked that project is accessible to restricted prior to function call.
        // Hence, summary page is ALWAYS accessible
        if (strpos($request_uri, '/projects/') !== false) {
            return true;
        }

        if ($project !== null) {
            $group_id = $project->getID();
        } else {
            $group_id =  (isset($GLOBALS['group_id'])) ? $GLOBALS['group_id'] : $url->getGroupIdFromUrl($request_uri);
        }

        // Make sure the URI starts with a single slash
        $req_uri='/'.trim($request_uri, "/");
        $user_is_allowed=false;
        /* Examples of input params:
         Script: /projects, Uri=/projects/ljproj/
         Script: /project/admin/index.php, Uri=/project/admin/?group_id=101
         Script: /tracker/index.php, Uri=/tracker/index.php?group_id=101
         Script: /tracker/index.php, Uri=/tracker/?func=detail&aid=14&atid=101&group_id=101
        */

        // Restricted users cannot access any page belonging to a project they are not a member of.
        // In addition, the following URLs are forbidden (value overriden in site-content file)
        $forbidden_url = array( 
          '/new/',        // list of the newest releases made on the Codendi site ('/news' must be allowed...)
          '/project/register.php',    // Register a new project
          '/export',      // Codendi XML feeds
          '/info.php'     // PHP info
          );
        // Default values are very restrictive, but they can be overriden in the site-content file
        // Default support project is project 1.
        $allow_welcome_page=false;       // Allow access to welcome page 
        $allow_news_browsing=false;      // Allow restricted users to read/comment news, including for their project
        $allow_user_browsing=false;      // Allow restricted users to access other user's page (Developer Profile)
        $allow_access_to_project_forums      = array(1); // Support project help forums are accessible through the 'Discussion Forums' link
        $allow_access_to_project_trackers    = array(1); // Support project trackers are used for support requests
        $allow_access_to_project_docs        = array(1); // Support project documents and wiki (Note that the User Guide is always accessible)
        $allow_access_to_project_mail        = array(1); // Support project mailing lists (Developers Channels)
        $allow_access_to_project_frs         = array(1); // Support project file releases
        $allow_access_to_project_refs        = array(1); // Support project references
        $allow_access_to_project_news        = array(1); // Support project news
        $allow_access_to_project_trackers_v5 = array(1); //Support project trackers v5 are used for support requests
        // List of fully public projects (same access for restricted and unrestricted users)

        // Customizable security settings for restricted users:
        include($GLOBALS['Language']->getContent('include/restricted_user_permissions','en_US'));
        // End of customization
        
        // For convenient reasons, admin can customize those variables as arrays
        // but for performances reasons we prefer to use hashes (avoid in_array)
        // so we transform array(101) => array(101=>0)
        $allow_access_to_project_forums      = array_flip($allow_access_to_project_forums);
        $allow_access_to_project_trackers    = array_flip($allow_access_to_project_trackers);
        $allow_access_to_project_docs        = array_flip($allow_access_to_project_docs);
        $allow_access_to_project_mail        = array_flip($allow_access_to_project_mail);
        $allow_access_to_project_frs         = array_flip($allow_access_to_project_frs);
        $allow_access_to_project_refs        = array_flip($allow_access_to_project_refs);
        $allow_access_to_project_news        = array_flip($allow_access_to_project_news);
        $allow_access_to_project_trackers_v5 = array_flip($allow_access_to_project_trackers_v5);

        foreach ($forbidden_url as $str) {
            $pos = strpos($req_uri, $str);
            if ($pos === false) {
                // Not found
            } else {
                if ($pos == 0) {
                    // beginning of string
                    return false;
                }
            }
        }

        // Welcome page
        if (! $allow_welcome_page && $request_uri === '/') {
            return false;
        }

        //Forbid search unless it's on a tracker
        if (strpos($req_uri,'/search') === 0 && isset($_REQUEST['type_of_search']) && $_REQUEST['type_of_search'] == 'tracker') {
            return true;
        } elseif( strpos($req_uri,'/search') === 0 ) {
            return false;
        }

        // Forbid access to other user's page (Developer Profile)
        if ((strpos($req_uri,'/users/') === 0)&&(!$allow_user_browsing)) {
            if ($req_uri != '/users/'.$user->getName() && $req_uri != '/users/'.$user->getName().'/avatar.png') {
                return false;
            }
        }

        // Forum and news. Each published news is a special forum of project 'news'
        if (strpos($req_uri,'/news/') === 0 &&
            isset($allow_access_to_project_news[$group_id])) {
            $user_is_allowed=true;
        }
        
        if (strpos($req_uri,'/news/') === 0 && 
            $allow_news_browsing) {
            $user_is_allowed=true;
         }
        
        if (strpos($req_uri,'/forum/') === 0 &&
            isset($allow_access_to_project_forums[$group_id])) {
              $user_is_allowed=true;
         }

        // Codendi trackers
        if (strpos($req_uri,'/tracker/') === 0 && 
            isset($allow_access_to_project_trackers[$group_id])) {
            $user_is_allowed=true;
        }

        // Trackers v5
        if (strpos($req_uri,'/plugins/tracker/') === 0 &&
            isset($allow_access_to_project_trackers_v5[$group_id])) {
            $user_is_allowed=true;
        }

        // Codendi documents and wiki
        if (((strpos($req_uri,'/docman/') === 0) || 
            (strpos($req_uri,'/plugins/docman/') === 0) ||
            (strpos($req_uri,'/wiki/') === 0)) &&
            isset($allow_access_to_project_docs[$group_id])) {
            $user_is_allowed=true;
        }

        // Codendi mailing lists page
        if (strpos($req_uri,'/mail/') === 0 &&
            isset($allow_access_to_project_mail[$group_id])) {
            $user_is_allowed=true;
        }
        
        // Codendi file releases
        if (strpos($req_uri,'/file/') === 0 &&
            isset($allow_access_to_project_frs[$group_id])) {
            $user_is_allowed=true;
        }
        
        // References
        if (strpos($req_uri,'/goto') === 0 &&
            isset($allow_access_to_project_refs[$group_id])) {
            $user_is_allowed=true;
        }

        if (! $user_is_allowed) {
            $event = new RestrictedUsersAreHandledByPluginEvent($request_uri);
            $this->getEventManager()->processEvent($event);
            $user_is_allowed = $event->getPluginHandleRestricted();
        }

        if ($group_id && ! $user_is_allowed) {
            if (in_array($group_id, ForgeConfig::getSuperPublicProjectsFromRestrictedFile())) {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * Display error message for restricted user.
     *
     * @param URL $url Accessed url
     * 
     * @return void
     */
    function displayRestrictedUserError(URL $url, PFUser $user, Project $project = null) {
        $error = new Error_PermissionDenied_RestrictedUser($url);
        $error->buildInterface($user, $project);
        exit;
    }
    
    /**
     * Display error message for restricted project
     *
     * @param URL $url Accessed url
     * 
     * @return void
     */
    function displayPrivateProjectError(URL $url, PFUser $user, Project $project = null) {
        $GLOBALS['Response']->send401UnauthorizedHeader();
        $sendMail = new Error_PermissionDenied_PrivateProject($url);
        $sendMail->buildInterface($user, $project);
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
    public function assertValidUrl($server, HTTPRequest $request, Project $project = null) {
        if (!$this->isException($server)) {
            $this->verifyProtocol($request);
            $this->verifyRequest($server);
            $chunks = $this->getUrlChunks();
            if (isset($chunks)) {
                $location = $this->getRedirectionURL($request, $server);
                $this->header($location);
            }

            $user = $this->getCurrentUser();
            $url  = $this->getUrl();
            try {
                if (! $user->isAnonymous()) {
                    $password_expiration_checker = new User_PasswordExpirationChecker();
                    $password_expiration_checker->checkPasswordLifetime($user);
                }

                if (! $project) {
                    $group_id = (isset($GLOBALS['group_id'])) ? $GLOBALS['group_id'] : $url->getGroupIdFromUrl($server['REQUEST_URI']);
                    if ($group_id) {
                        $project = $this->getProjectManager()->getProject($group_id);
                    }
                }
                if ($project) {
                    $this->userCanAccessProject($user, $project);
                } else {
                    $this->checkRestrictedAccess($server);
                }

                return true;

            } catch (Project_AccessRestrictedException $exception) {
                if (! isset($project)) {
                    $project = null;
                }
                $this->displayRestrictedUserError($url, $user, $project);
            } catch (Project_AccessPrivateException $exception) {
                if (! isset($project)) {
                    $project = null;
                }
                $this->displayPrivateProjectError($url, $user, $project);
            } catch (Project_AccessProjectNotFoundException $exception) {
                $this->exitError(
                    $GLOBALS['Language']->getText('include_html','g_not_exist'),
                    $exception->getMessage()
                );
            } catch (Project_AccessDeletedException $exception) {
                $this->exitError(
                    $GLOBALS['Language']->getText('include_session','insufficient_g_access'),
                    $exception->getMessage()
                );
            } catch (User_PasswordExpiredException $exception) {
                if (! $this->isPageAllowedWhenPasswordExpired($server)) {
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('include_account', 'change_pwd_err'));
                    $GLOBALS['Response']->redirect('/account/change_pw.php?user_id'.$user->getId());
                }
            }
        }
    }

    private function isPageAllowedWhenPasswordExpired($server) {
        return $this->isLogoutPage($server) || $this->isScriptAllowedForAnonymous($server);
    }

    private function isLogoutPage($server) {
        return isset($server['SCRIPT_NAME']) && $server['SCRIPT_NAME'] == '/account/logout.php';
    }

    /**
     * Ensure given user can access given project
     *
     * @param PFUser  $user
     * @param Project $project
     * @return boolean
     * @throws Project_AccessProjectNotFoundException
     * @throws Project_AccessDeletedException
     * @throws Project_AccessRestrictedException
     * @throws Project_AccessPrivateException
     */
    public function userCanAccessProject(PFUser $user, Project $project) {
        if ($project->isError()) {
            throw new Project_AccessProjectNotFoundException();
        } elseif ($user->isSuperUser()) {
            return true;
        } elseif (! $project->isActive()) {
            throw new Project_AccessDeletedException($project);
        } elseif ($user->isMember($project->getID())) {
            return true;
        } elseif ($this->getPermissionsOverriderManager()->doesOverriderAllowUserToAccessProject($user, $project)) {
            return true;
        } elseif ($user->isRestricted()) {
            if ( ! $project->allowsRestricted() ||
                ! $this->restrictedUserCanAccessUrl($user, $this->getUrl(), $_SERVER['REQUEST_URI'], $project)) {
                throw new Project_AccessRestrictedException();
            }
            return true;
        } elseif ($project->isPublic()) {
            return true;
        } elseif ($this->userHasBeenDelegatedAccess($user)) {
            return true;
        }
        throw new Project_AccessPrivateException();
    }

    private function userHasBeenDelegatedAccess(PFUser $user) {
        $can_access    = false;
        $event_manager = EventManager::instance();

        $event_manager->processEvent(
            Event::HAS_USER_BEEN_DELEGATED_ACCESS,
            array(
                'can_access' => &$can_access,
                'user'       => $user,
            )
        );

        return $can_access;
    }

    /**
     * Ensure given user can access given project and user is admin of the project
     *
     * @param PFUser  $user
     * @param Project $project
     * @return boolean
     *
     * @throws Project_AccessProjectNotFoundException
     * @throws Project_AccessDeletedException
     * @throws Project_AccessRestrictedException
     * @throws Project_AccessPrivateException
     * @throws Project_AccessNotAdminException
     */
    public function userCanAccessProjectAndIsProjectAdmin(PFUser $user, Project $project) {
        if ($this->userCanAccessProject($user, $project)) {
            if (! $user->isAdmin($project->getId())) {
                throw new Project_AccessNotAdminException();
            }
            return true;
        }
    }

    /**
     * @param PFUser  $user
     * @param Project $project
     * @return boolean
     *
     * @throws Project_AccessProjectNotFoundException
     * @throws Project_AccessDeletedException
     * @throws Project_AccessRestrictedException
     * @throws Project_AccessPrivateException
     * @throws Project_AccessNotAdminException
     */
    public function userCanManageProjectMembership(PFUser $user, Project $project)
    {
        if ($this->userCanAccessProject($user, $project)) {
            $dao = new MembershipDelegationDao();
            if (! $user->isAdmin($project->getId()) && ! $dao->doesUserHasMembershipDelegation($user->getId(), $project->getID())) {
                throw new Project_AccessNotAdminException();
            }
            return true;
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
    function exitError($title, $text) {
        exit_error($title, $text);
    }

    /**
     * Wrapper for tests
     *
     * @return ProjectManager
     */
    function getProjectManager() {
        return ProjectManager::instance();
    }

    /**
     * Wrapper of header method
     *
     * @param String $location
     *
     * @return void
     */
    function header($location) {
        header('Location: '.$location);
        exit;
    }

}
