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

use ForumML_Attachment;
use GuzzleHttp\Psr7\ServerRequest;
use HTTPRequest;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Valid_UInt;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;

class OutputAttachmentController implements DispatchableWithRequest
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
        include_once __DIR__ . '/../../../src/www/mail/mail_utils.php';

        $groupId = $request->getValidated('group_id', 'UInt', 0);

        if (! $this->plugin->isAllowed($groupId)) {
            throw new ForbiddenException();
        }

        $vList = new Valid_UInt('list');
        $vList->required();
        // Checks 'list' parameter
        if (! $request->valid($vList)) {
            exit_error(
                $GLOBALS["Language"]->getText('global', 'error'),
                $GLOBALS["Language"]->getText('plugin_forumml', 'specify_list')
            );
        } else {
            $list_id = $request->get('list');
            if (! user_isloggedin() || (! mail_is_list_public($list_id) && ! user_ismember($groupId))) {
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

        // Topic
        $vTopic = new Valid_UInt('topic');
        $vTopic->required();
        if ($request->valid($vTopic)) {
            $topic = $request->get('topic');
        } else {
            $topic = 0;
        }

        $attchmentId = $request->getValidated('id', 'UInt', 0);
        if ($attchmentId) {
            $fmlAttch = new ForumML_Attachment();
            $attch    = $fmlAttch->getById($attchmentId);
            if ($attch && file_exists($attch['file_path'])) {
                $response_builder = new BinaryFileResponseBuilder(
                    HTTPFactoryBuilder::responseFactory(),
                    HTTPFactoryBuilder::streamFactory()
                );
                $response         = $response_builder->fromFilePath(
                    ServerRequest::fromGlobals(),
                    $attch['file_path'],
                    $attch['file_name'],
                    $attch['type']
                );
                (new SapiStreamEmitter())->emit($response);
                exit();
            }
            $layout->addFeedback('error', $GLOBALS["Language"]->getText('plugin_forumml', 'attchment_not_found'));
        } else {
            $layout->addFeedback('error', $GLOBALS["Language"]->getText('plugin_forumml', 'missing_param'));
        }
        $layout->redirect(
            '/plugins/forumml/message.php?group_id=' . $groupId . '&list=' . $list_id . '&topic=' . $topic
        );
    }
}
