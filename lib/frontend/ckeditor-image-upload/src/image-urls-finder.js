/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

export function findImageUrls(html_string) {
    return Array.from(
        new DOMParser().parseFromString(html_string, "text/html").querySelectorAll("img"),
    ).map((img) => img.getAttribute("src"));
}

export function isThereAnImageWithDataURI(html_string) {
    return Array.from(
        new DOMParser().parseFromString(html_string, "text/html").querySelectorAll("img"),
    ).some(doesImageHaveADataURI);
}

function doesImageHaveADataURI(img) {
    return data_uri_regexp.test(img.getAttribute("src"));
}

const data_uri_regexp = /^data:/i;
