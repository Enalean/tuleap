/*
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

import { Upload } from "tus-js-client";
import type VueRouter from "vue-router";

export function uploadFile(
    project_id: number,
    project_archive: File,
    upload_href: string,
    router: VueRouter.Router,
    setIsCreatingProject: (is_creating_project: boolean) => void,
    setErrorMessage: (error: string) => void,
): void {
    const uploader = new Upload(project_archive, {
        uploadUrl: upload_href,
        metadata: {
            filename: project_archive.name,
            filetype: project_archive.type,
        },
        onError: function (): void {
            setIsCreatingProject(false);
            setErrorMessage(
                "Unable to finalize the creation of the project, the upload of the archive failed.",
            );
        },
        onSuccess: async function (): Promise<void> {
            setIsCreatingProject(true);
            await router.push({
                name: "from-archive-creation",
                params: { project_id: String(project_id) },
            });
        },
    });
    uploader.start();
}
