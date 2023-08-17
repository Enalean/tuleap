<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Project;

use ForgeConfig;
use PFUser;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Request\RestrictedUsersAreHandledByPluginEvent;
use URL;

class RestrictedUserCanAccessUrlOrProjectVerifier implements RestrictedUserCanAccessVerifier
{
    /**
     * @var EventDispatcherInterface
     */
    private $event_manager;
    /**
     * @var URL
     */
    private $url;
    /**
     * @var string
     */
    private $request_uri;

    public function __construct(EventDispatcherInterface $event_manager, URL $url, string $request_uri)
    {
        $this->event_manager = $event_manager;
        $this->url           = $url;
        $this->request_uri   = $request_uri;
    }

    public function isRestrictedUserAllowedToAccess(PFUser $user, ?Project $project = null): bool
    {
        // This assume that we already checked that project is accessible to restricted prior to function call.
        // Hence, summary page is ALWAYS accessible
        if (strpos($this->request_uri, '/projects/') !== false) {
            return true;
        }

        if ($project !== null) {
            $group_id = $project->getID();
        } else {
            $group_id = (isset($GLOBALS['group_id'])) ? $GLOBALS['group_id'] : $this->url->getGroupIdFromUrl($this->request_uri);
        }

        // Make sure the URI starts with a single slash
        $req_uri         = '/' . trim($this->request_uri, "/");
        $user_is_allowed = false;
        /* Examples of input params:
         Script: /projects, Uri=/projects/ljproj/
         Script: /project/admin/index.php, Uri=/project/admin/?group_id=101
         Script: /tracker/index.php, Uri=/tracker/index.php?group_id=101
         Script: /tracker/index.php, Uri=/tracker/?func=detail&aid=14&atid=101&group_id=101
        */

        // Restricted users cannot access any page belonging to a project they are not a member of.
        // In addition, the following URLs are forbidden (value overriden in site-content file)
        $forbidden_url = [
            '/new/',        // list of the newest releases made on the Codendi site ('/news' must be allowed...)
            '/project/register.php',    // Register a new project
            '/export',      // Codendi XML feeds
            '/info.php',     // PHP info
        ];
        // Default values are very restrictive, but they can be overriden in the site-content file
        // Default support project is project 1.
        $allow_welcome_page                  = false;       // Allow access to welcome page
        $allow_news_browsing                 = false;      // Allow restricted users to read/comment news, including for their project
        $allow_user_browsing                 = false;      // Allow restricted users to access other user's page (Developer Profile)
        $allow_access_to_project_forums      = [1]; // Support project help forums are accessible through the 'Discussion Forums' link
        $allow_access_to_project_trackers    = [1]; // Support project trackers are used for support requests
        $allow_access_to_project_docs        = [1]; // Support project documents and wiki (Note that the User Guide is always accessible)
        $allow_access_to_project_mail        = [1]; // Support project mailing lists (Developers Channels)
        $allow_access_to_project_frs         = [1]; // Support project file releases
        $allow_access_to_project_refs        = [1]; // Support project references
        $allow_access_to_project_news        = [1]; // Support project news
        $allow_access_to_project_trackers_v5 = [1]; //Support project trackers v5 are used for support requests
        // List of fully public projects (same access for restricted and unrestricted users)

        // Customizable security settings for restricted users:
        include($GLOBALS['Language']->getContent('include/restricted_user_permissions', 'en_US'));
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
        if (! $allow_welcome_page && $this->request_uri === '/') {
            return false;
        }

        //Forbid search unless it's on a tracker
        if (strpos($req_uri, '/search') === 0 && isset($_REQUEST['type_of_search']) && $_REQUEST['type_of_search'] == 'tracker') {
            return true;
        } elseif (strpos($req_uri, '/search') === 0) {
            return false;
        }

        // Forbid access to other user's page (Developer Profile)
        if ((strpos($req_uri, '/users/') === 0) && (! $allow_user_browsing)) {
            if ($req_uri != '/users/' . $user->getUserName()) {
                return false;
            }
        }

        // Forum and news. Each published news is a special forum of project 'news'
        if (
            strpos($req_uri, '/news/') === 0 &&
            isset($allow_access_to_project_news[$group_id])
        ) {
            $user_is_allowed = true;
        }

        if (
            strpos($req_uri, '/news/') === 0 &&
            $allow_news_browsing
        ) {
            $user_is_allowed = true;
        }

        if (
            strpos($req_uri, '/forum/') === 0 &&
            isset($allow_access_to_project_forums[$group_id])
        ) {
            $user_is_allowed = true;
        }

        // Codendi trackers
        if (
            strpos($req_uri, '/tracker/') === 0 &&
            isset($allow_access_to_project_trackers[$group_id])
        ) {
            $user_is_allowed = true;
        }

        // Trackers v5
        if (
            strpos($req_uri, '/plugins/tracker/') === 0 &&
            isset($allow_access_to_project_trackers_v5[$group_id])
        ) {
            $user_is_allowed = true;
        }

        // Codendi documents and wiki
        if (
            ((strpos($req_uri, '/docman/') === 0) ||
                (strpos($req_uri, '/plugins/docman/') === 0) ||
                (strpos($req_uri, '/wiki/') === 0)) &&
            isset($allow_access_to_project_docs[$group_id])
        ) {
            $user_is_allowed = true;
        }

        // Codendi file releases
        if (
            strpos($req_uri, '/file/') === 0 &&
            isset($allow_access_to_project_frs[$group_id])
        ) {
            $user_is_allowed = true;
        }

        // References
        if (
            strpos($req_uri, '/goto') === 0 &&
            isset($allow_access_to_project_refs[$group_id])
        ) {
            $user_is_allowed = true;
        }

        if (! $user_is_allowed) {
            $event           = $this->event_manager->dispatch(new RestrictedUsersAreHandledByPluginEvent($this->request_uri));
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
}
