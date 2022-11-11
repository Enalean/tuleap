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

import { okAsync, errAsync } from "neverthrow";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { FileUploaded } from "../../src/domain/fields/file-field/FileUploaded";
import type { FinishFileUpload } from "../../src/domain/fields/file-field/FinishFileUpload";

export type FinishFileUploadStub = FinishFileUpload & {
    getCallCount(): number;
};

export const FinishFileUploadStub = {
    withSuccessiveFiles: (
        first_file: FileUploaded,
        ...other_files: FileUploaded[]
    ): FinishFileUploadStub => {
        const all_files = [first_file, ...other_files];
        let calls_count = 0;

        return {
            uploadFile: (): ResultAsync<FileUploaded, never> => {
                calls_count++;
                const file = all_files.shift();
                if (file !== undefined) {
                    return okAsync(file);
                }
                throw Error("No file configured");
            },

            getCallCount: () => calls_count,
        };
    },

    withFault: (fault: Fault): FinishFileUploadStub => {
        let calls_count = 0;

        return {
            uploadFile: (): ResultAsync<never, Fault> => {
                calls_count++;
                return errAsync(fault);
            },

            getCallCount: () => calls_count,
        };
    },
};
