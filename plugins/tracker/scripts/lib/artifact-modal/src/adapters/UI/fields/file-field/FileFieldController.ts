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

import type { FileFieldValueModel } from "./FileFieldValueModel";
import { NewFileToAttach } from "./NewFileToAttach";
import type { AttachedFileDescription } from "./AttachedFileDescription";
import type { FileFieldType } from "./FileFieldType";

export type NewFileToAttachCollection = ReadonlyArray<NewFileToAttach>;
export type AttachedFileCollection = ReadonlyArray<AttachedFileDescription> | undefined;

export interface FileFieldControllerType {
    getNewFilesToAttach(): NewFileToAttachCollection;
    getAttachedFiles(): AttachedFileCollection;
    setFileOfNewFileToAttach(file: NewFileToAttach, new_file: File): NewFileToAttachCollection;
    setDescriptionOfNewFileToAttach(
        file: NewFileToAttach,
        description: string
    ): NewFileToAttachCollection;
    reset(file: NewFileToAttach): NewFileToAttachCollection;
    addNewFileToAttach(): NewFileToAttachCollection;
    markFileForRemoval(file: AttachedFileDescription): AttachedFileCollection;
    cancelFileRemoval(file: AttachedFileDescription): AttachedFileCollection;
}

export const FileFieldController = (
    field: FileFieldType,
    value_model: FileFieldValueModel
): FileFieldControllerType => {
    let attached_files: AttachedFileCollection = field.file_descriptions;

    return {
        getNewFilesToAttach: (): NewFileToAttachCollection => value_model.temporary_files,

        getAttachedFiles: (): AttachedFileCollection => attached_files,

        setFileOfNewFileToAttach(file: NewFileToAttach, new_file: File): NewFileToAttachCollection {
            value_model.temporary_files = value_model.temporary_files.map((existing_file) => {
                if (existing_file !== file) {
                    return existing_file;
                }
                return NewFileToAttach.fromFileAndPrevious(existing_file, new_file);
            });
            return value_model.temporary_files;
        },

        setDescriptionOfNewFileToAttach(
            file: NewFileToAttach,
            description: string
        ): NewFileToAttachCollection {
            value_model.temporary_files = value_model.temporary_files.map((existing_file) => {
                if (existing_file !== file) {
                    return existing_file;
                }
                return NewFileToAttach.fromDescriptionAndPrevious(existing_file, description);
            });
            return value_model.temporary_files;
        },

        reset(file: NewFileToAttach): NewFileToAttachCollection {
            value_model.temporary_files = value_model.temporary_files.map((existing_file) => {
                if (existing_file !== file) {
                    return existing_file;
                }
                return NewFileToAttach.build();
            });
            return value_model.temporary_files;
        },

        addNewFileToAttach(): NewFileToAttachCollection {
            value_model.temporary_files = [...value_model.temporary_files, NewFileToAttach.build()];
            return value_model.temporary_files;
        },

        markFileForRemoval(file: AttachedFileDescription): AttachedFileCollection {
            value_model.value = value_model.value.filter((id) => id !== file.id);

            attached_files = attached_files?.map((existing_file) => {
                if (existing_file !== file) {
                    return existing_file;
                }
                return { ...file, marked_for_removal: true };
            });
            return attached_files;
        },

        cancelFileRemoval(file: AttachedFileDescription): AttachedFileCollection {
            value_model.value = [...value_model.value, file.id];

            attached_files = attached_files?.map((existing_file) => {
                if (existing_file !== file) {
                    return existing_file;
                }
                return { ...file, marked_for_removal: false };
            });
            return attached_files;
        },
    };
};
