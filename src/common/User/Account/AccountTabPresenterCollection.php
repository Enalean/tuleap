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

    /**
     * @var array<string, AccountTabSection>
     */
    private $sections;
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
        $this->sections     = [
            AccountTabGeneralSection::NAME => new AccountTabGeneralSection([
                new AccountTabPresenter(_('Account'), DisplayAccountInformationController::URL, $current_href),
                new AccountTabPresenter(_('Notifications'), DisplayNotificationsController::URL, $current_href),
                new AccountTabPresenter(_('Appearance & language'), DisplayAppearanceController::URL, $current_href),
                new AccountTabPresenter(_('Edition & CSV'), DisplayEditionController::URL, $current_href),
            ]),
            AccountTabSecuritySection::NAME => new AccountTabSecuritySection([
                new AccountTabPresenter(_('Password'), DisplaySecurityController::URL, $current_href),
                new AccountTabPresenter(_('Keys & tokens'), DisplayKeysTokensController::URL, $current_href),
            ]),
        ];
        $this->current_href = $current_href;
        $this->user         = $user;
    }

    public function current(): mixed
    {
        return current($this->sections);
    }

    public function next(): void
    {
        next($this->sections);
    }

    public function key(): string
    {
        return key($this->sections);
    }

    public function valid(): bool
    {
        return key($this->sections) !== null;
    }

    public function rewind(): void
    {
        reset($this->sections);
    }

    public function add(string $section_name, AccountTabPresenter $tab): void
    {
        if (array_key_exists($section_name, $this->sections)) {
            $this->sections[$section_name]->addTab($tab);
        } else {
            throw new UnexistingTabSectionException($section_name);
        }
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
