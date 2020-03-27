import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import { createAngularPromiseWrapper } from "../../../../../../../tests/jest/angular-promise-wrapper.js";

describe("KanbanItemRestService -", function () {
    let wrapPromise, KanbanItemRestService, RestErrorService, $q, mockRestangular;
    beforeEach(function () {
        angular.mock.module(kanban_module, function ($provide) {
            $provide.decorator("RestErrorService", function ($delegate) {
                jest.spyOn($delegate, "reload").mockImplementation(() => {});

                return $delegate;
            });
        });

        let $rootScope, Restangular;
        angular.mock.inject(function (
            _$rootScope_,
            _$q_,
            _KanbanItemRestService_,
            _RestErrorService_,
            _Restangular_
        ) {
            $rootScope = _$rootScope_;
            $q = _$q_;
            KanbanItemRestService = _KanbanItemRestService_;
            RestErrorService = _RestErrorService_;
            Restangular = _Restangular_;
        });

        mockRestangular = {
            get: jest.fn(),
        };
        jest.spyOn(Restangular, "one").mockReturnValue(mockRestangular);

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    describe("getItem()", function () {
        it(`Given an item id, when I get the item,
            then a GET request will be made and a resolved promise will be returned`, async () => {
            const response = {
                id: 410,
                item_name: "paterfamiliarly",
                label: "Disaccustomed",
            };

            mockRestangular.get.mockReturnValue($q.resolve({ data: response }));

            const promise = KanbanItemRestService.getItem(410);

            expect(mockRestangular.get).toHaveBeenCalled();
            expect(await wrapPromise(promise)).toEqual(
                expect.objectContaining({
                    id: 410,
                    item_name: "paterfamiliarly",
                    label: "Disaccustomed",
                })
            );
        });

        it(`When there is an error with my request,
            then the error will be handled by RestErrorService and a rejected promise will be returned`, async () => {
            mockRestangular.get.mockReturnValue(
                $q.reject({ data: { error: 404, message: "Error" } })
            );

            const promise = KanbanItemRestService.getItem(410);

            await expect(wrapPromise(promise)).rejects.toBe(undefined);
            expect(RestErrorService.reload).toHaveBeenCalledWith(
                expect.objectContaining({
                    data: {
                        error: 404,
                        message: "Error",
                    },
                })
            );
        });
    });
});
