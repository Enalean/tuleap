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

import tuleap_artifact_modal_module from "../../tuleap-artifact-modal.js";
import angular from "angular";
import "angular-mocks";

import BaseUsersOpenListController from "./users-open-list-field-controller.js";
import * as tlp from "tlp";

jest.mock("tlp");

describe("UsersOpenListFieldController", () => {
    let $element, $scope, $rootScope, $compile, $compileSecondStep, UsersOpenListFieldController;

    beforeEach(() => {
        angular.mock.module(tuleap_artifact_modal_module);

        var $controller;
        angular.mock.inject(function (_$controller_, _$rootScope_) {
            $controller = _$controller_;
            $rootScope = _$rootScope_;

            $scope = $rootScope.$new();
        });

        $element = angular.element("<div></div>");

        $compileSecondStep = jest.fn(() => "compiled template");
        $compile = jest.fn(() => $compileSecondStep);

        UsersOpenListFieldController = $controller(BaseUsersOpenListController, {
            $element,
            $scope,
            $rootScope,
            $compile,
        });

        UsersOpenListFieldController.field = {
            hint: "abnormalness",
            loading: false,
        };
        UsersOpenListFieldController.value_model = {
            value: {
                bind_value_objects: [],
            },
        };
    });

    describe("init() -", function () {
        it("When initializing the controller, then a select2 will be created and its events will be listened", function () {
            $element.append(
                angular.element('<select class="tuleap-artifact-modal-open-list-users"></select>'),
            );
            jest.spyOn($element, "on").mockImplementation(() => {});
            const tlpSelect2Spy = jest.spyOn(tlp, "select2");

            UsersOpenListFieldController.$onInit();

            expect(tlpSelect2Spy).toHaveBeenCalled();
            expect($element.on).toHaveBeenCalledWith("select2:selecting", expect.any(Function));
            expect($element.on).toHaveBeenCalledWith("select2:unselecting", expect.any(Function));
        });
    });

    describe("isRequiredAndEmpty() -", function () {
        it("Given that the field was required and the value model empty, then it will return true", function () {
            UsersOpenListFieldController.field.required = true;
            UsersOpenListFieldController.value_model.value.bind_value_objects = [];

            expect(UsersOpenListFieldController.isRequiredAndEmpty()).toBe(true);
        });

        it("Given that the field was required and the value model had a value, then it will return false", function () {
            UsersOpenListFieldController.value_model.value.bind_value_objects = [
                {
                    avatar_url:
                        "https://semilooper.com/italianization/tenible?a=perihepatitis&b=unpalatal#stealthwise",
                    display_name: "Damaris Rubison (drubison)",
                    email: "bdelloid@pajama.org",
                    id: 213,
                    is_anonymous: false,
                    ldap_id: "213",
                    real_name: "Damaris Rubison",
                    status: "A",
                    uri: "/users/drubison",
                    username: "drubison",
                },
            ];
            UsersOpenListFieldController.field.required = true;

            expect(UsersOpenListFieldController.isRequiredAndEmpty()).toBe(false);
        });

        it("Given that the field was not required and the value model empty, then it will return false", function () {
            UsersOpenListFieldController.field.required = false;
            UsersOpenListFieldController.value_model.value.bind_value_objects = [];

            expect(UsersOpenListFieldController.isRequiredAndEmpty()).toBe(false);
        });
    });

    describe("templateUserResult() -", function () {
        it("Given a 'searching' result, then the result text will be returned", function () {
            var searching_result = {
                loading: true,
                text: "searching...",
            };

            var result = UsersOpenListFieldController.templateUserResult(searching_result);

            expect(result).toBe("searching...");
        });

        it("Given a user result coming from the REST route, a class will be added to the container and a template will be rendered for it", function () {
            jest.spyOn($rootScope, "$new").mockImplementation(() => {});
            var classList_add = jest.fn();
            var container = {
                classList: {
                    add: classList_add,
                },
            };

            var user_representation = {
                avatar_url:
                    "https://pharmacopsychology.com/dephysicalize/aberdeen?a=diffarreation&b=shutoff#conchoid",
                display_name: "Sally Sadak (ssadak)",
                email: "bdelloid@pajama.org",
                id: 610,
                is_anonymous: false,
                ldap_id: "610",
                real_name: "Sally Sadak",
                status: "A",
                uri: "/users/ssadak",
                username: "ssadak",
            };

            var isolate_scope = {
                result: user_representation,
            };
            $rootScope.$new.mockReturnValue(isolate_scope);

            var result = UsersOpenListFieldController.templateUserResult(
                user_representation,
                container,
            );

            expect($rootScope.$new).toHaveBeenCalled();
            expect($compile).toHaveBeenCalled();
            expect($compileSecondStep).toHaveBeenCalledWith(isolate_scope);
            expect(result).toBe("compiled template");
            expect(classList_add).toHaveBeenCalled();
        });
    });

    describe("templateUserSelection() -", function () {
        beforeEach(function () {
            jest.spyOn($rootScope, "$new").mockImplementation(() => {});
        });

        it("Given a user selection coming from the REST route, then a template will be rendered for it", function () {
            var user_representation = {
                avatar_url:
                    "https://pharmacopsychology.com/dephysicalize/aberdeen?a=diffarreation&b=shutoff#conchoid",
                display_name: "Sally Sadak (ssadak)",
                email: "bdelloid@pajama.org",
                id: 610,
                is_anonymous: false,
                ldap_id: "610",
                real_name: "Sally Sadak",
                status: "A",
                uri: "/users/ssadak",
                username: "ssadak",
            };

            var isolate_scope = {
                result: user_representation,
            };
            $rootScope.$new.mockReturnValue(isolate_scope);

            var result = UsersOpenListFieldController.templateUserSelection(user_representation);

            expect($rootScope.$new).toHaveBeenCalled();
            expect($compile).toHaveBeenCalled();
            expect($compileSecondStep).toHaveBeenCalledWith(isolate_scope);
            expect(result).toBe("compiled template");
        });

        it("Given a user selection with only an ID (coming from the template ng-repeat), then the user's data from the value model will be used and a template will be rendered with it", function () {
            var user_representation = {
                avatar_url: "http://compulsitor.com/formulae/gearbox?a=balanus&b=tuna#chirotony",
                display_name: "Odessa Chmielewski (ochmielewski)",
                email: "bdelloid@pajama.org",
                id: 553,
                is_anonymous: false,
                ldap_id: "553",
                real_name: "Odessa Chmielewski",
                status: "A",
                uri: "/users/ochmielewski",
                username: "ochmielewski",
            };

            UsersOpenListFieldController.value_model.value.bind_value_objects = [
                user_representation,
            ];

            var isolate_scope = {
                result: user_representation,
            };
            $rootScope.$new.mockReturnValue(isolate_scope);

            var result = UsersOpenListFieldController.templateUserSelection({ id: 553 });

            expect($rootScope.$new).toHaveBeenCalled();
            expect($compile).toHaveBeenCalled();
            expect($compileSecondStep).toHaveBeenCalledWith(isolate_scope);
            expect(result).toBe("compiled template");
        });

        it("Given a user selection with only a text (anonymous user coming from the template ng-repeat), then the user's data from the value model will be used and a template will be rendered with it", function () {
            var user_representation = {
                id: null,
                avatar_url: "http://Dioon.com/themes/common/images/avatar_default.png",
                display_name: "archprelatical@sublinear.net",
                email: "archprelatical@sublinear.net",
                is_anonymous: true,
                ldap_id: null,
                real_name: null,
                status: null,
                uri: null,
                username: null,
            };

            UsersOpenListFieldController.value_model.value.bind_value_objects = [
                user_representation,
            ];

            var isolate_scope = {
                result: user_representation,
            };
            $rootScope.$new.mockReturnValue(isolate_scope);

            var result = UsersOpenListFieldController.templateUserSelection({
                text: "archprelatical@sublinear.net",
            });

            expect($rootScope.$new).toHaveBeenCalled();
            expect($compile).toHaveBeenCalled();
            expect($compileSecondStep).toHaveBeenCalledWith(isolate_scope);
            expect(result).toBe("compiled template");
        });
    });

    describe("handleUsersValueSelection() -", function () {
        it("Given an event with a user selection, then it will be pushed in the value_model", function () {
            var event = {
                params: {
                    name: "select",
                    args: {
                        data: {
                            avatar_url:
                                "https://despiteously.com/teleprinter/pronominal?a=monitory&b=ellipsograph#unrealize",
                            display_name: "Regina Gogel (rgogel)",
                            email: "polyhedron@writter.org",
                            id: 990,
                            is_anonymous: false,
                            ldap_id: "990",
                            real_name: "Regina Gogel",
                            status: "A",
                            uri: "/users/rgogel",
                            username: "rgogel",
                        },
                    },
                },
            };

            UsersOpenListFieldController.handleUsersValueSelection(event);

            expect(UsersOpenListFieldController.value_model.value.bind_value_objects).toEqual([
                {
                    avatar_url:
                        "https://despiteously.com/teleprinter/pronominal?a=monitory&b=ellipsograph#unrealize",
                    display_name: "Regina Gogel (rgogel)",
                    email: "polyhedron@writter.org",
                    id: 990,
                    is_anonymous: false,
                    ldap_id: "990",
                    real_name: "Regina Gogel",
                    status: "A",
                    uri: "/users/rgogel",
                    username: "rgogel",
                },
            ]);
        });
    });

    describe("handleUsersValueUnselection() -", function () {
        it("Given an event with a user unselection, then it will be removed from the value model", function () {
            UsersOpenListFieldController.value_model.value.bind_value_objects = [
                {
                    avatar_url:
                        "http://ophthalmorrhexis.com/cominform/catatoniac?a=chaetotactic&b=strayer#comfortably",
                    display_name: "Gene Telman (gtelman)",
                    email: "cracidae@ticer.org",
                    id: 887,
                    is_anonymous: false,
                    ldap_id: "887",
                    real_name: "Gene Telman",
                    status: "A",
                    uri: "/users/gtelman",
                    username: "gtelman",
                },
            ];
            var event = {
                params: {
                    name: "unselect",
                    args: {
                        data: {
                            id: "887",
                            is_anonymous: false,
                        },
                    },
                },
            };

            UsersOpenListFieldController.handleUsersValueUnselection(event);

            expect(UsersOpenListFieldController.value_model.value.bind_value_objects).toEqual([]);
        });

        it("Given an event with a user unselection (at the first index), then it will be removed from the value model", function () {
            UsersOpenListFieldController.value_model.value.bind_value_objects = [
                {
                    avatar_url:
                        "http://ophthalmorrhexis.com/cominform/catatoniac?a=chaetotactic&b=strayer#comfortably",
                    display_name: "Gene Telman (gtelman)",
                    email: "cracidae@ticer.org",
                    id: 887,
                    is_anonymous: false,
                    ldap_id: "887",
                    real_name: "Gene Telman",
                    status: "A",
                    uri: "/users/gtelman",
                    username: "gtelman",
                },
                {
                    avatar_url:
                        "http://ophthalmorrhexis.com/cominform/catatoniac?a=chaetotactic&b=strayer#comfortably",
                    display_name: "Gene Telman2 (gtelman2)",
                    email: "cracidae2@ticer.org",
                    id: 888,
                    is_anonymous: false,
                    ldap_id: "888",
                    real_name: "Gene Telman2",
                    status: "A",
                    uri: "/users/gtelman2",
                    username: "gtelman2",
                },
            ];
            var event = {
                params: {
                    name: "unselect",
                    args: {
                        data: {
                            id: "887",
                            is_anonymous: false,
                        },
                    },
                },
            };

            UsersOpenListFieldController.handleUsersValueUnselection(event);

            expect(UsersOpenListFieldController.value_model.value.bind_value_objects).toEqual([
                {
                    avatar_url:
                        "http://ophthalmorrhexis.com/cominform/catatoniac?a=chaetotactic&b=strayer#comfortably",
                    display_name: "Gene Telman2 (gtelman2)",
                    email: "cracidae2@ticer.org",
                    id: 888,
                    is_anonymous: false,
                    ldap_id: "888",
                    real_name: "Gene Telman2",
                    status: "A",
                    uri: "/users/gtelman2",
                    username: "gtelman2",
                },
            ]);
        });

        it("Given an event with an anonymous user unselection (only email), then it will be removed from the value model", function () {
            UsersOpenListFieldController.value_model.value.bind_value_objects = [
                {
                    id: "",
                    avatar_url: "http://areolate.com/themes/common/images/avatar_default.png",
                    display_name: "ithomiinae@heaper.net",
                    email: "ithomiinae@heaper.net",
                    is_anonymous: true,
                    ldap_id: null,
                    real_name: null,
                    status: null,
                    uri: null,
                    username: null,
                },
            ];
            var event = {
                params: {
                    name: "unselect",
                    args: {
                        data: {
                            id: "ithomiinae@heaper.net",
                            is_anonymous: true,
                        },
                    },
                },
            };

            UsersOpenListFieldController.handleUsersValueUnselection(event);

            expect(UsersOpenListFieldController.value_model.value.bind_value_objects).toEqual([]);
        });

        it("Given an event with an anonymous user unselection (only email, at the first index), then it will be removed from the value model", function () {
            UsersOpenListFieldController.value_model.value.bind_value_objects = [
                {
                    avatar_url:
                        "http://ophthalmorrhexis.com/cominform/catatoniac?a=chaetotactic&b=strayer#comfortably",
                    display_name: "Gene Telman (gtelman)",
                    email: "cracidae@ticer.org",
                    id: 887,
                    is_anonymous: false,
                    ldap_id: "887",
                    real_name: "Gene Telman",
                    status: "A",
                    uri: "/users/gtelman",
                    username: "gtelman",
                },
                {
                    id: "",
                    avatar_url: "http://areolate.com/themes/common/images/avatar_default.png",
                    display_name: "ithomiinae@heaper.net",
                    email: "ithomiinae@heaper.net",
                    is_anonymous: true,
                    ldap_id: null,
                    real_name: null,
                    status: null,
                    uri: null,
                    username: null,
                },
            ];
            var event = {
                params: {
                    name: "unselect",
                    args: {
                        data: {
                            id: "ithomiinae@heaper.net",
                            is_anonymous: true,
                        },
                    },
                },
            };

            UsersOpenListFieldController.handleUsersValueUnselection(event);

            expect(UsersOpenListFieldController.value_model.value.bind_value_objects).toEqual([
                {
                    avatar_url:
                        "http://ophthalmorrhexis.com/cominform/catatoniac?a=chaetotactic&b=strayer#comfortably",
                    display_name: "Gene Telman (gtelman)",
                    email: "cracidae@ticer.org",
                    id: 887,
                    is_anonymous: false,
                    ldap_id: "887",
                    real_name: "Gene Telman",
                    status: "A",
                    uri: "/users/gtelman",
                    username: "gtelman",
                },
            ]);
        });

        it("Given an event with an anonymous user unselection (only email, already in the DOM), then it will be removed from the value model", function () {
            UsersOpenListFieldController.value_model.value.bind_value_objects = [
                {
                    id: "",
                    avatar_url: "http://areolate.com/themes/common/images/avatar_default.png",
                    display_name: "ithomiinae@heaper.net",
                    email: "ithomiinae@heaper.net",
                    is_anonymous: true,
                    ldap_id: null,
                    real_name: null,
                    status: null,
                    uri: null,
                    username: null,
                },
            ];
            var event = {
                params: {
                    name: "unselect",
                    args: {
                        data: {
                            id: "ithomiinae@heaper.net",
                            element: {
                                attributes: {
                                    "is-anonymous": {
                                        value: "true",
                                    },
                                },
                            },
                        },
                    },
                },
            };

            UsersOpenListFieldController.handleUsersValueUnselection(event);

            expect(UsersOpenListFieldController.value_model.value.bind_value_objects).toEqual([]);
        });
    });

    describe("newAnonymousUser() -", function () {
        it("Given blank space, then it returns null", function () {
            var new_open_value = {
                term: "   ",
            };

            var result = UsersOpenListFieldController.newAnonymousUser(new_open_value);

            expect(result).toBeNull();
        });

        it("Given a string, then it returns an object with 'id', 'display_name', 'email' and 'is_anonymous' attributes", function () {
            var new_open_value = {
                term: "besiegingly@discovery.com",
            };

            var result = UsersOpenListFieldController.newAnonymousUser(new_open_value);

            expect(result).toEqual({
                id: "besiegingly@discovery.com",
                display_name: "besiegingly@discovery.com",
                email: "besiegingly@discovery.com",
                is_anonymous: true,
            });
        });

        it("Given a string with blank space, it trims it and returns an object", function () {
            var new_open_value = {
                term: " synangium@alchemy.com  ",
            };

            var result = UsersOpenListFieldController.newAnonymousUser(new_open_value);

            expect(result).toEqual({
                id: "synangium@alchemy.com",
                display_name: "synangium@alchemy.com",
                email: "synangium@alchemy.com",
                is_anonymous: true,
            });
        });
    });
});
