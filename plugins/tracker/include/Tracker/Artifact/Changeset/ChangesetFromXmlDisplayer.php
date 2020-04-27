<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Artifact\Changeset;

class ChangesetFromXmlDisplayer
{
    /**
     * @var ChangesetFromXmlDao
     */
    private $dao;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \TemplateRenderer
     */
    private $renderer;

    public function __construct(ChangesetFromXmlDao $dao, \UserManager $user_manager, \TemplateRenderer $renderer)
    {
        $this->dao          = $dao;
        $this->user_manager = $user_manager;
        $this->renderer     = $renderer;
    }

    public function display(int $changeset_id): string
    {
        $changeset_import_data = $this->dao->searchChangeset($changeset_id);

        if (! $changeset_import_data) {
            return "";
        }

        $changeset_representation = new ChangesetFromXmlDataRepresentation(
            (int) $changeset_import_data['user_id'],
            (int) $changeset_import_data['timestamp']
        );

        $user = $this->user_manager->getUserById($changeset_representation->getUserId());
        if (! $user) {
            return "";
        }

        return $this->renderer->renderToString(
            'xml_changeset_import',
            new ChangesetFromXmlPresenter($user, $changeset_representation->getTimestamp())
        );
    }
}
