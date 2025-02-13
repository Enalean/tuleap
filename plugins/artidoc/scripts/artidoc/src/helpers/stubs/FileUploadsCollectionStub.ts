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

import { ref } from "vue";
import type { FileUploadsCollection } from "@/sections/attachments/FileUploadsCollection";
import { noop } from "@/helpers/noop";

export const FileUploadsCollectionStub = {
    withoutUploadsInProgress: (): FileUploadsCollection => ({
        pending_uploads: ref([]),
        deleteUpload: noop,
        cancelSectionUploads: noop,
        addPendingUpload: noop,
    }),

    withUploadsInProgress: (): FileUploadsCollection => ({
        pending_uploads: ref([
            {
                file_id: "id1",
                section_id: "section_id1",
                file_name: "a.jpg",
                progress: 45,
            },
            {
                file_id: "id2",
                section_id: "section_id2",
                file_name: "b.png",
                progress: 0,
            },
            {
                file_id: "id3",
                section_id: "section_id3",
                file_name: "c.jpeg",
                progress: 100,
            },
        ]),
        deleteUpload: noop,
        cancelSectionUploads: noop,
        addPendingUpload: noop,
    }),
};
