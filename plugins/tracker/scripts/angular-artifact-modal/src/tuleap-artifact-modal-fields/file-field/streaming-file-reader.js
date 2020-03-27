/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { Readable } from "readable-stream";
import { file_upload_rules } from "./file-upload-rules-state.js";

export { getFileReaderStream };

// from https://github.com/jimmywarting/Screw-FileReader
function promisifyFileReader(file_reader) {
    return new Promise((resolve, reject) => {
        file_reader.onload = file_reader.onerror = (evt) => {
            file_reader.onload = file_reader.onerror = null;

            evt.type === "load"
                ? resolve(file_reader.result || file_reader)
                : reject(new Error("Failed to read the blob/file"));
        };
    });
}

function readBlobAsArrayBuffer(blob) {
    const file_reader = new FileReader();
    file_reader.readAsArrayBuffer(blob);
    return promisifyFileReader(file_reader);
}

function getFileReaderStream(blob) {
    let position = 0;
    const DEFAULT_CHUNK_SIZE = file_upload_rules.max_chunk_size;

    const stream = new Readable({
        highWaterMark: DEFAULT_CHUNK_SIZE,
        objectMode: true,
    });
    stream._read = function (size) {
        if (position >= blob.size) {
            stream.push(null);
            return;
        }
        const chunk = blob.slice(position, position + size);

        readBlobAsArrayBuffer(chunk).then(
            (array_buffer) => {
                const uint8array = new Uint8Array(array_buffer);
                position += uint8array.byteLength;

                stream.push(uint8array);
            },
            (error) => {
                stream.emit("error", error);
            }
        );
    };

    return stream;
}
