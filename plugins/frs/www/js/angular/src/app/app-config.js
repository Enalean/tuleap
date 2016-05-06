angular
    .module('tuleap.frs')
    .config(FrsConfig);

FrsConfig.$inject = [
    '$showdownProvider'
];

function FrsConfig(
    $showdownProvider
) {
    $showdownProvider.setOption('sanitize', true);
    $showdownProvider.setOption('simplifiedAutoLink', true);
}
