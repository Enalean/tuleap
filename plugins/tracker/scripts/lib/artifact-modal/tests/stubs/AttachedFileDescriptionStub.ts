/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { AttachedFileDescription } from "../../src/adapters/UI/fields/file-field/AttachedFileDescription";

export const AttachedFileDescriptionStub = {
    withImage: (data?: Partial<AttachedFileDescription>): AttachedFileDescription => {
        const id = data?.id ?? 237;
        const name = data?.name ?? "coccydynia.png";
        const html_url = `/plugins/tracker/attachments/${id}-${name}`;
        const html_preview_url = `/plugins/tracker/attachments/preview/${id}-${name}`;

        return {
            id,
            name,
            description: "unbreaking makebate",
            html_url,
            html_preview_url,
            size: 44198,
            submitted_by: 171,
            marked_for_removal: false,
            display_as_image: true,
            ...data,
        };
    },

    withNotAnImage: (data?: Partial<AttachedFileDescription>): AttachedFileDescription => {
        const id = data?.id ?? 429;
        const name = data?.name ?? "cholecystitis.txt";
        const html_url = `/plugins/tracker/attachments/${id}-${name}`;

        return {
            id,
            name,
            description: "cespitose hydromedusoid",
            html_url,
            html_preview_url: null,
            size: 16831,
            submitted_by: 117,
            marked_for_removal: false,
            display_as_image: false,
            ...data,
        };
    },
};
