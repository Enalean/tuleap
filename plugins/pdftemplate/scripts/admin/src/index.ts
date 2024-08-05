/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import "./admin.scss";
import {
    initiateStylesCodeEditor,
    initiateTitlePageContentCodeEditor,
    initiateHeaderContentCodeEditor,
    initiateFooterContentCodeEditor,
} from "./initiate-code-editors";
import { initiateModals } from "./initiate-modals";
import { initiatePrintPreview } from "./initiate-print-preview";
import { initiateCopyImageUrl } from "./initiate-copy-image-url";

document.addEventListener("DOMContentLoaded", () => {
    const style = initiateStylesCodeEditor();
    const title_page_content = initiateTitlePageContentCodeEditor();
    const header_content = initiateHeaderContentCodeEditor();
    const footer_content = initiateFooterContentCodeEditor();
    initiateModals(document);
    initiateCopyImageUrl();

    if (style && title_page_content && header_content && footer_content) {
        initiatePrintPreview(style, title_page_content, header_content, footer_content);
    }
});
