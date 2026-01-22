#!/usr/bin/env groovy

/* 17266 is the number of characters before the list of modified files is cut in a "docker run" command */
def getModifiedFilesSinceFirstParentOfCurrentCommit(String path) {
    dir (path) {
        return sh(
            returnStdout: true,
            script: """#!/usr/bin/env bash
            changes=\$(git diff --name-only --diff-filter=ACMTUXB --no-renames \${GIT_COMMIT}^)
            if [ \$(echo "\$changes" | wc --chars) -ge 17266 ]; then
                echo -n "."
            else
                echo -n "\$changes" | tr '\n' ' '
            fi
            """
        )
    }
}

def authenticateOnTailscale(String credentialsId = 'tailscale-auth-key-public') {
    withCredentials([
        string(credentialsId: credentialsId, variable: 'TAILSCALE_AUTH_KEY')
    ]) {
        sh 'sudo tailscale up --accept-dns=false --auth-key="\$TAILSCALE_AUTH_KEY"'
    }
    sh 'sudo tailscale set --accept-dns=true'
    sh "timeout --kill-after=1s 300 sh -c 'while sudo tailscale dns query my.enalean.com. a | grep \"failed to query DNS\"; do sleep 1; done'"
}

def setupNixCache(String cache_name, String signing_public_key, String auth_token_credentials_id) {
    def credentials = []
    if (auth_token_credentials_id != '') {
        credentials = [string(credentialsId: auth_token_credentials_id, variable: 'AUTH_TOKEN')]
    }
    withCredentials(credentials) {
        dir ('sources') {
            sh """
                export CACHE_NAME="${cache_name}"
                export SIGNING_PUBLIC_KEY="${signing_public_key}"
                ./tools/utils/nix/setup-cache.sh
            """
        }
    }
}

def setupTuleapCommunityNixCache(String auth_token_credentials_id = '') {
    setupNixCache('tuleap-community', 'tuleap-community-20260122:Ns2HhaP2Xt5GrU3jqF53j159t0w3LVYEhZ+NbbXg5jI=', auth_token_credentials_id)
}

return this;
