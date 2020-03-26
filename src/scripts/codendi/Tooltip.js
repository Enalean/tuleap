/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 * Copyright (c) Enalean, 2011-2018. All Rights Reserved.
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

/* global module:readonly require:readonly */

var codendi;

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    var jQuery = require("jquery");
    codendi = {};
    tooltipModule(jQuery, codendi);

    module.exports = {
        loadTooltips: codendi.Tooltip.load,
    };
} else {
    codendi = window.codendi || {};
    tooltipModule(window.jQuery, codendi);
}

function tooltipModule($, codendi) {
    codendi.Tooltips = [];

    codendi.Tooltip = function (element, url, options) {
        this.element = $(element);
        this.url = url;
        this.options = options || {};

        this.fetching = false;
        this.fetched = false;
        this.old_title = this.element.attr("title");

        this.tooltip = false;

        this.showEvent = $.proxy(this.show, this);
        this.element.on("mouseover", this.showEvent);
        this.hideEvent = $.proxy(this.hide, this);
        this.element.on("mouseout", this.hideEvent);
    };

    codendi.Tooltip.prototype.createTooltip = function (content) {
        this.fetched = true;
        this.tooltip = $("<div>").hide().addClass("codendi-tooltip").html(content);
        $(document.body).append(this.tooltip);
    };

    codendi.Tooltip.prototype.show = function (evt) {
        this.show_tooltip = true;
        var mouse_event = evt;

        if (this.timeout) {
            clearTimeout(this.timeout);
        }
        if (this.tooltip) {
            if (this.options.atCursorPosition) {
                var posX = mouse_event.pageX;
                var posY = mouse_event.pageY;
                this.tooltip.css({
                    top: posY + 10 + "px",
                    left: posX + 10 + "px",
                });
            } else {
                var pos = this.element.offset();
                this.tooltip.css({
                    top: pos.top + this.element.outerHeight() + "px",
                    left: pos.left + "px",
                });
            }
            this.tooltip.show();
            if (evt) {
                evt.preventDefault();
            }
        } else if (!this.fetched) {
            this.fetch(evt);
        }
    };

    codendi.Tooltip.prototype.hide = function () {
        this.show_tooltip = false;
        if (this.tooltip) {
            this.timeout = setTimeout(
                $.proxy(function () {
                    this.tooltip.hide();
                }, this),
                200
            );
        }
    };

    codendi.Tooltip.prototype.fetch = function (evt) {
        if (this.fetching) {
            return;
        }

        this.fetching = true;
        this.element.attr("title", "");
        $.get(this.url).done($.proxy(success, this));

        function success(data) {
            this.fetching = false;
            this.fetched = true;
            if (data) {
                this.createTooltip(data);
                if (this.show_tooltip) {
                    this.show(evt);
                }
            } else {
                this.element.attr("title", this.old_title);
            }
        }
    };

    codendi.Tooltip.selectors = ["a.cross-reference", "a[class^=direct-link-to]"];

    codendi.Tooltip.load = function (element, at_cursor_position) {
        var sparkline_hrefs = {};

        var options = {
            atCursorPosition: at_cursor_position,
        };

        $(codendi.Tooltip.selectors.join(",")).each(function (index, a) {
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
            $.post("/sparklines.php", {
                "sparklines[]": hrefs,
            }).done(function (data, statusText, xhr) {
                if (xhr.status !== 200) {
                    return;
                }

                for (var href in data) {
                    sparkline_hrefs[href].each(function (a) {
                        $(a).prepend(
                            $("<img>")
                                .attr("src", data[href])
                                .css("vertical-align", "middle")
                                .css("padding-right", "2px")
                                .css("width", "10px")
                                .css("height", "10px")
                        );
                    });
                }
            });
        }
    }
}
