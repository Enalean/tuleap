#!/usr/bin/env groovy

/* 17266 is the number of characters before the list of modified files is cut in a "docker run" command */
def getModifiedFilesSinceFirstParentOfCurrentCommit(String path) {
    dir (path) {
        return sh(
            returnStdout: true,
            script: """#!/usr/bin/env bash
            changes=\$(git diff --name-only --diff-filter=ACMTUXB --no-renames \${GIT_COMMIT}^ | grep -v -E '^VERSION\$|Makefile\$')
            if [ \$(echo "\$changes" | wc --chars) -ge 17266 ]; then
                echo -n "."
            else
                echo -n "\$changes" | tr '\n' ' '
            fi
            """
        )
    }
}

return this;
