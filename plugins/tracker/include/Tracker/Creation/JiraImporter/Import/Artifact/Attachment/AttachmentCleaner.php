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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment;

use DateTimeImmutable;
use DirectoryIterator;

class AttachmentCleaner
{
    public function deleteDownloadedAttachmentOlderThanOneDay(DateTimeImmutable $current_time): void
    {
        if (! is_dir(AttachmentDownloader::getTmpFolderURL())) {
            return;
        }

        $directory           = new DirectoryIterator(AttachmentDownloader::getTmpFolderURL());
        $yesterday_timestamp = $current_time->getTimestamp() - (24 * 3600);
        foreach ($directory as $file) {
            if (! $file->isDot() && $file->getMTime() < $yesterday_timestamp) {
                unlink($file->getRealPath());
            }
        }
    }
}
