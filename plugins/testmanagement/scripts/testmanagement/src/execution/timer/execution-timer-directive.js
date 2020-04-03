import moment from "moment";

import "./execution-timer-directive.tpl.html";

export default TimerDirective;

TimerDirective.$inject = ["$interval"];

function TimerDirective($interval) {
    return {
        restrict: "AE",
        templateUrl: "execution-timer-directive.tpl.html",
        require: "ngModel",
        scope: {
            execution_time: "=ngModel",
        },
        link: linkFunction,
    };

    function linkFunction(scope, iElement, iAttrs, ngModelCtrl) {
        var timer = null;
        startTimeButton();

        scope.$watch("execution_time_format", function () {
            ngModelCtrl.$setViewValue(scope.execution_time_format);
        });

        scope.toggleTimer = function () {
            if (!scope.time_is_started) {
                startTimeButton();
            } else {
                stopTimeButton();
            }
        };

        ngModelCtrl.$formatters.push(function (modelValue) {
            var duration = moment.duration(modelValue, "seconds");
            var hours = zeroPadding(duration.hours());
            var minutes = zeroPadding(duration.minutes());
            var seconds = zeroPadding(duration.seconds());

            scope.execution_time_format = hours + ":" + minutes + ":" + seconds;
            return scope.execution_time_format;
        });

        ngModelCtrl.$parsers.push(function (viewValue) {
            var duration = moment.duration(viewValue);
            return duration.asSeconds();
        });

        function startTimeButton() {
            scope.time_is_started = true;
            timer = $interval(function () {
                scope.execution_time++;
            }, 1000);
        }

        function stopTimeButton() {
            scope.time_is_started = false;
            $interval.cancel(timer);
        }

        function zeroPadding(number) {
            if (number.toString().length < 2) {
                number = "0" + number;
            }
            return number;
        }
    }
}
