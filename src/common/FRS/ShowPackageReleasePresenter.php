<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use EventManager;
use FRSRelease;
use Tuleap\FRS\Events\GetReleaseNotesLink;

/**
 * @psalm-immutable
 */
final readonly class ShowPackageReleasePresenter
{
    private function __construct(
        public int $id,
        public string $name,
        public bool $is_hidden,
        public string $date,
        public string $show_url,
        public string $edit_url,
        public string $delete_url,
        public \CSRFSynchronizerToken $csrf_token,
    ) {
    }

    public static function fromRelease(
        FRSRelease $release,
    ): self {
        $event = new GetReleaseNotesLink($release);
        EventManager::instance()->dispatch($event);
        $show_url = $event->getUrl();

        $edit_url = '/file/admin/release.php?' . http_build_query([
            'func' => 'edit',
            'group_id' => $release->getGroupID(),
            'package_id' => $release->getPackageID(),
            'id' => $release->getReleaseID(),
        ]);

        $delete_url = '/file/admin/release.php?' . http_build_query([
            'func' => 'delete',
            'group_id' => $release->getGroupID(),
            'package_id' => $release->getPackageID(),
            'id' => $release->getReleaseID(),
        ]);

        return new self(
            $release->getReleaseID(),
            $release->getName(),
            $release->isHidden(),
            \format_date($GLOBALS['Language']->getText('system', 'datefmt_short'), (int) $release->getReleaseDate()),
            $show_url,
            $edit_url,
            $delete_url,
            new \CSRFSynchronizerToken('/file/' . urlencode((string) $release->getGroupID()) . '/package/' . urlencode((string) $release->getPackageID()))
        );
    }
}
