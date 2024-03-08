<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration\Template\Upload;

use DateInterval;
use DateTimeImmutable;
use PFUser;
use Tuleap\Project\REST\v1\Project\ProjectFilePOSTRepresentation;

final readonly class ProjectFileToUploadCreator
{
    private const EXPIRATION_DELAY_IN_HOURS = 4;

    public function __construct(
        private SaveFileUpload $file_ongoing_upload_save_dao,
    ) {
    }

    public function creatFileToUpload(
        ProjectFilePOSTRepresentation $template_file_representation,
        PFUser $current_user,
        DateTimeImmutable $current_time,
        \Project $project,
    ): FileToUpload {
        $file_to_insert = InsertFileToUpload::fromREST(
            $template_file_representation,
            $current_user,
            $current_time->add(new DateInterval('PT' . self::EXPIRATION_DELAY_IN_HOURS . 'H')),
            $project,
        );
        $upload_file_id = $this->file_ongoing_upload_save_dao->saveFileOnGoingUpload(
            $file_to_insert
        );
        return new FileToUpload($upload_file_id);
    }
}
