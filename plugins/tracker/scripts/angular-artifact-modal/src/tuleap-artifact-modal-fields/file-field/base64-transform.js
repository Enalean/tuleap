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

import { Transform } from "readable-stream";

export function getBase64Transform() {
    const stream = new Transform({
        readableObjectMode: true,
    });
    stream.setEncoding("ascii");

    stream._transform = function (chunk, encoding, callback) {
        //from https://github.com/dominictarr/arraybuffer-base64/blob/master/index.js
        let binary_string = "";
        for (let i = 0; i < chunk.byteLength; i++) {
            binary_string += String.fromCharCode(chunk[i]);
        }
        callback(null, window.btoa(binary_string));
    };

    return stream;
}
