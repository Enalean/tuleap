/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import jQuery from "jquery";

import { initGettext } from "../../../../src/www/scripts/tuleap/gettext/gettext-init";

(async function($): Promise<void> {
    const language = document.body.dataset.userLocale;
    if (language === undefined) {
        throw new Error("Not able to find the user language.");
    }

    const gettext_provider = await initGettext(language, "tuleap-hudson_git", locale =>
        import(/* webpackChunkName: "git-administration-po-" */ `../po/${locale}.po`)
    );

    function confirmDeletionPopover(): void {
        $(".remove-jenkins-server").each(function() {
            const id = $(this).data("popover-id");

            // eslint-disable-next-line @typescript-eslint/ban-ts-ignore
            // @ts-ignore
            $(this).popover({
                container: ".git-administration-jenkins-server",
                title: gettext_provider.gettext("Wait a minute..."),
                content: $("#" + id).html()
            });
        });
    }

    function dismissPopover(): void {
        // eslint-disable-next-line @typescript-eslint/ban-ts-ignore
        // @ts-ignore
        $(".remove-jenkins-server").popover("hide");
    }

    function bindShowPopover(): void {
        $(".remove-jenkins-server").click(function(event) {
            event.preventDefault();

            dismissPopover();

            // eslint-disable-next-line @typescript-eslint/ban-ts-ignore
            // @ts-ignore
            $(this).popover("show");
        });
    }

    $(function(): void {
        confirmDeletionPopover();

        bindShowPopover();

        $("body").on("click", function(event) {
            if ($(event.target).hasClass("dismiss-popover")) {
                dismissPopover();
            }

            if (
                $(event.target).data("toggle") !== "popover" &&
                $(event.target).parents(".popover.in").length === 0 &&
                $(event.target).parents('[data-toggle="popover"]').length === 0
            ) {
                dismissPopover();
            }
        });
    });
})(jQuery);
