/**
 * Copyright (c) Enalean SAS - 2014. All rights reserved
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

!(function ($) {
    $(document).ready(function () {
        $("#select-all").click(function () {
            updateRepositoriesCheckboxesState(this.checked);
            updateMassChangeButtonState();
        });

        $(".repository-ids").click(function () {
            updateMassChangeButtonState();
            updateSelectAllCheckboxState();
        });

        $("body").click(function (event) {
            if (
                $(event.target).parents(".update-n-repositories").length === 0 &&
                $(event.target).parents(".popover.in").length === 0
            ) {
                $(".update-n-repositories > span").popover("hide");
            }
        });

        $(".check-repository-mirror").on("change", changeCheckboxChangeState);

        $(".check-all-mirror").on("click", function (evt) {
            var mirror_id = $(this).data("id");

            $("[data-mirror-id=" + mirror_id + "]")
                .prop("checked", true)
                .each(changeCheckboxChangeState);

            evt.preventDefault();
        });

        $(".uncheck-all-mirror").on("click", function (evt) {
            var mirror_id = $(this).data("id");

            $("[data-mirror-id=" + mirror_id + "]")
                .prop("checked", false)
                .each(changeCheckboxChangeState);

            evt.preventDefault();
        });

        $(".check-all-repository").on("click", function (evt) {
            var repository_id = $(this).data("id");

            $("[data-repository-id=" + repository_id + "]")
                .prop("checked", true)
                .each(changeCheckboxChangeState);

            evt.preventDefault();
        });

        $(".uncheck-all-repository").on("click", function (evt) {
            var repository_id = $(this).data("id");

            $("[data-repository-id=" + repository_id + "]")
                .prop("checked", false)
                .each(changeCheckboxChangeState);

            evt.preventDefault();
        });
    });

    function changeCheckboxChangeState() {
        var has_changed =
            ($(this).is(":checked") && $(this).parent("td").hasClass("was-unused")) ||
            (!$(this).is(":checked") && $(this).parent("td").hasClass("was-used"));

        if (has_changed) {
            $(this).parent("td").addClass("has-changed");
        } else {
            $(this).parent("td").removeClass("has-changed");
        }
    }

    function updateRepositoriesCheckboxesState(state) {
        $(".repository-ids").prop("checked", state);
    }

    function updateMassChangeButtonState() {
        if ($(".repository-ids:checked").length === 0) {
            $("#go-to-mass-change").prop("disabled", true);
        } else {
            $("#go-to-mass-change").prop("disabled", false);
        }
    }

    function updateSelectAllCheckboxState() {
        if ($(".repository-ids").length === $(".repository-ids:checked").length) {
            $("#select-all").prop("checked", true);
        } else {
            $("#select-all").prop("checked", false);
        }
    }
})(window.jQuery);
