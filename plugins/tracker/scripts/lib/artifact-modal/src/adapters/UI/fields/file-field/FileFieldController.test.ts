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

import { NewFileToAttach } from "./NewFileToAttach";
import type { AttachedFileCollection, NewFileToAttachCollection } from "./FileFieldController";
import { FileFieldController } from "./FileFieldController";
import type { FileFieldValueModel } from "./FileFieldValueModel";
import type { FileFieldType } from "./FileFieldType";
import type { AttachedFileDescription } from "./AttachedFileDescription";

describe(`FileFieldController`, () => {
    describe(`setDescriptionOfNewFileToAttach()`, () => {
        const DESCRIPTION = "Palaeonemertinea";

        const setDescription = (file: NewFileToAttach): NewFileToAttachCollection => {
            const field = {} as FileFieldType;
            const value_model = {
                temporary_files: [{ file: undefined, description: "should not change" }, file],
            } as unknown as FileFieldValueModel;

            const controller = FileFieldController(field, value_model);
            return controller.setDescriptionOfNewFileToAttach(file, DESCRIPTION);
        };

        it(`given a new file to attach and a description, it will replace the description of the new file to attach
            and return the list of new files`, () => {
            const file = { file: undefined, description: "previous value" };
            const [, second_file] = setDescription(file);
            expect(second_file.description).toBe(DESCRIPTION);
        });
    });

    describe(`setFileOfNewFileToAttach()`, () => {
        const setFile = (
            file_to_attach: NewFileToAttach,
            file: File
        ): NewFileToAttachCollection => {
            const field = {} as FileFieldType;
            const value_model = {
                temporary_files: [
                    { file: new File([], "a_file.txt"), description: "" },
                    file_to_attach,
                ],
            } as unknown as FileFieldValueModel;

            const controller = FileFieldController(field, value_model);
            return controller.setFileOfNewFileToAttach(file_to_attach, file);
        };

        it(`given a new file to attach and a File (to upload), it will replace the File of the new file to attach
            and return the list of new files`, () => {
            const new_file = new File([], "another_file.txt");
            const file_to_attach = { file: undefined, description: "file to attach" };
            const [, second_file] = setFile(file_to_attach, new_file);
            expect(second_file.file).toBe(new_file);
        });
    });

    describe(`reset()`, () => {
        const reset = (file: NewFileToAttach): NewFileToAttachCollection => {
            const field = {} as FileFieldType;
            const value_model = {
                temporary_files: [
                    {
                        file: new File([], "another_file.txt"),
                        description: "should not change",
                    },
                    file,
                ],
            } as unknown as FileFieldValueModel;

            const controller = FileFieldController(field, value_model);
            return controller.reset(file);
        };

        it(`will reset the new file to attach`, () => {
            const file = { file: new File([], "a_file.txt"), description: "a description" };
            const [, second_file] = reset(file);
            expect(second_file.file).toBeUndefined();
            expect(second_file.description).toBe("");
        });
    });

    describe(`addNewFileToAttach()`, () => {
        const addFile = (): NewFileToAttachCollection => {
            const field = {} as FileFieldType;
            const value_model = {
                temporary_files: [],
            } as unknown as FileFieldValueModel;

            const controller = FileFieldController(field, value_model);
            return controller.addNewFileToAttach();
        };

        it(`will add a new file to attach to the file field's value model`, () => {
            const files = addFile();
            expect(files).toHaveLength(1);
            expect(files[0]).toEqual(NewFileToAttach.build());
        });
    });

    describe(`markFileForRemoval()`, () => {
        const FILE_ID = 878;
        let value_model: FileFieldValueModel;

        beforeEach(() => {
            value_model = { value: [FILE_ID] } as unknown as FileFieldValueModel;
        });

        const markFileForRemoval = (file: AttachedFileDescription): AttachedFileCollection => {
            const field = { file_descriptions: [{ id: 799 }, file] } as FileFieldType;
            const controller = FileFieldController(field, value_model);
            return controller.markFileForRemoval(file);
        };

        it(`will remove the given file's id from the value model and mark it for removal`, () => {
            const file_description = { id: FILE_ID } as AttachedFileDescription;
            const files = markFileForRemoval(file_description);
            if (files === undefined) {
                throw new Error("Attached files should be defined");
            }

            expect(files[1].marked_for_removal).toBe(true);
            expect(value_model.value).not.toContain(FILE_ID);
        });
    });

    describe(`cancelFileRemoval()`, () => {
        const FILE_ID = 565;
        let value_model: FileFieldValueModel;

        beforeEach(() => {
            value_model = { value: [FILE_ID] } as unknown as FileFieldValueModel;
        });

        const cancelFileRemoval = (file: AttachedFileDescription): AttachedFileCollection => {
            const field = { file_descriptions: [{ id: 447 }, file] } as FileFieldType;
            const controller = FileFieldController(field, value_model);
            return controller.cancelFileRemoval(file);
        };

        it(`will add back the given file's id to the value model and unmark it for removal`, () => {
            const file_description = {
                id: FILE_ID,
                marked_for_removal: true,
            } as AttachedFileDescription;
            const files = cancelFileRemoval(file_description);
            if (files === undefined) {
                throw new Error("Attached files should be defined");
            }

            expect(files[1].marked_for_removal).toBe(false);
            expect(value_model.value).toContain(FILE_ID);
        });
    });
});
