/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { Writable } from "readable-stream";
import pump from "pump";
import {
    uploadTemporaryFile as uploadFile,
    uploadAdditionalChunk,
} from "../../rest/rest-service.js";

import { getFileReaderStream } from "./streaming-file-reader.js";
import { getBase64Transform } from "./base64-transform.js";
import { file_upload_rules } from "./file-upload-rules-state.js";

export { uploadAllTemporaryFiles, uploadTemporaryFile };

function uploadAllTemporaryFiles(temporary_files) {
    const promises = temporary_files.map((file) => uploadTemporaryFile(file));

    return Promise.all(promises);
}

function uploadTemporaryFile(temporary_file) {
    if (
        !Object.prototype.hasOwnProperty.call(temporary_file, "file") ||
        typeof temporary_file.file.name === "undefined"
    ) {
        return Promise.resolve();
    }

    let temporary_file_id;
    let chunk_offset = 1;

    const file_type =
        temporary_file.file.type !== "" ? temporary_file.file.type : "application/octet-stream";

    const file_upload_stream = new Writable({
        highWaterMark: file_upload_rules.max_chunk_size,
        decodeStrings: false,
    });
    file_upload_stream._write = function (chunk, encoding, callback) {
        if (chunk_offset === 1) {
            return uploadFile(
                temporary_file.file.name,
                file_type,
                chunk,
                temporary_file.description
            ).then(
                (id) => {
                    chunk_offset++;
                    temporary_file_id = id;
                    callback();
                },
                (error) => callback(error)
            );
        }

        return uploadAdditionalChunk(temporary_file_id, chunk, chunk_offset).then(
            () => {
                chunk_offset++;
                callback();
            },
            (error) => callback(error)
        );
    };

    return new Promise((resolve, reject) => {
        pump(
            getFileReaderStream(temporary_file.file),
            getBase64Transform(),
            file_upload_stream,
            (error) => {
                if (error) {
                    error.file_name = temporary_file.file.name;
                    error.file_size = temporary_file.file.size;
                    reject(error);
                    return;
                }

                resolve(temporary_file_id);
            }
        );
    });
}
