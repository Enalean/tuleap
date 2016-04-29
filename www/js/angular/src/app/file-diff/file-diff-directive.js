angular
    .module('tuleap.pull-request')
    .directive('fileDiff', FileDiffDirective);

FileDiffDirective.$inject = [
    '$window',
    'lodash',
    '$state',
    'SharedPropertiesService',
    'FileDiffRestService'
];

function FileDiffDirective(
    $window,
    lodash,
    $state,
    SharedPropertiesService,
    FileDiffRestService
) {
    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'file-diff/file-diff.tpl.html',
        controller      : 'FileDiffController as diff',
        bindToController: true,
        link            : function(scope, element) {
            var unidiffOptions = {
                readOnly: true,
                gutters : ['gutter-oldlines', 'gutter-newlines']
            };
            var unidiff = $window.CodeMirror.fromTextArea(element.find('textarea')[0], unidiffOptions);

            var pullRequest = SharedPropertiesService.getPullRequest();
            var filePath = $state.params.file_path;
            FileDiffRestService.getUnidiff(pullRequest.id, filePath).then(function(data) {
                var content = lodash.map(data.lines, 'content').join('\n');
                unidiff.setValue(content);

                data.lines.forEach(function(line, lnb) {
                    if (line.old_offset) {
                        unidiff.setGutterMarker(lnb, 'gutter-oldlines',
                            document.createTextNode(line.old_offset)); // eslint-disable-line angular/document-service
                    } else {
                        unidiff.addLineClass(lnb, 'background', 'added-lines');
                    }
                    if (line.new_offset) {
                        unidiff.setGutterMarker(lnb, 'gutter-newlines',
                            document.createTextNode(line.new_offset)); // eslint-disable-line angular/document-service
                    } else {
                        unidiff.addLineClass(lnb, 'background', 'deleted-lines');
                    }
                });
            });
        }
    };
}
