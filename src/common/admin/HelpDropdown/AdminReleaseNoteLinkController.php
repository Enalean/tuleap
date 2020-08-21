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
 *
 */

declare(strict_types=1);

namespace Tuleap\admin\HelpDropdown;

use CSRFSynchronizerToken;
use HTTPRequest;
use PFUser;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\BuildVersion\FlavorFinderFromFilePresence;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\HelpDropdown\ReleaseLinkDao;
use Tuleap\HelpDropdown\ReleaseNoteManager;
use Tuleap\HelpDropdown\VersionNumberExtractor;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use UserPreferencesDao;

final class AdminReleaseNoteLinkController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;

    /**
     * @var ReleaseNoteManager
     */
    private $help_links_manager;

    /**
     * @var string
     */
    private $version_number;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        AdminPageRenderer $admin_page_renderer,
        ReleaseNoteManager $help_links_manager,
        CSRFSynchronizerToken $csrf_token,
        string $version_number
    ) {
        $this->admin_page_renderer = $admin_page_renderer;
        $this->help_links_manager  = $help_links_manager;
        $this->version_number      = $version_number;
        $this->csrf_token          = $csrf_token;
    }

    public static function buildSelf(): self
    {
        return new self(
            new AdminPageRenderer(),
            new ReleaseNoteManager(
                new ReleaseLinkDao(),
                new UserPreferencesDao(),
                new VersionNumberExtractor(),
                new DBTransactionExecutorWithConnection(
                    DBFactory::getMainTuleapDBConnection()
                )
            ),
            new CSRFSynchronizerToken('/admin/release-note/'),
            VersionPresenter::fromFlavorFinder(new FlavorFinderFromFilePresence())->version_number
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $current_user = $request->getCurrentUser();
        $this->checkUserIsSiteAdmin($current_user);

        $this->loadAdminPage();
    }

    private function checkUserIsSiteAdmin(PFUser $user): void
    {
        if (! $user->isSuperUser()) {
            throw new ForbiddenException(
                dgettext("tuleap-core", 'Permission denied')
            );
        }
    }

    public function loadAdminPage(): void
    {
        $title = dgettext("tuleap-core", "Manage help links");
        $release_note_link = $this->help_links_manager->getReleaseNoteLink($this->version_number);

        $presenter = new AdminReleaseNotePresenter(
            CSRFSynchronizerTokenPresenter::fromToken($this->csrf_token),
            $release_note_link
        );

        $this->admin_page_renderer->renderANoFramedPresenter(
            $title,
            __DIR__ . '/../../../templates/admin/release_note',
            'release-note-link',
            $presenter
        );
    }
}
