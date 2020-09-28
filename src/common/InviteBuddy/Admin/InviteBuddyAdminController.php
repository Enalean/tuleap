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

namespace Tuleap\InviteBuddy\Admin;

use CSRFSynchronizerToken;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class InviteBuddyAdminController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const URL = '/admin/invitations/';

    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var InviteBuddyConfiguration
     */
    private $configuration;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        AdminPageRenderer $admin_page_renderer,
        InviteBuddyConfiguration $configuration,
        CSRFSynchronizerToken $csrf_token
    ) {
        $this->admin_page_renderer = $admin_page_renderer;
        $this->configuration       = $configuration;
        $this->csrf_token          = $csrf_token;
    }

    public static function buildSelf(): self
    {
        return new self(
            new AdminPageRenderer(),
            new InviteBuddyConfiguration(\EventManager::instance()),
            self::getCSRFSynchronizerToken(),
        );
    }

    public static function getCSRFSynchronizerToken(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::URL);
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $this->admin_page_renderer->renderANoFramedPresenter(
            _("Invitations"),
            realpath(__DIR__ . '/../../../templates/admin/invitations'),
            'invitations',
            [
                'max_invitations_by_day' => $this->configuration->getNbMaxInvitationsByDay(),
                'csrf_token'             => $this->csrf_token,
            ],
        );
    }
}
