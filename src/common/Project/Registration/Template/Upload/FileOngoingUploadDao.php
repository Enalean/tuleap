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

use Tuleap\DB\DataAccessObject;

final class FileOngoingUploadDao extends DataAccessObject implements SaveFileUpload
{
    public function saveFileOnGoingUpload(InsertFileToUpload $file_to_upload): int
    {
        return (int) $this->getDB()->insertReturnId(
            'project_file_upload',
            [
                'file_name'            => $file_to_upload->name,
                'file_size'       => $file_to_upload->file_size,
                'user_id'         => $file_to_upload->user_id,
                'expiration_date' => $file_to_upload->expiration_date,
            ]
        );
    }
}
