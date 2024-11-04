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

import DOMPurify from "dompurify";
import { retrieveTooltipData } from "./retrieve-tooltip-data";
import type { ElementWithCrossrefHref, SemiStructuredContent, Tooltip } from "./type";
import { isSemiStructuredContent } from "./type";

export function createTooltip(
    crossref: ElementWithCrossrefHref,
    options: { at_cursor_position?: boolean } = {},
): Tooltip {
    let fetching = false;
    let fetched = false;
    let show_tooltip = false;
    let timeout: number;
    const old_title = crossref.element.title;

    let tooltip: HTMLElement | undefined = undefined;

    crossref.element.addEventListener("mouseover", show);
    crossref.element.addEventListener("mouseout", hide);

    return {
        destroy: (): void => {
            crossref.element.removeEventListener("mouseover", show);
            crossref.element.removeEventListener("mouseout", hide);

            tooltip?.remove();
        },
    };

    function appendTooltipToBody(content: string | SemiStructuredContent): void {
        const { sanitize } = DOMPurify;
        const sanitize_options = {
            ADD_TAGS: ["tlp-relative-date"],
            ADD_ATTR: ["date", "absolute-date", "placement", "preference", "locale"],
        };

        fetched = true;
        tooltip = document.createElement("div");
        tooltip.style.display = "none";
        if (isSemiStructuredContent(content)) {
            tooltip.classList.add("codendi-tooltip");
            tooltip.classList.add("crossref-tooltip");

            if (content.accent_color.length > 0) {
                tooltip.classList.add("tlp-swatch-" + content.accent_color);
            }

            if (content.title_as_html.length > 0) {
                const header = document.createElement("div");
                header.classList.add("crossref-tooltip-header");
                header.innerHTML = sanitize(content.title_as_html, sanitize_options);
                tooltip.appendChild(header);
            }

            if (content.body_as_html) {
                const body = document.createElement("div");
                body.classList.add("crossref-tooltip-body");
                body.innerHTML = sanitize(content.body_as_html, sanitize_options);
                tooltip.appendChild(body);
            }
        } else {
            tooltip.classList.add("codendi-tooltip");
            tooltip.innerHTML = sanitize(content, sanitize_options);
        }
        document.body.appendChild(tooltip);
    }

    function show(mouse_event: MouseEvent): void {
        show_tooltip = true;

        if (timeout) {
            clearTimeout(timeout);
        }
        if (tooltip) {
            if (options.at_cursor_position) {
                const posX = Math.floor(mouse_event.pageX);
                const posY = Math.floor(mouse_event.pageY);
                tooltip.style.top = posY + 10 + "px";
                tooltip.style.left = posX + 10 + "px";
            } else {
                const box = crossref.element.getBoundingClientRect();
                const top = box.top + window.pageYOffset - document.documentElement.clientTop;
                const left = box.left + window.pageXOffset - document.documentElement.clientLeft;

                tooltip.style.top = Math.floor(top) + crossref.element.offsetHeight + "px";
                tooltip.style.left = Math.floor(left) + "px";
            }
            tooltip.style.display = "";
            if (mouse_event) {
                mouse_event.preventDefault();
            }
        } else if (!fetched) {
            fetchTooltip(mouse_event);
        }
    }

    function hide(): void {
        show_tooltip = false;
        if (tooltip) {
            timeout = window.setTimeout(() => {
                if (tooltip) {
                    tooltip.style.display = "none";
                }
            }, 200);
        }
    }

    async function fetchTooltip(mouse_event: MouseEvent): Promise<void> {
        if (fetching) {
            return;
        }

        fetching = true;
        if (old_title) {
            crossref.element.title = "";
        }
        const url = new URL(crossref.getHref());
        const data = await retrieveTooltipData(url);

        fetching = false;
        fetched = true;

        if (data) {
            appendTooltipToBody(data);
            if (show_tooltip) {
                show(mouse_event);
            }
        } else if (old_title) {
            crossref.element.title = old_title;
        }
    }
}
