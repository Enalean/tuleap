#!/usr/bin/env groovy

def getModifiedFilesSinceFirstParentOfCurrentCommit(String path) {
    dir (path) {
        return sh(
            returnStdout: true,
            script: "git diff --name-only --diff-filter=ACMTUXB ${GIT_COMMIT}^ | tr '\n' ' '"
        )
    }
}

return this;