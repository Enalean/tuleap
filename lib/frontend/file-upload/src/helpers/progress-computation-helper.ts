/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import type { OnGoingUploadFile } from "../file-upload-options";

export function computedProgress(
    files: OnGoingUploadFile[],
    file_name: string,
    bytes_sent: number,
    bytes_total: number,
): number {
    if (files.length === 0) {
        return 0;
    }

    let sum_progress = 0;
    files.forEach((file) => {
        if (file.file_name === file_name) {
            file.progress = Math.round((bytes_sent / bytes_total) * 100);
        }
        sum_progress += file.progress;
    });

    return Math.round(sum_progress / files.length);
}
