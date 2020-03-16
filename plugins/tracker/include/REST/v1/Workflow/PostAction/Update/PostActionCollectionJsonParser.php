<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction\Update;

use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\IncompatibleWorkflowModeException;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\Update\PostAction;
use Workflow;

/**
 * Json parser which produces a PostActionCollection.
 */
class PostActionCollectionJsonParser
{
    /**
     * @var PostActionUpdateJsonParser[]
     */
    private $action_parsers;

    public function __construct(PostActionUpdateJsonParser ...$action_parsers)
    {
        $this->action_parsers = $action_parsers;
    }

    /**
     * @throws I18NRestException 400
     * @throws IncompatibleWorkflowModeException
     */
    public function parse(Workflow $workflow, array $json): PostActionCollection
    {
        $post_actions = [];
        foreach ($json as $post_action_json) {
            if (!is_array($post_action_json)) {
                throw new I18NRestException(
                    400,
                    sprintf(
                        dgettext('tuleap-tracker', "Bad format: '%s'. Array expected"),
                        json_encode($post_action_json)
                    )
                );
            }
            $post_actions[] = $this->parsePostAction($workflow, $post_action_json);
        }
        return new PostActionCollection(...$post_actions);
    }

    /**
     * @throws I18NRestException 400
     * @throws IncompatibleWorkflowModeException
     */
    private function parsePostAction(Workflow $workflow, array $json): PostAction
    {
        foreach ($this->action_parsers as $parser) {
            if ($parser->accept($json)) {
                return $parser->parse($workflow, $json);
            }
        }
        throw new I18NRestException(
            400,
            sprintf(
                dgettext('tuleap-tracker', "Unknown post action '%s'."),
                json_encode($json)
            )
        );
    }
}
