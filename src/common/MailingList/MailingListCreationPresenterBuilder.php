<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\MailingList;

class MailingListCreationPresenterBuilder
{
    /**
     * @var \MailingListDao
     */
    private $dao;
    /**
     * @var \Codendi_HTMLPurifier
     */
    private $purifier;

    public function __construct(\MailingListDao $dao, \Codendi_HTMLPurifier $purifier)
    {
        $this->dao      = $dao;
        $this->purifier = $purifier;
    }

    public function build(
        \Project $project,
        \PFUser $current_user,
        \CSRFSynchronizerToken $csrf,
        string $sys_lists_domain,
        string $intro,
    ): MailingListCreationPresenter {
        $existing_lists = [];
        foreach ($this->dao->searchByProject((int) $project->getID()) as $row) {
            $existing_lists[] = $row['list_name'];
        }

        $default_name_value = '';
        $list_prefix        = \ForgeConfig::get('sys_lists_prefix') . $project->getUnixName() . '-';
        if ($current_user->isSuperUser()) {
            $default_name_value = $list_prefix . 'xxxxx';
            $list_prefix        = '';
        }

        return new MailingListCreationPresenter(
            (int) $project->getID(),
            $csrf,
            $sys_lists_domain,
            $list_prefix,
            $existing_lists,
            $this->purifier->purify($intro, \Codendi_HTMLPurifier::CONFIG_LIGHT),
            $default_name_value,
            MailingListCreationController::getUrl($project),
        );
    }
}
