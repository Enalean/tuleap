import "./file-diff.tpl.html";

import CodeMirror from "codemirror";
import FileDiffController from "./file-diff-controller.js";

export default FileDiffDirective;

FileDiffDirective.$inject = [
    "$window",
    "$state",
    "$compile",
    "gettextCatalog",
    "SharedPropertiesService",
    "FileDiffRestService",
    "TooltipService"
];

function FileDiffDirective(
    $window,
    $state,
    $compile,
    gettextCatalog,
    SharedPropertiesService,
    FileDiffRestService,
    TooltipService
) {
    $window.CodeMirror = CodeMirror;

    return {
        restrict: "A",
        scope: {},
        templateUrl: "file-diff.tpl.html",
        controller: FileDiffController,
        controllerAs: "diff",
        bindToController: true,
        link: linkFileDiffDirective
    };

    function linkFileDiffDirective(scope, element, attrs, diffController) {
        var pullRequest = SharedPropertiesService.getPullRequest();
        var filePath = $state.params.file_path;

        diffController.is_loading = true;

        FileDiffRestService.getUnidiff(pullRequest.id, filePath).then(function(data) {
            diffController.isBinaryFile = data.charset === "binary";

            if (!diffController.isBinaryFile) {
                var unidiffOptions = {
                    readOnly: true,
                    lineWrapping: true,
                    gutters: ["gutter-oldlines", "gutter-newlines"],
                    mode: data.mime_type
                };

                var unidiff = $window.CodeMirror(
                    document.getElementById("code-mirror-area"),
                    unidiffOptions
                );
                scope.$broadcast("code_mirror_initialized");
                displayUnidiff(unidiff, data.lines);

                data.inline_comments.forEach(function(comment) {
                    displayInlineComment(unidiff, comment, scope);
                });

                unidiff.on("gutterClick", showCommentForm);
            }

            diffController.is_loading = false;

            TooltipService.setupTooltips();
        });

        function showCommentForm(unidiff, lnb) {
            var elt = document.createElement("div");
            elt.innerHTML = `
                <div class="pull-request-new-inline-comment">
                    <i class="fa fa-plus-circle"></i>
                    <div class="arrow"></div>
                    <div class="pull-request-new-inline-comment-content">
                        <form>
                            <textarea type="text" class="tlp-textarea"></textarea>
                        </form>
                        <div class="pull-request-new-inline-comment-controls">
                            <button type="submit" class="tlp-button-primary">
                                <i class="tlp-button-icon fa fa-comment"></i> ${gettextCatalog.getString(
                                    "Comment"
                                )}
                            </button>
                            <button type="button" class="tlp-button-primary tlp-button-outline">
                                <i class="tlp-button-icon fa fa-times"></i> ${gettextCatalog.getString(
                                    "Cancel"
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            `;
            var commentFormWidget = unidiff.addLineWidget(lnb, elt, {
                coverGutter: true
            });

            elt.querySelector('button[type="submit"]').addEventListener("click", function(e) {
                e.preventDefault();
                var commentText = elt.querySelector("textarea").value;
                postComment(lnb, commentText).then(function(comment) {
                    displayInlineComment(unidiff, comment, scope);
                    commentFormWidget.clear();
                    TooltipService.setupTooltips();
                });
            });

            elt.querySelector('button[type="button"]').addEventListener("click", function() {
                commentFormWidget.clear();
            });
        }
    }

    function displayUnidiff(unidiff, fileLines) {
        let content = fileLines.map(({ content }) => content);
        content = content.join("\n");

        unidiff.setValue(content);

        fileLines.forEach(function(line, lnb) {
            if (line.old_offset) {
                unidiff.setGutterMarker(
                    lnb,
                    "gutter-oldlines",
                    document.createTextNode(line.old_offset)
                );
            } else {
                unidiff.addLineClass(lnb, "background", "pull-request-file-diff-added-lines");
            }
            if (line.new_offset) {
                unidiff.setGutterMarker(
                    lnb,
                    "gutter-newlines",
                    document.createTextNode(line.new_offset)
                );
            } else {
                unidiff.addLineClass(lnb, "background", "pull-request-file-diff-deleted-lines");
            }
        });
    }

    function displayInlineComment(unidiff, comment, scope) {
        var child_scope = scope.$new();
        child_scope.comment = comment;
        var inline_comment_element = $compile(
            '<inline-comment comment="comment"></inline-comment>'
        )(child_scope)[0];
        unidiff.addLineWidget(comment.unidiff_offset - 1, inline_comment_element, {
            coverGutter: true
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
            commentText
        );
    }
}
