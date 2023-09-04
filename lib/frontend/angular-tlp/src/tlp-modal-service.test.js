import angular from "angular";
import "angular-mocks";
import * as tlp from "@tuleap/tlp-modal";
import angular_tlp_module from "./index.js";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

describe("TlpModalService -", function () {
    let wrapPromise, TlpModalService, $templateCache, $document, $rootScope, $q;

    beforeEach(function () {
        angular.mock.module(angular_tlp_module);

        angular.mock.inject(
            function (_TlpModalService_, _$templateCache_, _$document_, _$rootScope_, _$q_) {
                TlpModalService = _TlpModalService_;
                $templateCache = _$templateCache_;
                $document = _$document_;
                $rootScope = _$rootScope_;
                $q = _$q_;
            },
        );

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    describe("open() -", function () {
        describe("validate options -", function () {
            it("Given no options were provided, then an error will be thrown with the usage help message", function () {
                expect(TlpModalService.open).toThrow();
            });

            it("Given no templateUrl option was provided, then an error will be thrown with the usage help message", function () {
                expect(function () {
                    TlpModalService.open({
                        controller: function () {},
                        controllerAs: "plethora",
                    });
                }).toThrow();
            });

            it("Given no controller option was provided, then an error will be thrown with the usage help message", function () {
                expect(function () {
                    TlpModalService.open({
                        templateUrl: "discord/unmustered.tpl.html",
                        controllerAs: "plethora",
                    });
                }).toThrow();
            });

            it("Given no controllerAs option was provided, then an error will be thrown with the usage help message", function () {
                expect(function () {
                    TlpModalService.open({
                        templateUrl: "discord/unmustered.tpl.html",
                        controller: function () {},
                    });
                }).toThrow();
            });

            it("Given templateUrl option was provided but was not a string, then an error will be thrown with the usage help message", function () {
                expect(function () {
                    TlpModalService.open({
                        templateUrl: null,
                        controller: function () {},
                        controllerAs: "plethora",
                    });
                }).toThrow();
            });

            it("Given controllerAs option was provided but was not a string, then an error will be thrown with the usage help message", function () {
                expect(function () {
                    TlpModalService.open({
                        templateUrl: "discord/unmustered.tpl.html",
                        controller: function () {},
                        controllerAs: null,
                    });
                }).toThrow();
            });

            it("Given tlpModalOptions was provided but was not an object, then an error will be thrown with the usage help message", function () {
                expect(function () {
                    TlpModalService.open({
                        templateUrl: "discord/unmustered.tpl.html",
                        controller: function () {},
                        controllerAs: "plethora",
                        tlpModalOptions: null,
                    });
                }).toThrow();
            });

            it("Given resolve option was provided but was not an object, then an error will be thrown with the usage help message", function () {
                expect(function () {
                    TlpModalService.open({
                        templateUrl: "discord/unmustered.tpl.html",
                        controller: function () {},
                        controllerAs: "plethora",
                        resolve: null,
                    });
                }).toThrow();
            });
        });

        it("Given a template that was not stored in $templateCache, then an error will be thrown with a message", function () {
            expect(function () {
                TlpModalService.open({
                    templateUrl: "discord/unmustered.tpl.html",
                    controller: function () {},
                    controllerAs: "plethora",
                });
            }).toThrow(Error, "templateUrl was not stored in templateCache. Did you import it ?");
        });

        describe("Given a template that was stored in $templateCache, a controller and controllerAs", function () {
            let fake_modal_object, tlp_modal;

            beforeEach(function () {
                var fake_template = '<div class="tlp-modal"></div>';
                $templateCache.put("discord/unmustered.tpl.html", fake_template);
                fake_modal_object = {
                    show: jest.fn(),
                    addEventListener: jest.fn(),
                };
                tlp_modal = jest.spyOn(tlp, "createModal").mockReturnValue(fake_modal_object);
            });

            afterEach(function () {
                document.querySelector(".tlp-modal")?.remove();
            });

            it(`when I open a new modal,
                then a promise will be resolved with the TLP modal object
                and the modal template will be appended to <body>`, async () => {
                var promise = TlpModalService.open({
                    templateUrl: "discord/unmustered.tpl.html",
                    controller: function () {},
                    controllerAs: "plethora",
                });

                await expect(wrapPromise(promise)).resolves.toEqual(fake_modal_object);
                expect(document.querySelector(".tlp-modal")).toBeDefined();
                expect(tlp_modal).toHaveBeenCalledWith(expect.any(Node), {});
                expect(fake_modal_object.show).toHaveBeenCalled();
                expect(fake_modal_object.addEventListener).toHaveBeenCalled();
            });

            it(`when I open a new modal,
                then the controller will be available on the created scope with the given controllerAs alias`, async () => {
                var scope = $rootScope.$new();
                jest.spyOn($rootScope, "$new").mockReturnValue(scope);

                var promise = TlpModalService.open({
                    templateUrl: "discord/unmustered.tpl.html",
                    controller: function () {},
                    controllerAs: "plethora",
                });

                await wrapPromise(promise);
                expect(scope.plethora).toBeDefined();
            });

            it(`and tlpModalOptions,
                when I open a new modal,
                then the tlpModalOptions will be passed to tlp.createModal()
                and a promise will be resolved`, async () => {
                var tlp_modal_options = { keyboard: false };

                var promise = TlpModalService.open({
                    templateUrl: "discord/unmustered.tpl.html",
                    controller: function () {},
                    controllerAs: "plethora",
                    tlpModalOptions: tlp_modal_options,
                });

                await wrapPromise(promise);
                expect(tlp_modal).toHaveBeenCalledWith(expect.any(Node), tlp_modal_options);
            });

            // eslint-disable-next-line prettier/prettier
            it(`and a resolve object, when I open a new modal, then the modal_instance and the resolve functions will be injected in the modal's controller`, (done) => { // eslint-disable-line jest/no-done-callback
                var first_resolve_function = function () {};
                var second_resolve_function = function () {};
                var controller = function (modal_instance, noology, incoagulable) {
                    expect(modal_instance).toEqual({});
                    expect(noology).toBe(first_resolve_function);
                    expect(incoagulable).toBe(second_resolve_function);

                    setTimeout(function () {
                        // The controller is instanciated first, the template is next.
                        // modal_instance receives a tlp_modal property after the template is compiled
                        expect(modal_instance.tlp_modal).toBe(fake_modal_object);
                        done();
                    }, 0);
                };

                var resolve_option = {
                    noology: first_resolve_function,
                    incoagulable: second_resolve_function,
                };
                var promise = TlpModalService.open({
                    templateUrl: "discord/unmustered.tpl.html",
                    controller: controller,
                    controllerAs: "plethora",
                    resolve: resolve_option,
                });

                wrapPromise(promise);
            });

            it(`and a resolve object with a promise,
                when I open a new modal,
                then the modal will only be instanciated after resolving the promise`, () => {
                var fake_resolve_function = function () {};
                var promise_resolved = $q.when(fake_resolve_function);

                var controller = function (upholden) {
                    expect(upholden).toBe(fake_resolve_function);
                };

                var resolve_option = {
                    upholden: promise_resolved,
                };
                var promise = TlpModalService.open({
                    templateUrl: "discord/unmustered.tpl.html",
                    controller: controller,
                    controllerAs: "plethora",
                    resolve: resolve_option,
                });

                return wrapPromise(promise);
            });

            it(`and a resolve object with a promise,
                when the resolve is rejected,
                then the modal will not be opened`, () => {
                var fake_resolve_function = function () {};
                var promise_resolved = $q.reject(fake_resolve_function);

                var controller = function (upholden) {
                    expect(upholden).toBe(fake_resolve_function);
                };

                var resolve_option = {
                    upholden: promise_resolved,
                };
                var promise = TlpModalService.open({
                    templateUrl: "discord/unmustered.tpl.html",
                    controller: controller,
                    controllerAs: "plethora",
                    resolve: resolve_option,
                });

                return wrapPromise(promise).catch((error) => {
                    // eslint-disable-next-line jest/no-conditional-expect
                    expect(error).toEqual(fake_resolve_function);
                });
            });

            it(`and given I had opened a new modal,
                when I close that modal,
                then the modal template will be removed from <body>`, async () => {
                fake_modal_object.addEventListener.mockImplementation(function (event, callback) {
                    callback();
                });

                var promise = TlpModalService.open({
                    templateUrl: "discord/unmustered.tpl.html",
                    controller: function () {},
                    controllerAs: "plethora",
                });

                await wrapPromise(promise);
                expect($document.find(".tlp-modal")).toHaveLength(0);
            });
        });
    });
});
