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

namespace Tuleap\Git\Account;

use CSRFSynchronizerToken;
use Git_RemoteServer_GerritServerFactory;
use HTTPRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\Account\UserPreferencesHeader;

final class AccountGerritController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const URL = '/plugins/git/account/gerrit';

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $gerrit_server_factory;

    public function __construct(EventDispatcherInterface $dispatcher, \TemplateRendererFactory $renderer_factory, Git_RemoteServer_GerritServerFactory $gerrit_server_factory)
    {
        $this->renderer   = $renderer_factory->getRenderer(__DIR__ . '/templates');
        $this->dispatcher = $dispatcher;
        $this->gerrit_server_factory = $gerrit_server_factory;
    }

    /**
     * @inheritDoc
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        if (! $this->gerrit_server_factory->hasRemotesSetUp() && count($this->gerrit_server_factory->getRemoteServersForUser($user)) === 0) {
            throw new ForbiddenException();
        }

        $tabs = $this->dispatcher->dispatch(new AccountTabPresenterCollection($user, self::URL));
        assert($tabs instanceof AccountTabPresenterCollection);

        (new UserPreferencesHeader())->display(dgettext('tuleap-git', 'Gerrit'), $layout);
        $this->renderer->renderToPage(
            'gerrit',
            new GerritPresenter(
                self::getCSRFToken(),
                $tabs,
                $this->gerrit_server_factory->getRemoteServersForUser($user)
            )
        );
        $layout->footer([]);
    }

    public static function getCSRFToken(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::URL);
    }
}
