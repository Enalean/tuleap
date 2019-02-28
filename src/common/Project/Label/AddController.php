<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Project\Label;

use CSRFSynchronizerToken;
use Exception;
use HTTPRequest;
use ProjectHistoryDao;
use Tuleap\Color\AllowedColorsCollection;

class AddController
{
    /**
     * @var LabelDao
     */
    private $dao;
    /**
     * @var LabelsManagementURLBuilder
     */
    private $url_builder;
    /**
     * @var AllowedColorsCollection
     */
    private $allowed_colors;
    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;

    public function __construct(
        LabelsManagementURLBuilder $url_builder,
        LabelDao $dao,
        ProjectHistoryDao $history_dao,
        AllowedColorsCollection $allowed_colors
    ) {
        $this->url_builder    = $url_builder;
        $this->dao            = $dao;
        $this->history_dao    = $history_dao;
        $this->allowed_colors = $allowed_colors;
    }

    public function add(HTTPRequest $request)
    {
        $project = $request->getProject();
        $url     = $this->url_builder->getURL($project);
        $token   = new CSRFSynchronizerToken($url);
        $token->check();

        $name       = $request->get('name');
        $color      = $request->get('color');
        $is_outline = $request->get('is_outline');

        try {
            $this->checkColor($color);

            $this->dao->addUniqueLabel($project->getID(), $name, $color, $is_outline);
            $this->history_dao->groupAddHistory('label_created', $name, $project->getID());
            $GLOBALS['HTML']->addFeedback(\Feedback::INFO, _('Label has been added.'));
        } catch (LabelWithSameNameAlreadyExistException $exception) {
            $GLOBALS['HTML']->addFeedback(
                \Feedback::ERROR,
                sprintf(
                    _('Cannot create the label "%s" because another one already exists with same name.'),
                    $name
                )
            );
        } catch (Exception $exception) {
            $GLOBALS['HTML']->addFeedback(\Feedback::ERROR, _('An error occurred while trying to add the label.'));
        }
        $GLOBALS['HTML']->redirect($url);
    }

    private function checkColor($new_color)
    {
        if (! $this->allowed_colors->isColorAllowed($new_color)) {
            throw new InvalidColorException();
        }
    }
}
