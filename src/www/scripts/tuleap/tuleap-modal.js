/**
 * Copyright (c) Enalean SAS - 2014 - Present. All rights reserved
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

/* global jQuery:readonly tuleap:readonly */
/**
 * Handle Tuleap modal
 */
(function ($) {
    tuleap.modal = {
        settings: {
            beforeClose: function () {},
        },

        init: function (settings) {
            var self = this;
            Object.extend(this.settings, settings);

            $(".tuleap-modal").show();
            this.setPanelHeight();

            $(".tuleap-modal-side-panel:first-child .tuleap-modal-side-panel-grip").click(
                function () {
                    self.toggleLeftSidePanel($(this));
                },
            );
            $(".tuleap-modal-side-panel:last-child .tuleap-modal-side-panel-grip").click(
                function () {
                    self.toggleRightSidePanel($(this));
                },
            );
            $(".tuleap-modal-close").click(function () {
                self.closeModal();
            });

            return this;
        },

        getDOMElement: function () {
            return $(".tuleap-modal")[0];
        },

        showLoad: function () {
            $("body").append('<div class="tuleap-modal-loading"></div>');
        },

        hideLoad: function () {
            $(".tuleap-modal-loading").remove();
        },

        toggleLeftSidePanel: function (grip) {
            var panel_content = grip.siblings(".tuleap-modal-side-panel-content");
            panel_content.toggle();
            var new_margin_left;

            if (panel_content.css("display") === "block") {
                new_margin_left =
                    parseInt($(".tuleap-modal").css("margin-left"), 10) -
                    parseInt(panel_content.css("width"), 10);
                $(".tuleap-modal").css("margin-left", new_margin_left + "px");
            } else {
                new_margin_left =
                    parseInt($(".tuleap-modal").css("margin-left"), 10) +
                    parseInt(panel_content.css("width"), 10);
                $(".tuleap-modal").css("margin-left", new_margin_left + "px");
            }
        },

        toggleRightSidePanel: function (grip) {
            grip.siblings(".tuleap-modal-side-panel-content").toggle();
        },

        setPanelHeight: function () {
            var computed_height = $(".tuleap-modal-main-panel").outerHeight();
            if (computed_height >= $(window).height()) {
                computed_height = $(window).height();
            }

            this.resizeContentPanels(computed_height);
            $(".tuleap-modal-side-panel").css("height", computed_height);
            $(".tuleap-modal-side-panel-grip > span").css("width", computed_height);

            $(".tuleap-modal-side-panel-content-actions").css(
                "height",
                $(".tuleap-modal-actions").outerHeight(),
            );
        },

        resizeContentPanels: function (computed_height) {
            if (computed_height < $(window).height()) {
                $(".tuleap-modal").css({
                    height: computed_height + "px",
                    top: "50%",
                    marginTop: "-" + computed_height / 2 + "px",
                });
            } else {
                var content_height =
                    computed_height -
                    $(".tuleap-modal-actions").outerHeight() -
                    $(".tuleap-modal-title").outerHeight() -
                    2 * parseInt($(".tuleap-modal-actions").css("padding-left"), 10);
                $(".tuleap-modal-content, .tuleap-modal-side-panel-content-content").css({
                    height: content_height + "px",
                });

                $(".tuleap-modal").css({
                    height: computed_height + "px",
                    top: "0",
                    marginTop: "0",
                });
            }
        },

        closeModal: function () {
            this.settings.beforeClose();

            $(".tuleap-modal-background, .tuleap-modal").fadeOut(150).remove();
        },
    };

    $(document).ready(function () {
        $(window).resize(function () {
            if ($(".tuleap-modal").length > 0) {
                tuleap.modal.setPanelHeight();
            }
        });
    });
})(jQuery);
