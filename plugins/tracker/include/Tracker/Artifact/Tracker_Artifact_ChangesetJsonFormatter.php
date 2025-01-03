<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

class Tracker_Artifact_ChangesetJsonFormatter
{
    private $renderer;

    public function __construct(TemplateRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function format(Tracker_Artifact_Changeset $changeset, PFUser $current_user)
    {
        return [
            'id'           => $changeset->getId(),
            'submitted_by' => $changeset->getSubmittedBy(),
            'submitted_on' => date('c', (int) $changeset->getSubmittedOn()),
            'email'        => $changeset->getEmail(),
            'html'         => $this->getChangeContentForJson($changeset, $current_user),
        ];
    }

    protected function getChangeContentForJson(Tracker_Artifact_Changeset $changeset, PFUser $current_user)
    {
        return $this->renderer->renderToString(
            'changeset-popup',
            new Tracker_Artifact_ChangesetJsonPresenter($changeset, $current_user)
        );
    }
}
