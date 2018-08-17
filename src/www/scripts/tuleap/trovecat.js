/**
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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

document.addEventListener("DOMContentLoaded", function() {
    var modal_add_element = document.getElementById("trove-cat-add"),
        button_modal_add_element = document.getElementById("add-project-category-button");

    if (modal_add_element) {
        var modal_add = tlp.modal(modal_add_element);

        button_modal_add_element.addEventListener("click", function() {
            modal_add.toggle();
        });
    }

    var matching_buttons = document.querySelectorAll(
        ".trovecats-edit-button, .trovecats-delete-button"
    );

    [].forEach.call(matching_buttons, function(button) {
        var modal_element = document.getElementById(button.dataset.modalId);

        if (modal_element) {
            var modal = tlp.modal(modal_element);

            button.addEventListener("click", function() {
                modal.toggle();
            });
        }
    });

    selectParentCategoryOption();
});

function selectParentCategoryOption() {
    var select_categories = document.getElementsByClassName(
        "trove-cats-modal-select-parent-category"
    );

    [].forEach.call(select_categories, function(select_element) {
        if (select_element.dataset) {
            var parent_id = select_element.dataset.parentTroveId;
            var id = select_element.dataset.id;
            var is_parent_hidden = false;

            for (var i = 0; i < select_element.length; i++) {
                var option = select_element[i];
                var selected = select_element.options[select_element.selectedIndex];
                var is_option_at_root_level = option.dataset.isTopLevelId;

                if (Boolean(is_option_at_root_level) === true) {
                    is_parent_hidden = false;
                }

                if (option.value === parent_id) {
                    option.setAttribute("selected", true);
                    allowMandatoryPropertyOnlyForRootCategories(option.value, id);

                    var is_option_top_level_id = selected.getAttribute("data-is-top-level-id");
                    var is_parent_mandatory = selected.getAttribute("data-is-parent-mandatory");
                    allowDisableOptionForChildUnderFirstParent(
                        is_option_top_level_id,
                        id,
                        is_parent_mandatory
                    );
                }

                is_parent_hidden = hideChildren(id, option, is_parent_hidden);
            }
        }

        select_element.addEventListener("change", function() {
            if (select_element.dataset) {
                allowMandatoryPropertyOnlyForRootCategories(select_element.value, id);

                var selected = select_element.options[select_element.selectedIndex];
                var is_option_top_level_id = selected.getAttribute("data-is-top-level-id");
                var is_parent_mandatory = selected.getAttribute("data-is-parent-mandatory");
                allowDisableOptionForChildUnderFirstParent(
                    is_option_top_level_id,
                    id,
                    is_parent_mandatory
                );
            }
        });
    });
}

function hideChildren(id, option_children_id, is_parent_hidden) {
    if (id === option_children_id.value || is_parent_hidden === true) {
        option_children_id.classList.add("trove-cats-option-hidden");
        option_children_id.setAttribute("disabled", true);
        return true;
    }
}

function allowMandatoryPropertyOnlyForRootCategories(select_id, id) {
    var mandatory_element = document.getElementById("is-mandatory-" + id),
        mandatory_checkbox = document.getElementById("trove-cats-modal-mandatory-checkbox-" + id);

    if (select_id !== "0") {
        mandatory_element.setAttribute("disabled", true);
        mandatory_checkbox.classList.add("tlp-form-element-disabled");
        mandatory_element.checked = false;
    } else {
        mandatory_element.removeAttribute("disabled");
        mandatory_checkbox.classList.remove("tlp-form-element-disabled");
    }
}

function allowDisableOptionForChildUnderFirstParent(select_is_top_level, id, is_parent_mandatory) {
    var disable_element = document.getElementById("trove-cats-modal-is-disable-" + id),
        disable_checkbox = document.getElementById("trove-cats-modal-disable-checkbox-" + id);

    if (Boolean(select_is_top_level) === false || Boolean(is_parent_mandatory) === false) {
        disable_element.setAttribute("disabled", true);
        disable_checkbox.classList.add("tlp-form-element-disabled");
        disable_element.checked = false;
    } else {
        disable_element.removeAttribute("disabled");
        disable_checkbox.classList.remove("tlp-form-element-disabled");
    }
}
