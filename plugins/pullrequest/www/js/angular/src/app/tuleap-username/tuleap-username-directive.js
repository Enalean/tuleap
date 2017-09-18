angular
    .module('tuleap.pull-request')
    .directive('tuleapUsername', TuleapUsernameDirective);

function TuleapUsernameDirective() {
    return {
        restrict: 'AE',
        scope   : {
            username: '='
        },
        templateUrl: 'tuleap-username/tuleap-username.tpl.html'
    };
}

