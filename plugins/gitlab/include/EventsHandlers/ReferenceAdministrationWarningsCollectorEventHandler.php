<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\EventsHandlers;

use Tuleap\Gitlab\Reference\GitlabCommitReference;
use Tuleap\Project\Admin\Reference\ReferenceAdministrationWarningsCollectorEvent;

final class ReferenceAdministrationWarningsCollectorEventHandler
{
    public function handle(ReferenceAdministrationWarningsCollectorEvent $event): void
    {
        $does_keyword_gitlab_commit_exists = array_search(
            GitlabCommitReference::REFERENCE_NAME,
            array_column(
                $event->getProjectReferences(),
                'keyword'
            )
        );

        if ($does_keyword_gitlab_commit_exists === false) {
            return;
        }

        $event->addWarningMessage(
            dgettext('tuleap-gitlab', "The project reference based on the keyword 'gitlab_commit' is overriding the system reference used by the GitLab plugin.")
        );
    }
}
