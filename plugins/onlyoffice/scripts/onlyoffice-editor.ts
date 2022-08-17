/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

const ONLYOFFICE_API_SCRIPT_PATH = "web-apps/apps/api/documents/api.js";

interface OnlyOfficeDocsAPI {
    DocEditor: (placeholder_id: string) => void;
}

declare global {
    interface Window {
        DocsAPI: OnlyOfficeDocsAPI | undefined;
    }
}

function addOnlyOfficeAPIScript(
    document: Document,
    window: Window,
    document_server_base_url: string
): Promise<OnlyOfficeDocsAPI> {
    return new Promise((resolve, reject) => {
        const api_script_url = new URL(ONLYOFFICE_API_SCRIPT_PATH, document_server_base_url);
        const onlyoffice_api_script_element = document.createElement("script");
        onlyoffice_api_script_element.src = api_script_url.href;
        onlyoffice_api_script_element.addEventListener("load", () => {
            if (window.DocsAPI === undefined) {
                throw new Error(
                    "ONLYOFFICE script " +
                        api_script_url.href +
                        " did not expose DocsAPI on window as expected"
                );
            }
            resolve(window.DocsAPI);
        });
        onlyoffice_api_script_element.addEventListener("error", (error_event: ErrorEvent) =>
            reject(error_event.error)
        );
        document.body.appendChild(onlyoffice_api_script_element);
    });
}

export async function main(document: Document, window: Window): Promise<void> {
    const document_server_base_url = document.body.dataset.documentServerUrl;
    if (!document_server_base_url) {
        document.body.innerText = "Missing document server URL";
        return;
    }

    try {
        const docs_api = await addOnlyOfficeAPIScript(document, window, document_server_base_url);
        docs_api.DocEditor("onlyoffice-editor");
    } catch (e) {
        document.body.innerText = "Error while loading ONLYOFFICE editor content";
        throw e;
    }
}

document.addEventListener("DOMContentLoaded", () => main(document, window));
