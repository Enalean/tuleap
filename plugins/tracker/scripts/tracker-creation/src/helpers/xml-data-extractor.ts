/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { TrackerToBeCreatedMandatoryData } from "../store/type";

const XML_MIME_TYPE = "text/xml";
const TRACKER_DEFAULT_COLOR = "inca-silver";

export async function extractNameAndShortnameFromXmlFile(
    file: File,
): Promise<TrackerToBeCreatedMandatoryData> {
    if (file.type !== XML_MIME_TYPE) {
        return Promise.reject("Not a xml file");
    }

    const xml_content = await readFile(file);

    if (!isContentAString(xml_content)) {
        return Promise.reject("Unable to parse the provided file");
    }

    const parser = new DOMParser();
    const xml_file = parser.parseFromString(xml_content, XML_MIME_TYPE);

    const name: Element | null = xml_file.querySelector("tracker > name");
    const shortname: Element | null = xml_file.querySelector("tracker > item_name");
    const tlp_color: Element | null = xml_file.querySelector("tracker > color");

    if (name === null || shortname === null || !name.textContent || !shortname.textContent) {
        return Promise.reject("The provided XML file does not provide any name and/or shortname");
    }

    let tracker_color = TRACKER_DEFAULT_COLOR;
    if (tlp_color !== null && tlp_color.textContent !== null) {
        tracker_color = tlp_color.textContent;
    }
    if (tracker_color === "") {
        return Promise.reject("The tracker color cannot be an empty string");
    }

    return {
        name: name.textContent,
        shortname: shortname.textContent,
        color: tracker_color,
    };
}

function isContentAString(xml_content: string | ArrayBuffer | null): xml_content is string {
    return xml_content !== null && !(xml_content instanceof ArrayBuffer);
}

function readFile(file: File): Promise<string | ArrayBuffer | null> {
    return new Promise((resolve, reject) => {
        const file_reader = new FileReader();
        file_reader.readAsText(file);

        file_reader.onload = file_reader.onerror = (evt: ProgressEvent): void => {
            file_reader.onload = file_reader.onerror = null;

            evt.type === "load"
                ? resolve(file_reader.result)
                : reject(new Error("Failed to read the blob/file"));
        };
    });
}
