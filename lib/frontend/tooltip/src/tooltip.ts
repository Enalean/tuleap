/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
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

const selectors = ["a.cross-reference", "a[class^=direct-link-to]"];

interface SemiStructuredContent {
    readonly title_as_html: string;
    readonly accent_color: string;
    readonly body_as_html: string;
}

type SparklineHrefCollection = Record<string, HTMLAnchorElement[]>;

function isSemiStructuredContent(
    content: string | SemiStructuredContent,
): content is SemiStructuredContent {
    return typeof content !== "string";
}

export const loadTooltips = function (element?: HTMLElement, at_cursor_position?: boolean): void {
    const sparkline_hrefs: SparklineHrefCollection = {};

    const options = {
        at_cursor_position: Boolean(at_cursor_position),
    };

    const targets = (element || document).querySelectorAll(selectors.join(","));

    targets.forEach(function (a) {
        if (a instanceof HTMLAnchorElement) {
            createTooltip(a, a.href, options);
            if (sparkline_hrefs[a.href]) {
                sparkline_hrefs[a.href].push(a);
            } else {
                sparkline_hrefs[a.href] = [a];
            }
        }
    });
    loadSparklines(sparkline_hrefs);
};

// So that window.codendi.Tooltip.load is defined;
export const load = loadTooltips;

function createTooltip(
    element: HTMLElement,
    reference_url: string,
    options: { at_cursor_position?: boolean } = {},
): void {
    let fetching = false;
    let fetched = false;
    let show_tooltip = false;
    let timeout: number;
    const old_title = element.title;

    let tooltip: HTMLElement | undefined = undefined;

    element.addEventListener("mouseover", show);
    element.addEventListener("mouseout", hide);

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
                const box = element.getBoundingClientRect();
                const top = box.top + window.pageYOffset - document.documentElement.clientTop;
                const left = box.left + window.pageXOffset - document.documentElement.clientLeft;

                tooltip.style.top = Math.floor(top) + element.offsetHeight + "px";
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
            timeout = setTimeout(() => {
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
        element.title = "";
        const url = new URL(reference_url);
        const data = await retrieveTooltipData(url);

        fetching = false;
        fetched = true;

        if (data) {
            appendTooltipToBody(data);
            if (show_tooltip) {
                show(mouse_event);
            }
        } else {
            element.title = old_title;
        }
    }
}

export async function retrieveTooltipData(
    url: URL,
): Promise<string | SemiStructuredContent | undefined> {
    url.searchParams.append("as-json-for-tooltip", "1");

    const response = await fetch(url.toString(), {
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
    });
    if (!response.ok) {
        return Promise.resolve(undefined);
    }

    const content_type = response.headers.get("content-type");
    if (!content_type) {
        return Promise.resolve(undefined);
    }

    return content_type.toLowerCase().startsWith("application/json")
        ? response.json()
        : response.text();
}

function loadSparklines(sparkline_hrefs: SparklineHrefCollection): void {
    const hrefs = Object.keys(sparkline_hrefs);

    if (hrefs.length) {
        const sparklines = hrefs.reduce((sparklines, href) => {
            sparklines.append("sparklines[]", href);

            return sparklines;
        }, new FormData());

        fetch(`/sparklines.php`, {
            method: "post",
            body: sparklines,
        })
            .then((response) => {
                if (response.status !== 200) {
                    return {};
                }

                return response.json();
            })
            .then((data: Record<string, string>) => {
                for (const href in data) {
                    sparkline_hrefs[href].forEach(function (a) {
                        const img = document.createElement("img");
                        img.src = data[href];
                        img.style.verticalAlign = "middle";
                        img.style.paddingRight = "2px";
                        img.style.width = "10px";
                        img.style.height = "10px";

                        const img_container = a.querySelector(".cross-reference-title") || a;
                        img_container.prepend(img);
                    });
                }
            });
    }
}
