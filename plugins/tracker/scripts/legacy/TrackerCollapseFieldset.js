/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

/* global codendi:readonly */

!(function ($) {
    function forceWidthToCurrentWidth($element) {
        $element.width($element.width());
    }

    function widthIsNotAnymoreForced($element) {
        $element.width("");
    }

    function saveState(evt, toggler, is_collapsing) {
        var id = $(toggler).attr("data-id");
        if (!$(evt.target).hasClass("fa-thumb-tack")) {
            return;
        }

        $.ajax({
            url: codendi.tracker.base_url + "?func=toggle-collapse&formElement=" + id,
        }).done(function () {
            var $always_collapsed = $(toggler).find(".tracker_artifact_fieldset_alwayscollapsed");
            if (is_collapsing) {
                $always_collapsed.addClass("active");
            } else {
                $always_collapsed.removeClass("active");
            }
        });
    }

    function onTogglerBefore(evt, toggler, is_collapsing) {
        var $parent = $(toggler).parent();
        if (!$parent.hasClass("tracker_artifact_fieldset")) {
            return;
        }

        if (is_collapsing) {
            forceWidthToCurrentWidth($parent);
        } else {
            widthIsNotAnymoreForced($parent);
        }

        saveState(evt, toggler, is_collapsing);
    }

    function generateTooltipOnTogglerIcon() {
        $(".tracker_artifact_fieldset_alwayscollapsed > i").hover(function () {
            var key = $(this).parent().hasClass("active")
                ? "always_expand_fieldset"
                : "always_collapse_fieldset";

            this.title = codendi.locales.tracker_artifact[key];
        });
    }

    $(document).ready(function () {
        codendi.Toggler.addBeforeListener(onTogglerBefore);
        generateTooltipOnTogglerIcon();
    });
})(window.jQuery);
