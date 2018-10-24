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

export function download(content, file_name, mime_type) {
    const link = document.createElement("a");
    const blob = new Blob([content], { type: mime_type });
    if (URL && "download" in link) {
        // html5 "download" attribute supported
        link.href = URL.createObjectURL(blob);
        link.setAttribute("download", file_name);
        document.body.appendChild(link);
        link.click();
        link.remove();
    } else {
        // IE11
        navigator.msSaveBlob(blob, file_name);
    }
}
