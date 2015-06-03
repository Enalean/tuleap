angular
    .module('modal')
    .directive('fieldDate', FieldDate)
    .directive('fieldDatetime', FieldDatetime);

FieldDate.$inject = ['amDateFormatFilter'];

function FieldDate(amDateFormatFilter) {
    return {
        require: 'ngModel',
        link: function(scope, element, attr, ngModelCtrl) {
            ngModelCtrl.$formatters.unshift(function(viewValue) {
                return amDateFormatFilter(viewValue, 'YYYY-MM-DD');
            });
        }
    };
}

FieldDatetime.$inject = ['amDateFormatFilter'];

function FieldDatetime(amDateFormatFilter) {
    return {
        require: 'ngModel',
        link: function(scope, element, attr, ngModelCtrl) {
            ngModelCtrl.$formatters.unshift(function(viewValue) {
                return amDateFormatFilter(viewValue, 'YYYY-MM-DD HH:mm');
            });
        }
    };
}