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

// Redefine a local codendi in order to stop adding more stuff in global variables
const local_codendi = {};

(function tooltipModule(codendi) {
    codendi.Tooltips = [];

    codendi.Tooltip = function (element, url, options) {
        this.element = element;
        this.url = url;
        this.options = options || {};

        this.fetching = false;
        this.fetched = false;
        this.old_title = this.element.title;

        this.tooltip = undefined;

        this.element.addEventListener("mouseover", this.show.bind(this));
        this.element.addEventListener("mouseout", this.hide.bind(this));
    };

    codendi.Tooltip.prototype.createTooltip = function (content) {
        const { sanitize } = DOMPurify;
        this.fetched = true;
        this.tooltip = document.createElement("div");
        this.tooltip.style.display = "none";
        if (typeof content.title_as_html !== "undefined") {
            this.tooltip.classList.add("codendi-tooltip");
            this.tooltip.classList.add("crossref-tooltip");

            if (content.accent_color.length > 0) {
                this.tooltip.classList.add("tlp-swatch-" + content.accent_color);
            }

            if (content.title_as_html.length > 0) {
                const header = document.createElement("div");
                header.classList.add("crossref-tooltip-header");
                header.innerHTML = sanitize(content.title_as_html);
                this.tooltip.appendChild(header);
            }

            if (content.body_as_html) {
                const body = document.createElement("div");
                body.classList.add("crossref-tooltip-body");
                body.innerHTML = sanitize(content.body_as_html);
                this.tooltip.appendChild(body);
            }
        } else {
            this.tooltip.classList.add("codendi-tooltip");
            this.tooltip.innerHTML = sanitize(content);
        }
        document.body.appendChild(this.tooltip);
    };

    codendi.Tooltip.prototype.show = function (mouse_event) {
        this.show_tooltip = true;

        if (this.timeout) {
            clearTimeout(this.timeout);
        }
        if (this.tooltip) {
            if (this.options.atCursorPosition) {
                const posX = Math.floor(mouse_event.pageX);
                const posY = Math.floor(mouse_event.pageY);
                this.tooltip.style.top = posY + 10 + "px";
                this.tooltip.style.left = posX + 10 + "px";
            } else {
                const box = this.element.getBoundingClientRect();
                let top = box.top + window.pageYOffset - document.documentElement.clientTop;
                let left = box.left + window.pageXOffset - document.documentElement.clientLeft;

                this.tooltip.style.top = Math.floor(top) + this.element.offsetHeight + "px";
                this.tooltip.style.left = Math.floor(left) + "px";
            }
            this.tooltip.style.display = "";
            if (mouse_event) {
                mouse_event.preventDefault();
            }
        } else if (!this.fetched) {
            this.fetch(mouse_event);
        }
    };

    codendi.Tooltip.prototype.hide = function () {
        this.show_tooltip = false;
        if (this.tooltip) {
            this.timeout = setTimeout(() => {
                this.tooltip.style.display = "none";
            }, 200);
        }
    };

    codendi.Tooltip.prototype.fetch = async function (mouse_event) {
        if (this.fetching) {
            return;
        }

        this.fetching = true;
        this.title = "";
        const url = new URL(this.url);
        url.searchParams.append("as-json-for-tooltip", "1");

        const response = await fetch(url.toString(), {
            credentials: "same-origin",
            headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        if (!response.ok) {
            return;
        }

        this.fetching = false;
        this.fetched = true;

        const data = response.headers
            .get("content-type")
            .toLowerCase()
            .startsWith("application/json")
            ? await response.json()
            : await response.text();

        if (data) {
            this.createTooltip(data);
            if (this.show_tooltip) {
                this.show(mouse_event);
            }
        } else {
            this.element.title = this.old_title;
        }
    };

    codendi.Tooltip.selectors = ["a.cross-reference", "a[class^=direct-link-to]"];

    codendi.Tooltip.load = function (element, at_cursor_position) {
        var sparkline_hrefs = {};

        var options = {
            atCursorPosition: at_cursor_position,
        };

        const targets = (element || document).querySelectorAll(codendi.Tooltip.selectors.join(","));

        targets.forEach(function (a) {
            codendi.Tooltips.push(new codendi.Tooltip(a, a.href, options));
            if (sparkline_hrefs[a.href]) {
                sparkline_hrefs[a.href].push(a);
            } else {
                sparkline_hrefs[a.href] = [a];
            }
        });
        loadSparklines(sparkline_hrefs);
    };

    function loadSparklines(sparkline_hrefs) {
        var hrefs = Object.keys(sparkline_hrefs);

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
                .then((data) => {
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
})(local_codendi);

export const loadTooltips = local_codendi.Tooltip.load;
// So that window.codendi.Tooltip.load is defined;
export const load = local_codendi.Tooltip.load;
