import { Writable } from "readable-stream";
import pump from "pump";
import {
    uploadTemporaryFile as uploadFile,
    uploadAdditionalChunk
} from "../../rest/rest-service.js";

import { getFileReaderStream } from "./streaming-file-reader.js";
import { getBase64Transform } from "./base64-transform.js";
import { file_upload_rules } from "./file-upload-rules-state.js";

export { uploadAllTemporaryFiles, uploadTemporaryFile };

function uploadAllTemporaryFiles(temporary_files) {
    const promises = temporary_files.map(file => uploadTemporaryFile(file));

    return Promise.all(promises);
}

function uploadTemporaryFile(temporary_file) {
    if (!temporary_file.hasOwnProperty("file") || typeof temporary_file.file.name === "undefined") {
        return Promise.resolve();
    }

    let temporary_file_id;
    let chunk_offset = 1;

    const file_type =
        temporary_file.file.type !== "" ? temporary_file.file.type : "application/octet-stream";

    const file_upload_stream = new Writable({
        highWaterMark: file_upload_rules.max_chunk_size,
        decodeStrings: false
    });
    file_upload_stream._write = function(chunk, encoding, callback) {
        if (chunk_offset === 1) {
            return uploadFile(
                temporary_file.file.name,
                file_type,
                chunk,
                temporary_file.description
            ).then(
                id => {
                    chunk_offset++;
                    temporary_file_id = id;
                    callback();
                },
                error => callback(error)
            );
        }

        return uploadAdditionalChunk(temporary_file_id, chunk, chunk_offset).then(
            () => {
                chunk_offset++;
                callback();
            },
            error => callback(error)
        );
    };

    return new Promise((resolve, reject) => {
        pump(
            getFileReaderStream(temporary_file.file),
            getBase64Transform(),
            file_upload_stream,
            error => {
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
