import "./tuleap-username.tpl.html";

export default TuleapUsernameDirective;

function TuleapUsernameDirective() {
    return {
        restrict: "AE",
        scope: {
            username: "=",
        },
        templateUrl: "tuleap-username.tpl.html",
    };
}
