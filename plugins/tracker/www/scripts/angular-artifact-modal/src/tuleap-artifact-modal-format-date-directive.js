angular
    .module('tuleap.artifact-modal')
    .directive('tuleapArtifactModalFormatDate', tuleapArtifactModalFormatDate)
    .directive('tuleapArtifactModalFormatDatetime', tuleapArtifactModalFormatDatetime);

tuleapArtifactModalFormatDate.$inject = ['amDateFormatFilter'];

function tuleapArtifactModalFormatDate(amDateFormatFilter) {
    return {
        require: 'ngModel',
        link: function(scope, element, attr, ngModelCtrl) {
            ngModelCtrl.$formatters.unshift(function(viewValue) {
                return amDateFormatFilter(viewValue, 'YYYY-MM-DD');
            });
        }
    };
}

tuleapArtifactModalFormatDatetime.$inject = ['amDateFormatFilter'];

function tuleapArtifactModalFormatDatetime(amDateFormatFilter) {
    return {
        require: 'ngModel',
        link: function(scope, element, attr, ngModelCtrl) {
            ngModelCtrl.$formatters.unshift(function(viewValue) {
                return amDateFormatFilter(viewValue, 'YYYY-MM-DD HH:mm');
            });
        }
    };
}