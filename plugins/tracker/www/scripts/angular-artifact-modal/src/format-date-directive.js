export default FormatDateDirective;

FormatDateDirective.$inject = ['amDateFormatFilter'];

function FormatDateDirective(amDateFormatFilter) {
    return {
        require: 'ngModel',
        link: function(scope, element, attr, ngModelCtrl) {
            ngModelCtrl.$formatters.unshift(function(viewValue) {
                return amDateFormatFilter(viewValue, 'YYYY-MM-DD');
            });
        }
    };
}
