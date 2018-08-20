#!/usr/bin/env groovy

def prepareSources(def credentialsId) {
    withCredentials([usernamePassword(
        credentialsId: credentialsId,
        passwordVariable: 'NPM_PASSWORD',
        usernameVariable: 'NPM_USER'
    )]) {
        sh 'docker run --rm -e NPM_REGISTRY="$NPM_REGISTRY" -e NPM_USER="$NPM_USER" -e NPM_PASSWORD="$NPM_PASSWORD" -e NPM_EMAIL="$NPM_EMAIL" -v "$WORKSPACE/sources/":/tuleap -v "$WORKSPACE/sources/":/output --tmpfs /tmp/tuleap_build:rw,noexec,nosuid --read-only $DOCKER_REGISTRY/tuleap-generated-files-builder dev'
    }
}

def runFilesStatusChangesDetection(String name_of_verified_files, String verified_files) {
    dir ('sources') {
        sh "tests/files_status_checker/verify.sh '${name_of_verified_files}' ${verified_files}"
    }
}

def runSimpleTestTests(String version) {
    sh "make -C $WORKSPACE/sources simpletest-${version}-ci"
}

def runPHPUnitTests(String version) {
    sh "make -C $WORKSPACE/sources phpunit-ci-${version}"
}

def runKarmaTests(String name, String path) {
    sh """
    cid="\$(docker create -v \$WORKSPACE/sources:/sources:ro --security-opt seccomp=\$WORKSPACE/sources/tests/karma/seccomp_chrome.json \$DOCKER_REGISTRY/enalean/tuleap-test-karma:latest --path ${path})"
    docker start --attach "\$cid" || true
    mkdir -p 'results/karma'
    docker cp "\$cid":/output/test-results.xml results/karma/test-${name}-results.xml || true
    docker rm -fv "\$cid"
    """
}

def runRESTTests(String version) {
    sh """
    mkdir -p results/api-rest/${version}
    docker run --rm -v \$WORKSPACE/sources:/usr/share/tuleap:ro --mount type=tmpfs,destination=/tmp -v \$WORKSPACE/results/api-rest/${version}:/output \$DOCKER_REGISTRY/enalean/tuleap-test-rest:${version}
    """
}

def runSOAPTests(String name, String imageVersion) {
    sh """
    cid="\$(docker create -v \$WORKSPACE/sources:/usr/share/tuleap:ro \$DOCKER_REGISTRY/enalean/tuleap-test-soap:${imageVersion})"
    docker start --attach "\$cid" || true
    mkdir -p 'results/api-soap/${name}/'
    docker cp "\$cid":/output/soap_tests.xml results/api-soap/${name}/soap_tests.xml || true
    docker rm -fv "\$cid"
    """
}

def runEndToEndTests(String flavor) {
    dir ('sources') {
        sh "tests/e2e/${flavor}/wrap.sh '$WORKSPACE/results/e2e/${flavor}/'"
    }
}

def runBuildAndRun(String os) {
    dir ('sources') {
        sh "OS='${os}' tests/build_and_run/test.sh"
    }
}

def runJavascriptCodingStandards() {
    sh 'docker run --rm -v $WORKSPACE/sources:/sources:ro $DOCKER_REGISTRY/prettier-checker "**/*.{js,vue}"'
}

return this;
