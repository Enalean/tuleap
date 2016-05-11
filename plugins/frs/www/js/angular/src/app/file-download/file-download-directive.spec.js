describe("fileDownloadDirective -", function() {
    var element, $scope, isolateScope;

    beforeEach(function() {
        module('tuleap.frs');

        var $rootScope, $compile;

        inject(function( // eslint-disable-line angular/di
            _$rootScope_,
            _$compile_
        ) {
            $compile   = _$compile_;
            $rootScope = _$rootScope_;
        });

        // Compile the directive
        $scope = $rootScope.$new();
        $scope.file = {
            name        : 'alphabetist.tar.gz',
            download_url: '%2Fsenso%2Finflationism%3Fa%3Dsextillionth%26b%3Dunfishable%23tricostate'
        };
        element = '<div file-download="file"></div>';
        element = $compile(element)($scope);
        $scope.$apply();
        isolateScope = element.isolateScope();
    });

    it("Given a file with an encoded download_url property, when I create the directive then there will be a file_download_url on the scope with the decoded download url", function() {
        var decoded_file_download = decodeURIComponent($scope.file.download_url);

        expect(isolateScope.file_download_url).toEqual(decoded_file_download);
    });
});
