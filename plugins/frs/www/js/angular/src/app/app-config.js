angular
    .module('tuleap.frs')
    .config(FrsConfig);

FrsConfig.$inject = [
    '$showdownProvider',
    '$urlRouterProvider'
];

function FrsConfig(
    $showdownProvider,
    $urlRouterProvider
) {
    $showdownProvider.setOption('sanitize', true);
    $showdownProvider.setOption('simplifiedAutoLink', true);

    $urlRouterProvider.otherwise('/');
}
