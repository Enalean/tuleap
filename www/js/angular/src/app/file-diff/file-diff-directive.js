angular
    .module('tuleap.pull-request')
    .directive('fileDiff', FileDiffDirective);

FileDiffDirective.$inject = [
    '$window',
    'lodash',
    '$state',
    '$uiViewScroll',
    '$interpolate',
    'SharedPropertiesService',
    'FileDiffRestService'
];

function FileDiffDirective(
    $window,
    lodash,
    $state,
    $uiViewScroll,
    $interpolate,
    SharedPropertiesService,
    FileDiffRestService
) {
    function linkFileDiffDirective(scope, element, attrs, diffController) {
        var pullRequest = SharedPropertiesService.getPullRequest();
        var filePath = $state.params.file_path;

        FileDiffRestService.getUnidiff(pullRequest.id, filePath).then(function(data) {
            diffController.isBinaryFile = data.charset === 'binary';

            if (! diffController.isBinaryFile) {
                var unidiffOptions = {
                    readOnly    : true,
                    lineWrapping: true,
                    gutters     : ['gutter-oldlines', 'gutter-newlines'],
                    mode        : data.mime_type
                };
                var unidiff = $window.CodeMirror.fromTextArea(element.find('textarea')[0], unidiffOptions);
                displayUnidiff(unidiff, data.lines);

                data.inline_comments.forEach(function(comment) {
                    displayInlineComment(unidiff, comment);
                });

                unidiff.on('gutterClick', showCommentForm);
            }

            $uiViewScroll(element);
        });
    }

    function displayUnidiff(unidiff, fileLines) {
        var content = lodash.map(fileLines, 'content').join('\n');
        unidiff.setValue(content);

        fileLines.forEach(function(line, lnb) {
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
    }

    var inlineCommentTemplate = $interpolate('<div class="inline-comment">'
        + '<div class="avatar"><img src="{{ user.avatar_url }}"></div>'
        + '<div class="arrow"></div>'
        + '<div class="comment">'
        + '<div class="info">'
        + '<span class="author">{{ user.display_name }}</span>'
        + '<span class="post-date">{{ post_date | date: "short" }}</span>'
        + '</div>'
        + '<div class="content">{{ content }}</div>'
        + '</div>'
        + '</div>');

    function displayInlineComment(unidiff, comment) {
        var elt = document.createElement('div'); // eslint-disable-line angular/document-service
        comment.content = comment.content.replace(/(?:\r\n|\r|\n)/g, '<br/>');
        elt.innerHTML = inlineCommentTemplate(comment);
        unidiff.addLineWidget(comment.unidiff_offset - 1, elt, {
            coverGutter: true
        });
    }

    function showCommentForm(unidiff, lnb) {
        var elt = document.createElement('div'); // eslint-disable-line angular/document-service
        elt.innerHTML = '<div class="new-inline-comment">'
            + '<i class="icon-plus-sign"></i>'
            + '<div class="arrow"></div>'
            + '<div class="new-inline-comment-content">'
            + '<form>'
            + '<textarea></textarea>'
            + '</form>'
            + '<div class="controls">'
            + '<button type="submit" class="btn btn-primary"><i class="icon-comment"></i> Comment</button>'
            + '<button type="button" class="btn"><i class="icon-remove"></i> Cancel</button>'
            + '</div></div></div>';
        var commentFormWidget = unidiff.addLineWidget(lnb, elt, {
            coverGutter: true
        });

        elt.querySelector('button[type="submit"]').addEventListener('click', function(e) {
            e.preventDefault();
            var commentText = elt.querySelector('textarea').value;
            postComment(lnb, commentText).then(function(comment) {
                displayInlineComment(unidiff, comment);
                commentFormWidget.clear();
            });
        });

        elt.querySelector('button[type="button"]').addEventListener('click', function() {
            commentFormWidget.clear();
        });
    }

    function postComment(lnb, commentText) {
        var pullRequest = SharedPropertiesService.getPullRequest();
        var filePath = $state.params.file_path;
        var unidiff_offset = lnb + 1;
        return FileDiffRestService.postInlineComment(
            pullRequest.id,
            filePath,
            unidiff_offset,
            commentText);
    }

    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'file-diff/file-diff.tpl.html',
        controller      : 'FileDiffController as diff',
        bindToController: true,
        link            : linkFileDiffDirective
    };
}
