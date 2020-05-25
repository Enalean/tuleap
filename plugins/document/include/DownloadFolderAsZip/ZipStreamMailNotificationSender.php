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

namespace Tuleap\Document\DownloadFolderAsZip;

use Codendi_Mail;
use ForgeConfig;

class ZipStreamMailNotificationSender
{
    public function sendNotificationAboutErrorsInArchive(\PFUser $user, \Docman_Folder $folder, \Project $project): void
    {
        $mail_title = dgettext('tuleap-document', 'Files missing in your latest archive download');
        $body_text =
            sprintf(
                dgettext(
                    'tuleap-document',
                    'You have recently downloaded the folder "%s" (id: %d) in the project %s.'
                ),
                $folder->getTitle(),
                $folder->getId(),
                $project->getPublicName()
            );

        $body_text .= PHP_EOL . dgettext('tuleap-document', 'However, all its content could not have been included in the archive.');
        $body_text .= PHP_EOL . dgettext('tuleap-document', 'You will find a file named TULEAP_ERRORS.txt at the root of the archive, listing all the impacted files.');
        $body_text .= PHP_EOL . PHP_EOL . dgettext('tuleap-document', 'Please contact your site administrator.');

        $mail = new Codendi_Mail();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setTo($user->getEmail());
        $mail->setSubject($mail_title);
        $mail->setBodyText($body_text);

        $mail->send();
    }
}
