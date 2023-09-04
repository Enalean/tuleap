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

const REFRESH_CALLBACK_URL_TOKEN_IN_MN = 5;
const ONLYOFFICE_API_SCRIPT_PATH = "web-apps/apps/api/documents/api.js";

type OnlyOfficeDocumentType = "word" | "cell" | "slide";

interface OnlyOfficeDocEditorConfig {
    token: string;
    documentType: OnlyOfficeDocumentType;
}

interface OnlyOfficeDocsAPI {
    DocEditor: (placeholder_id: string, config: OnlyOfficeDocEditorConfig) => void;
}

declare global {
    interface Window {
        DocsAPI: OnlyOfficeDocsAPI | undefined;
    }
}

function addOnlyOfficeAPIScript(
    document: Document,
    window: Window,
    document_server_base_url: string,
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
                        " did not expose DocsAPI on window as expected",
                );
            }
            resolve(window.DocsAPI);
        });
        onlyoffice_api_script_element.addEventListener("error", (error_event: ErrorEvent) =>
            reject(error_event.error),
        );
        document.body.appendChild(onlyoffice_api_script_element);
    });
}

const CELL_DOCUMENT_TYPE_EXTENSIONS = [
    "csv",
    "fods",
    "ods",
    "ots",
    "xls",
    "xlsb",
    "xlsm",
    "xlsx",
    "xlt",
    "xltm",
    "xltx",
];
const SLIDE_DOCUMENT_TYPE_EXTENSIONS = [
    "fodp",
    "odp",
    "otp",
    "pot",
    "potm",
    "potx",
    "pps",
    "ppsm",
    "ppsx",
    "ppt",
    "pptm",
    "pptx",
];

/**
 * @see https://api.onlyoffice.com/editors/config/#documentType
 */
function getDocumentType(token_payload: {
    document: { fileType: string };
}): OnlyOfficeDocumentType {
    if (CELL_DOCUMENT_TYPE_EXTENSIONS.includes(token_payload.document.fileType)) {
        return "cell";
    }
    if (SLIDE_DOCUMENT_TYPE_EXTENSIONS.includes(token_payload.document.fileType)) {
        return "slide";
    }
    return "word";
}

function refreshCallbackURLToken(window: Window, callback_url: string): void {
    window.parent.postMessage(callback_url, window.origin);
}

export async function main(document: Document, window: Window): Promise<void> {
    const document_server_base_url = document.body.dataset.documentServerUrl;
    if (!document_server_base_url) {
        document.body.innerText = "Missing document server URL";
        return;
    }

    const config_token = document.body.dataset.configToken;
    if (!config_token) {
        document.body.innerText = "Missing config token";
        return;
    }

    try {
        const docs_api = await addOnlyOfficeAPIScript(document, window, document_server_base_url);
        const jwt_payload_base64_url = config_token.split(".")[1];
        const jwt_payload_base64 = jwt_payload_base64_url.replace(/-/g, "+").replace(/_/g, "/");
        const token_payload = JSON.parse(atob(jwt_payload_base64));
        docs_api.DocEditor("onlyoffice-editor", {
            ...token_payload,
            documentType: getDocumentType(token_payload),
            token: config_token,
        });

        const refresh_interval_in_ms = (REFRESH_CALLBACK_URL_TOKEN_IN_MN * 60 - 2) * 1000;
        setInterval(
            refreshCallbackURLToken,
            refresh_interval_in_ms,
            window,
            token_payload.editorConfig.callbackUrl,
        );
    } catch (e) {
        document.body.innerText = "Error while loading ONLYOFFICE editor content";
        throw e;
    }
}

document.addEventListener("DOMContentLoaded", () => main(document, window));
