export default FormatDatetimeDirective;

FormatDatetimeDirective.$inject = ['amDateFormatFilter'];

function FormatDatetimeDirective(amDateFormatFilter) {
    return {
        require: 'ngModel',
        link: function(scope, element, attr, ngModelCtrl) {
            ngModelCtrl.$formatters.unshift(function(viewValue) {
                return amDateFormatFilter(viewValue, 'YYYY-MM-DD HH:mm');
            });
        }
    };
}
