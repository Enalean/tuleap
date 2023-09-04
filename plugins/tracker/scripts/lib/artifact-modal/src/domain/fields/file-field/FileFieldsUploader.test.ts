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
import { Fault } from "@tuleap/fault";
import { CreateFileUploadStub } from "../../../../tests/stubs/CreateFileUploadStub";
import { FileFieldsUploader } from "./FileFieldsUploader";
import type { FileFieldValueModel } from "./FileFieldValueModel";
import type { AttachedFileDescription } from "./AttachedFileDescription";
import { FinishFileUploadStub } from "../../../../tests/stubs/FinishFileUploadStub";
import type { FileUploadFault } from "./FileUploadFault";

const EXISTING_FILE_ID = 125;
const FIRST_FILE_FIELD_ID = 188;
const SECOND_FILE_FIELD_ID = 982;
const FIRST_FILE_ID = 590;
const FIRST_DESCRIPTION = "avicular mat";
const SECOND_FILE_ID = 371;
const SECOND_DESCRIPTION = "plurification trillibub";
const THIRD_FILE_ID = 241;
const THIRD_DESCRIPTION = "canaigre nigori";
const FIRST_FILE_NAME = "nonalienating.zip";

const isFileUploadFault = (fault: Fault): fault is FileUploadFault =>
    "isFileUpload" in fault && fault.isFileUpload() === true;

describe(`FileFieldsUploader`, () => {
    let field_values: Record<number, FileFieldValueModel>;
    let upload_creator: CreateFileUploadStub;
    let upload_finisher: FinishFileUploadStub;

    beforeEach(() => {
        field_values = {};
        field_values[FIRST_FILE_FIELD_ID] = {
            field_id: FIRST_FILE_FIELD_ID,
            label: "irrelevant",
            type: "file",
            file_descriptions: [{ id: EXISTING_FILE_ID } as AttachedFileDescription],
            temporary_files: [
                {
                    file: { name: FIRST_FILE_NAME, type: "application/zip" } as File,
                    description: FIRST_DESCRIPTION,
                },
                {
                    file: { name: "polyaxone.png", type: "image/png" } as File,
                    description: SECOND_DESCRIPTION,
                },
            ],
            value: [EXISTING_FILE_ID],
        } as unknown as FileFieldValueModel;
        field_values[SECOND_FILE_FIELD_ID] = {
            field_id: SECOND_FILE_FIELD_ID,
            label: "irrelevant",
            type: "file",
            file_descriptions: undefined,
            temporary_files: [
                {
                    file: { name: "exornation.txt", type: "text/plain" } as File,
                    description: THIRD_DESCRIPTION,
                },
            ],
            value: [],
        } as unknown as FileFieldValueModel;

        upload_creator = CreateFileUploadStub.withSuccessiveFiles(
            {
                file_id: FIRST_FILE_ID,
                upload_href: `/uploads/tracker/file/${FIRST_FILE_ID}`,
            },
            {
                file_id: SECOND_FILE_ID,
                upload_href: `/uploads/tracker/file/${SECOND_FILE_ID}`,
            },
            {
                file_id: THIRD_FILE_ID,
                upload_href: `/uploads/tracker/file/${THIRD_FILE_ID}`,
            },
        );

        upload_finisher = FinishFileUploadStub.withSuccessiveFiles(
            { file_id: FIRST_FILE_ID },
            { file_id: SECOND_FILE_ID },
            { file_id: THIRD_FILE_ID },
        );
    });

    const uploadAll = (): ResultAsync<void, Fault> => {
        const uploader = FileFieldsUploader(upload_creator, upload_finisher);

        return uploader.uploadAllFileFields(Object.values(field_values));
    };

    it(`will iterate on all file fields and for all new files attached,
        it will first create the file upload,
        then it will upload its data
        and it will attach the new file ID to the file field's value`, async () => {
        const result = await uploadAll();

        expect(result.isOk()).toBe(true);
        expect(field_values[FIRST_FILE_FIELD_ID].value).toContain(EXISTING_FILE_ID);
        expect(field_values[FIRST_FILE_FIELD_ID].value).toContain(FIRST_FILE_ID);
        expect(field_values[FIRST_FILE_FIELD_ID].value).toContain(SECOND_FILE_ID);
        expect(field_values[SECOND_FILE_FIELD_ID].value).toContain(THIRD_FILE_ID);
    });

    it(`will send the file metadata when creating the file upload`, async () => {
        field_values[FIRST_FILE_FIELD_ID].temporary_files = [
            { file: { type: "image/png" } as File, description: FIRST_DESCRIPTION },
        ];

        const result = await uploadAll();

        expect(result.isOk()).toBe(true);
        const new_upload = upload_creator.getFile(0);
        expect(new_upload?.file_field_id).toBe(FIRST_FILE_FIELD_ID);
        expect(new_upload?.file_type).toBe("image/png");
        expect(new_upload?.description).toBe(FIRST_DESCRIPTION);
    });

    it(`when the file fields don't have new files attached, it will return an Ok`, async () => {
        field_values[FIRST_FILE_FIELD_ID].temporary_files = [
            { file: undefined, description: FIRST_DESCRIPTION },
        ];
        field_values[SECOND_FILE_FIELD_ID].temporary_files = [];

        const result = await uploadAll();

        expect(result.isOk()).toBe(true);
        expect(field_values[FIRST_FILE_FIELD_ID].value).toStrictEqual([EXISTING_FILE_ID]);
        expect(field_values[SECOND_FILE_FIELD_ID].value).toHaveLength(0);
    });

    it(`when the file type could not be guessed by the browser, it defaults to "application/octet-stream"`, async () => {
        field_values[FIRST_FILE_FIELD_ID].temporary_files = [
            { file: { type: "" } as File, description: FIRST_DESCRIPTION },
        ];

        const result = await uploadAll();

        expect(result.isOk()).toBe(true);
        const new_upload = upload_creator.getFile(0);
        expect(new_upload?.file_type).toBe("application/octet-stream");
    });

    it(`when the file has size zero, it skips the actual upload and returns an Ok`, async () => {
        field_values[FIRST_FILE_FIELD_ID].temporary_files = [
            { file: { type: "application/zip", size: 0 } as File, description: FIRST_DESCRIPTION },
        ];
        field_values[SECOND_FILE_FIELD_ID].temporary_files = [];

        upload_creator = CreateFileUploadStub.withSuccessiveFiles({
            file_id: FIRST_FILE_ID,
            upload_href: null,
        });

        const result = await uploadAll();

        expect(result.isOk()).toBe(true);
        expect(upload_finisher.getCallCount()).toBe(0);
    });

    it(`when there is an error while creating any file upload, it returns a specialized Fault`, async () => {
        upload_creator = CreateFileUploadStub.withFault(
            Fault.fromMessage("Error while creating a new file upload"),
        );

        const result = await uploadAll();

        if (!result.isErr()) {
            throw Error("Expected an Err");
        }
        if (!isFileUploadFault(result.error)) {
            throw Error("Expected a File Upload Fault");
        }
        expect(result.error.getFileName()).toBe(FIRST_FILE_NAME);
    });

    it(`when there is an error while uploading any file, it returns a specialized Fault`, async () => {
        upload_finisher = FinishFileUploadStub.withFault(
            Fault.fromMessage("Error during file upload"),
        );

        const result = await uploadAll();

        if (!result.isErr()) {
            throw Error("Expected an Err");
        }
        if (!isFileUploadFault(result.error)) {
            throw Error("Expected a File Upload Fault");
        }
        expect(result.error.getFileName()).toBe(FIRST_FILE_NAME);
    });
});
