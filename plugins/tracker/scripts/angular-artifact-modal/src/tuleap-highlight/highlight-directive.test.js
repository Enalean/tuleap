import highlight_module from "./highlight.js";
import angular from "angular";
import "angular-mocks";

describe("TuleapHighlightDirective", function () {
    var element, $scope, $timeout;

    beforeEach(function () {
        angular.mock.module(highlight_module, function ($provide) {
            $provide.decorator("$timeout", function ($delegate) {
                jest.spyOn($delegate, "cancel").mockImplementation(() => {});

                return $delegate;
            });
        });

        angular.mock.inject(function ($rootScope, $compile, _$timeout_) {
            $timeout = _$timeout_;
            // Compile the directive
            $scope = $rootScope.$new();

            $scope.field = {
                filtered_values: [],
            };

            element = '<select tuleap-highlight-directive="field.filtered_values"></select>';
            element = $compile(element)($scope);
            $scope.$digest();
        });
    });

    it("When the watched $scope property changes, then the element will be highlighted using CSS classes", function () {
        $scope.field.filtered_values = [{ id: 96 }];
        $scope.$apply();

        expect(element.hasClass("tuleap-highlight-transition")).toBeFalsy();
        expect(element.hasClass("tuleap-highlight")).toBeTruthy();

        $timeout.flush(1);

        expect(element.hasClass("tuleap-highlight-transition")).toBeTruthy();
        expect(element.hasClass("tuleap-highlight")).toBeFalsy();
    });

    it("When the watched $scope property does not change, then the element will not be highlighted", function () {
        $scope.field.filtered_values = [];
        $scope.$apply();

        expect(element.hasClass("tuleap-highlight-transition")).toBeFalsy();
        expect(element.hasClass("tuleap-highlight")).toBeFalsy();
    });

    it("When the directive is destroyed, then the timeout will be canceled", function () {
        $scope.field.filtered_values = [{ id: 10 }];
        $scope.$apply();

        $scope.$destroy();

        expect($timeout.cancel).toHaveBeenCalled();
    });
});
