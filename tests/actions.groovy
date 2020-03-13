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
    npm --prefix "sources/" test -- '${path}' --ci --maxWorkers=2 --reporters=default --reporters=jest-junit --coverage --coverageReporters=text-summary --coverageReporters=cobertura --coverageDirectory="\$WORKSPACE/results/jest/coverage/"
    """
}

def runRESTTests(String db, String php) {
    sh """
    mkdir -p \$WORKSPACE/results/api-rest/php${php}-${db}
    TESTS_RESULT=\$WORKSPACE/results/api-rest/php${php}-${db} sources/tests/rest/bin/run-compose.sh "${php}" "${db}"
    """
}

def runDBTests(String db, String php) {
    sh """
    mkdir -p \$WORKSPACE/results/db/php${php}-${db}
    TESTS_RESULT=\$WORKSPACE/results/db/php${php}-${db} sources/tests/integration/bin/run-compose.sh "${db}"
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

def runESLint() {
    dir ('sources') {
        sh 'npm run eslint -- --quiet --format=checkstyle --output-file=../results/eslint/checkstyle.xml .'
    }
}

def runPsalm(String configPath, String filesToAnalyze, String root='.') {
    dir ('sources') {
        if (filesToAnalyze == '' || filesToAnalyze == '.') {
            sh """
            mkdir -p ../results/psalm/
            scl enable php73 "src/vendor/bin/psalm --show-info=false --report-show-info=false --config='${configPath}' --root='${root}' --report=../results/psalm/checkstyle.xml"
            """
        } else {
            sh """
            scl enable php73 "tests/psalm/psalm-ci-launcher.php --config='${configPath}' --report-folder=../results/psalm/ ${filesToAnalyze}"
            """
        }
    }
}

def runPHPCodingStandards(String phpcsPath, String rulesetPath, String filesToAnalyze) {
    if (filesToAnalyze == '') {
        return;
    }
    sh """
    docker run --rm -v $WORKSPACE/sources:/sources:ro -w /sources --network none php:7.3-cli-alpine -d memory_limit=512M \
        ${phpcsPath} --extensions=php --encoding=utf-8 --standard="${rulesetPath}" -p ${filesToAnalyze}
    """
}

return this;
