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

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Valid_UInt;

class WriteMailController implements DispatchableWithRequest
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

        if ($request->valid(new Valid_UInt('group_id'))) {
            $group_id = $request->get('group_id');
        } else {
            $group_id = "";
        }

        if (! $this->plugin->isAllowed($group_id)) {
            throw new ForbiddenException();
        }

        // Checks 'list' parameter
        if (! $request->valid(new Valid_UInt('list'))) {
            exit_error(
                $GLOBALS["Language"]->getText('global', 'error'),
                dgettext('tuleap-forumml', 'You must specify the mailing-list id.')
            );
        } else {
            $list_id = $request->get('list');
            if (! user_isloggedin() || (! mail_is_list_public($list_id) && ! user_ismember($group_id))) {
                exit_error(
                    $GLOBALS["Language"]->getText('include_exit', 'info'),
                    $GLOBALS["Language"]->getText('include_exit', 'mail_list_no_perm')
                );
            }
            if (! mail_is_list_active($list_id)) {
                exit_error(
                    $GLOBALS["Language"]->getText('global', 'error'),
                    dgettext('tuleap-forumml', 'The mailing-list does not exist or is inactive.')
                );
            }
        }

        $params['title'] = 'ForumML';
        $params['group'] = $group_id;
        $params['toptab'] = 'mail';
        $params['help'] = "collaboration.html#mailing-lists";
        mail_header($params);

        $purifier = \Codendi_HTMLPurifier::instance();

        $list_link = '<a href="/plugins/forumml/message.php?group_id=' . $purifier->purify(urlencode((string) $group_id)) . '&list=' . $purifier->purify(urlencode($list_id)) . '">' . mail_get_listname_from_list_id($list_id) . '</a>';
        echo '<H2><b>' . sprintf(dgettext('tuleap-forumml', 'Mailing-List \'%1$s\' - New Thread'), $list_link) . '</b></H2>
	<a href="/plugins/forumml/message.php?group_id=' . $purifier->purify(urlencode((string) $group_id)) . '&list=' . $purifier->purify(urlencode($list_id)) . '">[' . dgettext('tuleap-forumml', 'Browse Archives') . ']</a><br><br>
	<H3><b>' . dgettext('tuleap-forumml', 'Submit a new Thread:') . '</b></H3>';

        $assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../../src/www/assets/forumml', '/assets/forumml');
        // New thread form
        echo $assets->getHTMLSnippet('forumml.js');
        echo "<form name='form' method='post' enctype='multipart/form-data'>
	<table>
    <tr>
		<td valign='top' align='left'><b> " . dgettext('tuleap-forumml', 'Subject') . ":&nbsp;</b></td>
		<td align='left'><input type=text name='subject' size='80'></td>
	</tr></table>";
        echo '<table>
    <tr>
		<td align="left">
			<p><a href="javascript:;" onclick="addHeader(\'\',\'\',1);">[' . dgettext('tuleap-forumml', 'Add cc') . ']</a>
			 - <a href="javascript:;" onclick="addHeader(\'\',\'\',2);">[' . dgettext('tuleap-forumml', 'Attach file') . ']</a></p>
			<input type="hidden" value="0" id="header_val" />
			<div id="mail_header"></div></td></tr></table>';
        echo "<table><tr>
			<td valign='top' align='left'><b>" . dgettext('tuleap-forumml', 'Message:') . "&nbsp;</b></td>
			<td align='left'><textarea rows='20' cols='100' name='message'></textarea></td>
		</tr>
		<tr>
			<td></td>
			<td><input type='submit' name='post' value='" . $GLOBALS['Language']->getText('global', 'btn_submit') . "'>
				<input type='reset' value='" . dgettext('tuleap-forumml', 'Erase') . "'></td>
		</tr>
	</table></form>";

        mail_footer($params);
    }
}
