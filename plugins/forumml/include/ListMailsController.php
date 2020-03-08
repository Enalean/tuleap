<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ForumML;

use Codendi_HTMLPurifier;
use DataAccessObject;
use ForumML_MessageManager;
use HTTPRequest;
use ProjectManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Valid_Pv;
use Valid_String;
use Valid_UInt;
use Valid_WhiteList;

class ListMailsController implements DispatchableWithRequest
{
    /**
     * @var \ForumMLPlugin
     */
    private $plugin;

    public function __construct(\ForumMLPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        include_once __DIR__ . '/forumml_utils.php';
        include_once __DIR__ . '/../../../src/www/mail/mail_utils.php';

        $user = $request->getCurrentUser();

        $vGrp = new Valid_UInt('group_id');
        $vGrp->required();
        if ($request->valid($vGrp)) {
            $group_id = $request->get('group_id');
        } else {
            $group_id = "";
        }

        if (! $this->plugin->isAllowed($group_id)) {
            throw new ForbiddenException();
        }

        $vTopic = new Valid_UInt('topic');
        $vTopic->required();
        if ($request->valid($vTopic)) {
            $topic         = $request->get('topic');
            $fmlMessageMgr = new ForumML_MessageManager();
            $topicSubject  = $fmlMessageMgr->getHeaderValue($topic, FORUMML_SUBJECT);
        } else {
            $topic        = 0;
            $topicSubject = '';
        }

        $vOff = new Valid_UInt('offset');
        $vOff->required();
        if ($request->valid($vOff)) {
            $offset = $request->get('offset');
        } else {
            $offset = 0;
        }

        // Do we need to pure html cache
        $vPurge = new Valid_WhiteList('purge_cache', ['true']);
        $vPurge->required();
        if ($request->valid($vPurge)) {
            $purgeCache = true;
        } else {
            $purgeCache = false;
        }

        // Checks 'list' parameter
        $vList = new Valid_UInt('list');
        $vList->required();
        if (! $request->valid($vList)) {
            exit_error(
                $GLOBALS["Language"]->getText('global', 'error'),
                $GLOBALS["Language"]->getText('plugin_forumml', 'specify_list')
            );
            exit();
        } else {
            $list_id = $request->get('list');
            $project = ProjectManager::instance()->getProject($group_id);
            if (! $user->isMember($group_id) &&
                ($user->isRestricted() || ! mail_is_list_public($list_id) || ! $project->isPublic())
            ) {
                exit_error(
                    $GLOBALS["Language"]->getText('global', 'error'),
                    $GLOBALS["Language"]->getText('include_exit', 'no_perm')
                );
            }
            if (! mail_is_list_active($list_id)) {
                exit_error(
                    $GLOBALS["Language"]->getText('global', 'error'),
                    $GLOBALS["Language"]->getText('plugin_forumml', 'wrong_list')
                );
            }
        }

        // If the list is private, search if the current user is a member of that list. If not, permission denied
        $list_name = mail_get_listname_from_list_id($list_id);
        if (! mail_is_list_public($list_id)) {
            $members = [];
            exec("{$GLOBALS['mailman_bin_dir']}/list_members " . $list_name, $members);
            if (! in_array($user->getEmail(), $members)) {
                exit_permission_denied();
            }
        }

        // Build the mail to be sent
        if ($request->exist('send_reply')) {
            // process the mail
            $ret = plugin_forumml_process_mail(true);
            if ($ret) {
                $layout->addFeedback(
                    'warning',
                    $GLOBALS['Language']->getText(
                        'plugin_forumml',
                        'delay_redirection',
                        [$this->plugin->getThemePath() . "/images/ic/spinner-greenie.gif", $group_id, $list_id, $topic]
                    ),
                    CODENDI_PURIFIER_DISABLED
                );
            }
        }
        $vRep = new Valid_WhiteList('reply', ['1']);
        $vRep->required();
        if ($request->valid($vRep)) {
            $layout->addFeedback(
                'warning',
                $GLOBALS['Language']->getText('plugin_forumml', 'warn_post_without_confirm')
            );
        }

        $params['title'] = util_get_group_name_from_id($group_id) . ' - ForumML - ' . $list_name;
        if ($topicSubject) {
            $params['title'] .= ' - ' . $topicSubject;
        }
        $params['group']  = $group_id;
        $params['toptab'] = 'mail';
        $params['help']   = "collaboration.html#mailing-lists";
        if ($request->valid(new Valid_Pv('pv'))) {
            $params['pv'] = $request->get('pv');
        }
        mail_header($params);

        if ($request->exist('send_reply') && $request->valid($vTopic)) {
            if (isset($ret) && $ret) {
                // wait few seconds before redirecting to archives page
                echo "<script> setTimeout('window.location=\"/plugins/forumml/message.php?group_id=" . $group_id . "&list=" . $list_id . "&topic=" . $topic . "\"',3000) </script>";
            }
        }

        $list_link = '<a href="/plugins/forumml/message.php?group_id=' . $group_id . '&list=' . $list_id . '">' . $list_name . '</a>';
        $title     = $GLOBALS['Language']->getText('plugin_forumml', 'title_root', [$list_link]);
        if ($topic) {
            $fmlMessageMgr = new ForumML_MessageManager();
            $value         = $fmlMessageMgr->getHeaderValue($topic, FORUMML_SUBJECT);
            if ($value) {
                $title = $value;
            }
        } else {
            $title .= ' ' . $GLOBALS['Language']->getText('plugin_forumml', 'list_arch');
        }
        echo '<h2>' . $title . '</h2>';

        $hp              = Codendi_HTMLPurifier::instance();
        $purified_search = '';
        if ($request->exist('search')) {
            $purified_search = $hp->purify($request->get('search'));
        }
        if (! $request->exist('pv') || ($request->exist('pv') && $request->get('pv') == 0)) {
            echo "<table border=0 width=100%>
		<tr>";

            echo "<td align='left'>";
            if ($topic) {
                echo '<a href="/plugins/forumml/message.php?group_id=' . $group_id . '&list=' . $list_id . '">[' . $GLOBALS['Language']->getText('plugin_forumml', 'back_to_list') . ']</a>';
            } else {
                echo "		<a href='/plugins/forumml/index.php?group_id=" . $group_id . "&list=" . $list_id . "'>
					[" . $GLOBALS['Language']->getText('plugin_forumml', 'post_thread') . "]
				</a>";
            }
            echo "</td>";

            echo "
			<td align='right'>
				(<a href='/plugins/forumml/message.php?group_id=" . $group_id . "&list=" . $list_id . "&topic=" . $topic . "&offset=" . $offset . "&search=" . $purified_search . "&pv=1'>
					<img src='" . util_get_image_theme("msg.png") . "' border='0'>&nbsp;" . $GLOBALS['Language']->getText('global', 'printer_version') . "
				</a>)
			</td>
		</tr>
		</table><br>";
        }

        $vSrch = new Valid_String('search');
        $vSrch->required();
        if (! $request->valid($vSrch)) {
            // Check if there are archives to browse
            $qry = sprintf(
                'SELECT NULL' .
                ' FROM plugin_forumml_message' .
                ' WHERE id_list = %d' .
                '        LIMIT 1',
                db_ei($list_id)
            );
            $res = db_query($qry);
            if (db_numrows($res) > 0) {
                // Call to show_thread() function to display the archives
                if (isset($topic) && $topic != 0) {
                    // specific thread
                    plugin_forumml_show_thread($this->plugin, $list_id, $topic, $purgeCache, $user);
                } else {
                    plugin_forumml_show_all_threads($this->plugin, $list_id, $list_name, $offset);
                }
            } else {
                echo "<H2>" . $GLOBALS["Language"]->getText('plugin_forumml', 'empty_archives') . "</H2>";
            }
        } else {
            // search archives
            $dao              = new DataAccessObject();
            $sql              = sprintf(
                'SELECT mh.id_message, mh.value' .
                ' FROM plugin_forumml_message m, plugin_forumml_messageheader mh' .
                ' WHERE mh.id_header = %s' .
                ' AND m.id_list = %d' .
                ' AND m.id_parent = 0' .
                ' AND m.id_message = mh.id_message' .
                ' AND mh.value LIKE %s',
                FORUMML_SUBJECT,
                $dao->da->escapeInt($list_id),
                $dao->da->quoteLikeValueSurround($request->get('search'))
            );
            $result           = $dao->retrieve($sql);
            $number_of_result = 0;
            if ($result !== false) {
                $number_of_result = $result->rowCount();
            }
            echo "<H3>" . $GLOBALS['Language']->getText('plugin_forumml', 'search_result', $purified_search) . " (" . $hp->purify($number_of_result) . " " . $GLOBALS["Language"]->getText('plugin_forumml', 'found') . ")</H3>";
            if ($number_of_result > 0) {
                plugin_forumml_show_search_results($this->plugin, $result, $group_id, $list_id);
            }
        }

        mail_footer($params);
    }
}
