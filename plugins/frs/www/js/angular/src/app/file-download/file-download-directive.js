angular
    .module('tuleap.frs')
    .directive('fileDownload', fileDownloadDirective);

function fileDownloadDirective() {
    return {
        restrict: 'A',
        scope   : {
            file: '=fileDownload'
        },
        link    : link,
        template: '<a ng-href="{{ file_download_url }}" target="_blank">{{ file.name }}</a>'
    };

    function link($scope) {
        $scope.file_download_url = decodeURIComponent($scope.file.download_url);
    }
}
