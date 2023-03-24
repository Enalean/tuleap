<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Mediawiki\Migration;

use Feedback;
use Tuleap\Layout\BaseLayout;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;

final class ToStandaloneMediawikiRedirector
{
    public function tryRedirection(\Project $project, \HTTPRequest $request, BaseLayout $layout): void
    {
        $standalone_service = $project->getService(\Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService::SERVICE_SHORTNAME);
        if (! $standalone_service instanceof MediawikiStandaloneService) {
            return;
        }

        $layout->addFeedback(
            Feedback::WARN,
            dgettext(
                'tuleap-mediawiki',
                'Legacy Mediawiki service is not active in this project. You are being redirected to the new MediaWiki service.'
            )
        );

        $pagename = $request->get('title');
        $url      = $pagename ? $standalone_service->getPageUrl($pagename) : $standalone_service->getUrl();

        // Do not redirect directly to new url in order to be able to display
        // feedback to the end user (MW standalone does not display feedbacks).
        $layout->redirect('/my/redirect.php?' . http_build_query(['return_to' => $url]));
    }
}
