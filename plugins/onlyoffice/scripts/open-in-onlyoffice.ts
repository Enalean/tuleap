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

import "./style.scss";

function processRefreshTokenMessage(event: MessageEvent): void {
    if (event.origin !== window.origin || event.source === event.target) {
        return;
    }
    fetch("/onlyoffice/document_save_refresh_token", { method: "POST", body: event.data });
}

document.addEventListener("DOMContentLoaded", () => {
    window.addEventListener("message", processRefreshTokenMessage);
});
