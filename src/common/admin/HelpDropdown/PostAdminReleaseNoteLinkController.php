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
use Feedback;
use HTTPRequest;
use PFUser;
use Tuleap\BuildVersion\FlavorFinderFromFilePresence;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\HelpDropdown\ReleaseLinkDao;
use Tuleap\HelpDropdown\ReleaseNoteCustomLinkUpdater;
use Tuleap\HelpDropdown\VersionNumberExtractor;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use UserPreferencesDao;
use Valid_HTTPURI;

class PostAdminReleaseNoteLinkController implements DispatchableWithRequest
{
    /**
     * @var ReleaseNoteCustomLinkUpdater
     */
    private $custom_link_updater;

    /**
     * @var string
     */
    private $version_number;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        ReleaseNoteCustomLinkUpdater $custom_link_updater,
        CSRFSynchronizerToken $csrf_token,
        string $version_number
    ) {
        $this->custom_link_updater = $custom_link_updater;
        $this->version_number      = $version_number;
        $this->csrf_token          = $csrf_token;
    }

    public static function buildSelf(): self
    {
        return new self(
            new ReleaseNoteCustomLinkUpdater(
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

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $current_user = $request->getCurrentUser();
        $this->checkUserIsSiteAdmin($current_user);

        $valid_url = new Valid_HTTPURI();
        $custom_url = $request->get("url");
        if (! $custom_url || $custom_url === '' || ! $valid_url->validate($custom_url)) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext("tuleap-core", 'Provided release note URL is not well formed')
            );

            $layout->redirect('/admin/release-note/');
        }

        $this->csrf_token->check();

        $this->custom_link_updater->updateReleaseNoteLink(
            $custom_url,
            $this->version_number
        );

        $layout->addFeedback(
            \Feedback::INFO,
            dgettext("tuleap-core", "Custom release note link successfully updated.")
        );

        $layout->redirect('/admin/release-note/');
    }

    private function checkUserIsSiteAdmin(PFUser $user): void
    {
        if (! $user->isSuperUser()) {
            throw new ForbiddenException(
                dgettext("tuleap-core", 'Permission denied')
            );
        }
    }
}
