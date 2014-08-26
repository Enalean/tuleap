(function () {
    angular
        .module('testing')
        .run(run);

    function run(amMoment) {
        amMoment.changeLanguage('en');
    }
})();