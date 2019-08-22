#!/usr/bin/env groovy

def getModifiedFilesSinceFirstParentOfCurrentCommit(String path) {
    dir (path) {
        return sh(
            returnStdout: true,
            script: """#!/bin/bash
            changes=\$(git diff --name-only --diff-filter=ACMTUXB --no-renames \${GIT_COMMIT}^)
            if [ \$(echo "\$changes" | wc -l) -ge 1000 ]; then
                echo -n "."
            else
                echo -n "\$changes" | tr '\n' ' '
            fi
            """
        )
    }
}

return this;
