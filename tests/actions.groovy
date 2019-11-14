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

def runFilesStatusChangesDetection(String repository_to_inspect, String name_of_verified_files, String verified_files) {
    dir ('sources') {
        sh "tests/files_status_checker/verify.sh '${repository_to_inspect}' '${name_of_verified_files}' ${verified_files}"
    }
}

def runSimpleTestTests(String version) {
    sh "make -C $WORKSPACE/sources simpletest-${version}-ci"
}

def runPHPUnitTests(String version) {
    sh "make -C $WORKSPACE/sources phpunit-ci-${version}"
}

def runJestTests(String name, String path) {
    sh """
    mkdir -p 'results/jest/coverage/'
    export JEST_JUNIT_OUTPUT_DIR="\$WORKSPACE/results/jest/"
    export JEST_JUNIT_OUTPUT_NAME="test-${name}-results.xml"
    export JEST_SUITE_NAME="Jest ${name} test suite"
    npm --prefix "sources/" test -- '${path}' --ci --runInBand --reporters=default --reporters=jest-junit --coverage --coverageDirectory="\$WORKSPACE/results/jest/coverage/"
    """
}

def runRESTTests(String version) {
    sh """
    mkdir -p results/api-rest/${version}
    docker run --rm -v \$WORKSPACE/sources:/usr/share/tuleap:ro --mount type=tmpfs,destination=/tmp -v \$WORKSPACE/results/api-rest/${version}:/output --network none \$DOCKER_REGISTRY/enalean/tuleap-test-rest:${version}
    """
}

def runDBTests(String version) {
    sh """
    mkdir -p results/db/${version}
    docker run --rm -v \$WORKSPACE/sources:/usr/share/tuleap:ro --mount type=tmpfs,destination=/tmp -v \$WORKSPACE/results/db/${version}:/output --network none \$DOCKER_REGISTRY/enalean/tuleap-test-rest:${version} /usr/share/tuleap/tests/integration/bin/run.sh
    """
}

def runSOAPTests(String name, String imageVersion) {
    sh """
    cid="\$(docker create -v \$WORKSPACE/sources:/usr/share/tuleap:ro --network none \$DOCKER_REGISTRY/enalean/tuleap-test-soap:${imageVersion})"
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
    sh 'docker run --rm -v $WORKSPACE/sources:/sources:ro $DOCKER_REGISTRY/prettier-checker "**/*.{js,ts,vue}"'
}

def runESLint() {
    dir ('sources') {
        sh 'npm run eslint -- --quiet --format=checkstyle --output-file=../results/eslint/checkstyle.xml .'
    }
}

def runPsalm(String configPath, String filesToAnalyze) {
    dir ('sources') {
        if (filesToAnalyze == '' || filesToAnalyze == '.') {
            sh """
            mkdir -p ../results/psalm/
            scl enable php73 "src/vendor/bin/psalm --show-info=false --report-show-info=false --config='${configPath}' --report=../results/psalm/checkstyle.xml"
            """
        } else {
            sh """
            scl enable php73 "tests/psalm/psalm-ci-launcher.php --config='${configPath}' --report-folder=../results/psalm/ ${filesToAnalyze}"
            """
        }
    }
}

def runPHPCodingStandards(String phpcsPath, String rulesetPath, String filesToAnalyze) {
    sh """
    docker run --rm -v $WORKSPACE/sources:/sources:ro -w /sources --network none php:7.3-cli-alpine -d memory_limit=256M \
        ${phpcsPath} --extensions=php --encoding=utf-8 --standard="${rulesetPath}" -p ${filesToAnalyze}
    """
}

return this;
