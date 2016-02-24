angular
    .module('tuleap.pull-request')
    .config(FileDiffConfig);

FileDiffConfig.$inject = [
    '$stateProvider'
];

function FileDiffConfig(
    $stateProvider
) {
    $stateProvider.state('diff', {
        url   : '/diff-{file_path}',
        parent: 'pull-request',
        views : {
            'file-diff@pull-request': {
                template: '<div file-diff id="file-diff"></div>'
            }
        }
    });
}
