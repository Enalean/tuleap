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

import type { ResultAsync } from "neverthrow";
import { okAsync, errAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { CreateFileUpload } from "../../src/domain/fields/file-field/CreateFileUpload";
import type { FileUploadCreated } from "../../src/domain/fields/file-field/FileUploadCreated";
import type { NewFileUpload } from "../../src/domain/fields/file-field/NewFileUpload";

export type CreateFileUploadStub = CreateFileUpload & {
    getFile(call: number): NewFileUpload | undefined;
};

export const CreateFileUploadStub = {
    withSuccessiveFiles: (
        first_file: FileUploadCreated,
        ...other_files: FileUploadCreated[]
    ): CreateFileUploadStub => {
        const all_files = [first_file, ...other_files];
        const recorded_arguments = new Map<number, NewFileUpload>();
        let calls = 0;

        return {
            createFileUpload: (argument): ResultAsync<FileUploadCreated, never> => {
                recorded_arguments.set(calls, argument);
                calls++;

                const file = all_files.shift();
                if (file !== undefined) {
                    return okAsync(file);
                }
                throw Error("No file configured");
            },

            getFile: (call) => recorded_arguments.get(call),
        };
    },

    withFault: (fault: Fault): CreateFileUploadStub => {
        const recorded_arguments = new Map<number, NewFileUpload>();
        let calls = 0;

        return {
            createFileUpload: (argument): ResultAsync<never, Fault> => {
                recorded_arguments.set(calls, argument);
                calls++;

                return errAsync(fault);
            },

            getFile: (call) => recorded_arguments.get(call),
        };
    },
};
