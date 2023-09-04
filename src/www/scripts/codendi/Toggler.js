/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2011. All rights reserved
 * Copyright (c) Enalean SAS, 2011 - Present. All rights reserved
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

/* global jQuery:readonly codendi:readonly */

(function ($, codendi) {
    codendi.Toggler = {
        init: function (element, force_display, force_ajax) {
            $(element)
                .find(".toggler, .toggler-hide, .toggler-noajax, .toggler-hide-noajax")
                .each(function () {
                    codendi.Toggler.load($(this), force_display, force_ajax);
                });
        },
        load: function (toggler, force_display, force_ajax) {
            if (force_display) {
                var was_noajax =
                    toggler.hasClass("toggler-hide-noajax") || toggler.hasClass("toggler-noajax");
                toggler.removeClass("toggler-hide");
                toggler.removeClass("toggler-hide-noajax");
                toggler.removeClass("toggler");
                toggler.removeClass("toggler-noajax");
                if (force_display == "show") {
                    if (was_noajax) {
                        toggler.addClass("toggler");
                    } else {
                        toggler.addClass("toggler-noajax");
                    }
                } else {
                    if (was_noajax) {
                        toggler.addClass("toggler-hide");
                    } else {
                        toggler.addClass("toggler-hide-noajax");
                    }
                }
            }

            if (force_ajax) {
                var was_hide =
                    toggler.hasClass("toggler-hide") || toggler.hasClass("toggler-hide-noajax");
                toggler.removeClass("toggler-hide");
                toggler.removeClass("toggler-hide-noajax");
                toggler.removeClass("toggler");
                toggler.removeClass("toggler-noajax");
                if (force_ajax == "ajax") {
                    if (was_hide) {
                        toggler.addClass("toggler-hide");
                    } else {
                        toggler.addClass("toggler");
                    }
                } else {
                    if (was_hide) {
                        toggler.addClass("toggler-hide-noajax");
                    } else {
                        toggler.addClass("toggler-noajax");
                    }
                }
            }

            //prehide or preshow depending on the initial state of the toggler
            toggleNextSiblings(
                toggler,
                toggler.hasClass("toggler-hide") || toggler.hasClass("toggler-hide-noajax"),
            );

            toggler.on("click", function (evt) {
                var is_collapsing =
                    toggler.hasClass("toggler") || toggler.hasClass("toggler-noajax");

                codendi.Toggler.before(evt, toggler, is_collapsing);

                //toggle next siblings
                toggleNextSiblings(toggler, is_collapsing);

                //toggle the state
                if (toggler.hasClass("toggler-noajax") || toggler.hasClass("toggler-hide-noajax")) {
                    toggler.toggleClass("toggler-noajax").toggleClass("toggler-hide-noajax");
                } else {
                    toggler.toggleClass("toggler").toggleClass("toggler-hide");
                    //save the state with ajax only if the toggler has an id
                    if (toggler.attr("id")) {
                        $.get("/toggler.php", {
                            id: toggler.attr("id"),
                        });
                    }
                }
            });

            function toggleNextSiblings(toggler, is_collapsing) {
                if (is_collapsing) {
                    toggler.nextAll().hide();
                } else {
                    toggler.nextAll().show();
                }
            }
        },
        before_listeners: [],
        addBeforeListener: function (callback) {
            codendi.Toggler.before_listeners.push(callback);
        },
        before: function (evt, toggler, is_collapsing) {
            codendi.Toggler.before_listeners.forEach(function (callback) {
                callback(evt, toggler, is_collapsing);
            });
        },
    };

    $(document).ready(function () {
        codendi.Toggler.init(document.body);
    });
})(jQuery, codendi);
