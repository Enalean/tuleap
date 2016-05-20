angular
    .module('tuleap.pull-request')
    .service('FilepathsService', FilepathsService);

FilepathsService.$inject = [
    'lodash'
];

function FilepathsService(
    lodash
) {
    var self = this;
    var filepaths = [];

    lodash.extend(self, {
        setFilepaths: setFilepaths,
        previous    : previous,
        next        : next
    });

    function setFilepaths(files) {
        filepaths = lodash.map(files, 'path');
    }

    function previous(filepath) {
        var index = filepaths.indexOf(filepath);
        return (index > 0) ? filepaths[index - 1] : '';
    }

    function next(filepath) {
        var index = filepaths.indexOf(filepath);
        return (index < filepaths.length - 1) ? filepaths[index + 1] : '';
    }
}
