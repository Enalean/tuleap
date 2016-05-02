angular
    .module('tuleap.pull-request')
    .directive('fileDiff', FileDiffDirective);

FileDiffDirective.$inject = [
    '$window',
    'lodash',
    '$state',
    '$interpolate',
    'SharedPropertiesService',
    'FileDiffRestService'
];

function FileDiffDirective(
    $window,
    lodash,
    $state,
    $interpolate,
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

                var inlineCommentTemplate = $interpolate('<div class="inline-comment">'
                    + '<div class="info"><div class="author">'
                    + '<div class="avatar"><img src="{{ user.avatar_url }}"></div>'
                    + '<span>{{ user.username }}</span></div>'
                    + '<small class="post-date">{{ post_date | date: "short" }}</small></div>'
                    + '<div class="content">{{ content }}</div>'
                    + '</div>');

                data.inline_comments.forEach(function(comment) {
                    var elt = document.createElement('div'); // eslint-disable-line angular/document-service
                    comment.content = comment.content.replace(/(?:\r\n|\r|\n)/g, '<br/>');
                    elt.innerHTML = inlineCommentTemplate(comment);
                    unidiff.addLineWidget(comment.unidiff_offset - 1, elt, {
                        coverGutter: true
                    });
                });
            });
        }
    };
}
