<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\User\Account;

use PFUser;
use Tuleap\Event\Dispatchable;

class AccountTabPresenterCollection implements Dispatchable, \Iterator
{
    public const NAME = 'accountTabPresenterCollection';

    private $all_tabs;
    private $i = 0;
    /**
     * @var string
     */
    private $current_href;
    /**
     * @var PFUser
     */
    private $user;

    public function __construct(PFUser $user, string $current_href)
    {
        $this->all_tabs = [
            new AccountTabPresenter(_('Account'), DisplayAccountInformationController::URL, 'fa-address-card-o', $current_href),
            new AccountTabPresenter(_('Security'), DisplaySecurityController::URL, 'fa-lock', $current_href),
            new AccountTabPresenter(_('Notifications'), DisplayNotificationsController::URL, 'fa-bell-o', $current_href),
            new AccountTabPresenter(_('Keys & tokens'), DisplayKeysTokensController::URL, 'fa-key', $current_href),
            new AccountTabPresenter(_('Appearance & language'), DisplayAppearanceController::URL, 'fa-paint-brush', $current_href),
            new AccountTabPresenter(_('Edition & CSV'), DisplayEditionController::URL, 'fa-pencil', $current_href),
            new AccountTabPresenter(_('Experimental'), DisplayExperimentalController::URL, 'fa-flask', $current_href),
        ];
        $this->current_href = $current_href;
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->all_tabs[$this->i];
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        $this->i++;
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->i;
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        return isset($this->all_tabs[$this->i]);
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->i = 0;
    }

    public function add(AccountTabPresenter $tab): void
    {
        $this->all_tabs[] = $tab;
    }

    public function getCurrentHref(): string
    {
        return $this->current_href;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }
}
