/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import tpl from "./users-result-template.tpl.html";
import { select2 } from "tlp";
import { isDefined } from "angular";
import { has, remove } from "lodash-es";
import { searchUsers } from "../../rest/rest-service";
import { initOpenListFieldLabelAndSelectIds } from "./open-list-field-ids-initializer";

export default OpenListFieldController;

OpenListFieldController.$inject = ["$element", "$compile", "$rootScope"];

function OpenListFieldController($element, $compile, $rootScope) {
    const self = this;
    Object.assign(self, {
        $onInit: init,
        handleUsersValueSelection,
        handleUsersValueUnselection,
        newAnonymousUser,
        templateUserResult,
        templateUserSelection,
        isRequiredAndEmpty,
        getFieldValue,
    });

    function init() {
        var open_list_element = $element[0].querySelector(".tuleap-artifact-modal-open-list-users");
        if (!open_list_element) {
            return;
        }

        initOpenListFieldLabelAndSelectIds(
            $element[0],
            open_list_element,
            self.value_model.field_id,
        );

        select2(open_list_element, {
            minimumInputLength: 3,
            placeholder: self.field.hint,
            allowClear: true,
            tags: true,
            createTag: self.newAnonymousUser,
            ajax: {
                transport: function (params, success, failure) {
                    return searchUsers(params.data.term).then(
                        function (response) {
                            success(response);
                        },
                        function (error) {
                            failure(error);
                        },
                    );
                },
            },
            templateResult: self.templateUserResult,
            templateSelection: self.templateUserSelection,
        });

        $element.on("select2:selecting", self.handleUsersValueSelection);

        $element.on("select2:unselecting", self.handleUsersValueUnselection);
    }

    function isRequiredAndEmpty() {
        return self.field.required && self.value_model.value.bind_value_objects.length === 0;
    }

    function templateUserSelection(result) {
        // This happens for users that were previous artifact values in edition_mode
        // They come from the ng-repeat in the template and only have an ID or an email
        // because <option> can not contain other HTML tags
        var user_representation = getUserRepresentationForInitialSelection(result);
        user_representation = isDefined(user_representation) ? user_representation : result;

        return templateOpenListUser(user_representation);
    }

    function templateUserResult(result, container) {
        if (result.loading === true) {
            return result.text;
        }

        container.classList.add("open-list-field-search-container");
        return templateOpenListUser(result);
    }

    function templateOpenListUser(result) {
        var user_display_template = tpl;
        var isolate_scope = $rootScope.$new();
        isolate_scope.result = result;
        return $compile(user_display_template)(isolate_scope);
    }

    function getUserRepresentationForInitialSelection(result) {
        return self.value_model.value.bind_value_objects.find((value_object) => {
            if (value_object.id) {
                return result.id === value_object.id.toString();
            }
            return result.text === value_object.email;
        });
    }

    function handleUsersValueSelection(event) {
        var new_selection = event.params.args.data;

        self.value_model.value.bind_value_objects.push(new_selection);
    }

    function handleUsersValueUnselection(event) {
        var removed_selection = event.params.args.data;
        var is_anonymous = false;

        if (has(removed_selection, "is_anonymous")) {
            is_anonymous = removed_selection.is_anonymous;
        } else if (has(removed_selection, "element")) {
            is_anonymous = removed_selection.element.attributes["is-anonymous"].value === "true";
        }

        remove(self.value_model.value.bind_value_objects, function (value_object) {
            if (is_anonymous) {
                return value_object.email === removed_selection.id;
            }
            return value_object.id === Number.parseInt(removed_selection.id, 10);
        });
    }

    function newAnonymousUser(new_open_value) {
        var term = new_open_value.term.trim();

        if (term === "") {
            return null;
        }

        return {
            id: term,
            display_name: term,
            email: term,
            is_anonymous: true,
        };
    }

    function getFieldValue(field_value) {
        var value = field_value.email;

        if (field_value.id) {
            value = field_value.id;
        }

        return value;
    }
}
