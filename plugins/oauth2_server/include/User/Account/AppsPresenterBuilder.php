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

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\User\Account\AccountTabPresenterCollection;

class AppsPresenterBuilder
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var AppFactory
     */
    private $app_factory;

    public function __construct(EventDispatcherInterface $dispatcher, AppFactory $app_factory)
    {
        $this->dispatcher  = $dispatcher;
        $this->app_factory = $app_factory;
    }

    public function build(\PFUser $user): AppsPresenter
    {
        $tabs = $this->dispatcher->dispatch(new AccountTabPresenterCollection($user, AccountAppsController::URL));
        assert($tabs instanceof AccountTabPresenterCollection);

        $app_presenters = [];
        $apps = $this->app_factory->getAppsAuthorizedByUser($user);
        foreach ($apps as $app) {
            $app_presenters[] = new AccountAppPresenter($app->getId(), $app->getName(), $app->getProject()->getPublicName());
        }

        return new AppsPresenter($tabs, ...$app_presenters);
    }
}
