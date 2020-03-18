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
 */

declare(strict_types=1);

namespace Tuleap\OAuth2Server\User\Account;

use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\User\Account\AccountTabPresenterCollection;

final class AppsPresenter
{
    /**
     * @var CSRFSynchronizerTokenPresenter
     *
     * @psalm-readonly
     */
    public $csrf_token;
    /**
     * @var AccountTabPresenterCollection
     *
     * @psalm-readonly
     */
    public $tabs;
    /**
     * @var AccountAppPresenter[]
     *
     * @psalm-readonly
     */
    public $apps;
    /**
     * @var bool
     *
     * @psalm-readonly
     */
    public $has_apps;

    public function __construct(
        CSRFSynchronizerTokenPresenter $csrf_token,
        AccountTabPresenterCollection $tabs,
        AccountAppPresenter ...$app_presenters
    ) {
        $this->csrf_token = $csrf_token;
        $this->tabs       = $tabs;
        $this->apps       = $app_presenters;
        $this->has_apps   = count($this->apps) > 0;
    }
}
