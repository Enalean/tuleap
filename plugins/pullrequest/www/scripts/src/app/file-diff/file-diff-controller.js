import CodeMirror from "codemirror";

export default FileDiffController;

FileDiffController.$inject = [
    "$element",
    "$state",
    "$scope",
    "$compile",
    "FileDiffRestService",
    "SharedPropertiesService",
    "TooltipService"
];

function FileDiffController(
    $element,
    $state,
    $scope,
    $compile,
    FileDiffRestService,
    SharedPropertiesService,
    TooltipService
) {
    const self = this;
    Object.assign(self, {
        is_loading: true,
        is_binary_file: false,
        file_path: $state.params.file_path,
        pull_request: SharedPropertiesService.getPullRequest(),
        unidiff: null,
        $onInit: init,
        displayInlineComment,
        submitNewInlineCommentCallback
    });

    function init() {
        FileDiffRestService.getUnidiff(self.pull_request.id, self.file_path)
            .then(data => {
                self.is_binary_file = data.charset === "binary";

                if (self.is_binary_file) {
                    return;
                }

                const unidiff_options = {
                    readOnly: true,
                    lineWrapping: true,
                    gutters: ["gutter-oldlines", "gutter-newlines"],
                    mode: data.mime_type
                };

                self.unidiff = CodeMirror(
                    $element[0].querySelector("#code-mirror-area"),
                    unidiff_options
                );
                $scope.$broadcast("code_mirror_initialized");
                displayUnidiff(data.lines);

                data.inline_comments.forEach(comment => {
                    self.displayInlineComment(comment);
                });

                self.unidiff.on("gutterClick", showCommentForm);

                TooltipService.setupTooltips();
            })
            .finally(() => {
                self.is_loading = false;
            });
    }

    function displayUnidiff(file_lines) {
        let content = file_lines.map(({ content }) => content);
        content = content.join("\n");

        self.unidiff.setValue(content);

        file_lines.forEach((line, line_number) => {
            if (line.old_offset) {
                self.unidiff.setGutterMarker(
                    line_number,
                    "gutter-oldlines",
                    document.createTextNode(line.old_offset)
                );
            } else {
                self.unidiff.addLineClass(
                    line_number,
                    "background",
                    "pull-request-file-diff-added-lines"
                );
            }
            if (line.new_offset) {
                self.unidiff.setGutterMarker(
                    line_number,
                    "gutter-newlines",
                    document.createTextNode(line.new_offset)
                );
            } else {
                self.unidiff.addLineClass(
                    line_number,
                    "background",
                    "pull-request-file-diff-deleted-lines"
                );
            }
        });
    }

    function displayInlineComment(comment) {
        const child_scope = $scope.$new();
        child_scope.comment = comment;
        const inline_comment_element = $compile(
            '<inline-comment comment="comment"></inline-comment>'
        )(child_scope)[0];
        self.unidiff.addLineWidget(comment.unidiff_offset - 1, inline_comment_element, {
            coverGutter: true
        });
    }

    function showCommentForm(unidiff, line_number) {
        const child_scope = $scope.$new();
        const new_inline_comment_element = $compile(`
            <new-inline-comment submit-callback="diff.submitNewInlineCommentCallback"
                                codemirror-widget="codemirror_widget"
                                line-number=${line_number}
            ></new-inline-comment>
        `)(child_scope)[0];
        child_scope.codemirror_widget = unidiff.addLineWidget(
            line_number,
            new_inline_comment_element,
            {
                coverGutter: true
            }
        );
    }

    function submitNewInlineCommentCallback(line_number, comment_text) {
        return postComment(line_number, comment_text).then(comment => {
            self.displayInlineComment(comment);
            TooltipService.setupTooltips();
        });
    }

    function postComment(line_number, comment_text) {
        const unidiff_offset = Number(line_number) + 1;
        return FileDiffRestService.postInlineComment(
            self.pull_request.id,
            self.file_path,
            unidiff_offset,
            comment_text
        );
    }
}
