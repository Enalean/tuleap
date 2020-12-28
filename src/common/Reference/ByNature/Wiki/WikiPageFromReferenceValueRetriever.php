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

namespace Tuleap\Reference\ByNature\Wiki;

use PFUser;
use Project;
use Tuleap\PHPWiki\WikiPage;
use Wiki;

class WikiPageFromReferenceValueRetriever
{
    public function getWikiPageUserCanView(Project $project, PFUser $user, string $reference_value): ?WikiPage
    {
        $wiki = new Wiki((int) $project->getID());
        if (! $wiki->isAutorized($user->getId())) {
            return null;
        }

        $page = new WikiPage((int) $project->getID(), $reference_value);
        if (! $page->isAutorized($user->getId())) {
            return null;
        }

        return $page;
    }
}
