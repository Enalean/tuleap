#!/usr/bin/env groovy

def runFilesStatusChangesDetection(String repository_to_inspect, String name_of_verified_files, String verified_files) {
    dir ('sources') {
        sh "tests/files_status_checker/verify.sh '${repository_to_inspect}' '${name_of_verified_files}' ${verified_files}"
    }
}

def runPHPUnitTests(String version, Boolean with_coverage = false) {
    def coverage_enabled='0'
    if (with_coverage) {
        coverage_enabled='1'
    }
    sh "make -C $WORKSPACE/sources phpunit-ci PHP_VERSION=${version} COVERAGE_ENABLED=${coverage_enabled}"
}

def runJestTests(String name, String path, Boolean with_coverage = false) {
    def coverage_params=''
    if (with_coverage) {
        coverage_params="--coverage --coverageReporters=text-summary --coverageReporters=cobertura"
    }
    sh """
    mkdir -p 'results/jest/coverage/'
    export JEST_JUNIT_OUTPUT_DIR="\$WORKSPACE/results/jest/"
    export JEST_JUNIT_UNIQUE_OUTPUT_NAME=true
    export JEST_SUITE_NAME="Jest ${name} test suite"
    export COVERAGE_BASE_OUTPUT_DIR="\$WORKSPACE/results/jest/coverage/"
    timeout 1h pnpm --prefix "sources/${path}" test -- --ci --maxWorkers=30% --reporters=default --reporters=jest-junit ${coverage_params}
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
    TESTS_RESULT=\$WORKSPACE/results/db/php${php}-${db} sources/tests/integration/bin/run-compose.sh "${php}" "${db}"
    """
}

def runSOAPTests(String db, String php) {
    sh """
    mkdir -p \$WORKSPACE/results/soap/php${php}-${db}
    TESTS_RESULT=\$WORKSPACE/results/soap/php${php}-${db} sources/tests/soap/bin/run-compose.sh "${php}" "${db}"
    """
}

def runEndToEndTests(String flavor, String db) {
    dir ('sources') {
        sh "tests/e2e/${flavor}/wrap.sh '${db}' $WORKSPACE/results/e2e/${flavor}/'"
    }
}

def runBuildAndRun(String os) {
    dir ('sources') {
        sh "OS='${os}' tests/build_and_run/test.sh"
    }
}

def runESLint() {
    dir ('sources') {
        sh 'pnpm run eslint -- --quiet --format=checkstyle --output-file=../results/eslint/checkstyle.xml .'
    }
}

def runStylelint() {
    dir ('sources') {
        sh 'pnpm run stylelint **/*.scss **/*.vue'
    }
}

def runPsalm(String configPath, String filesToAnalyze, String root='.') {
    withEnv(['XDG_CACHE_HOME=/tmp/psalm_cache/']) {
        dir ('sources') {
            if (filesToAnalyze == '' || filesToAnalyze == '.') {
                sh """
                mkdir -p ../results/psalm/
                scl enable php80 "src/vendor/bin/psalm --show-info=false --report-show-info=false --config='${configPath}' --no-cache --root='${root}' --report=../results/psalm/checkstyle.xml"
                """
            } else {
                sh """
                scl enable php80 "tests/psalm/psalm-ci-launcher.php --config='${configPath}' --report-folder=../results/psalm/ ${filesToAnalyze}"
                """
            }
        }
    }
}

def runPsalmTaintAnalysis(String configPath, String root='.') {
    withEnv(['XDG_CACHE_HOME=/tmp/psalm_cache/']) {
        dir ('sources') {
            sh """
            mkdir -p ../results/psalm/
            scl enable php80 "src/vendor/bin/psalm --taint-analysis --config='${configPath}' --no-cache --root='${root}' --report=../results/psalm/checkstyle.xml"
            """
        }
    }
}

def runPsalmUnusedCodeDetection(String configPath, String root='.') {
    withEnv(['XDG_CACHE_HOME=/tmp/psalm_cache/']) {
        dir ('sources') {
            sh """
            mkdir -p ../results/psalm/
            scl enable php80 "src/vendor/bin/psalm --find-unused-code --show-info=false --report-show-info=false --config='${configPath}' --no-cache --root='${root}' --report=../results/psalm/checkstyle.xml"
            """
        }
    }
}

def runPHPCodingStandards(String phpcsPath, String rulesetPath, String filesToAnalyze) {
    if (filesToAnalyze == '') {
        return;
    }
    sh """
    docker run --rm -v $WORKSPACE/sources:/sources:ro -w /sources --network none \${DOCKER_REGISTRY:-ghcr.io}/enalean/tuleap-test-phpunit:c7-php80 \
        scl enable php80 "php -d memory_limit=512M ${phpcsPath} --extensions=php,phpstub --encoding=utf-8 --standard="${rulesetPath}" -p ${filesToAnalyze}"
    """
}

def runDeptrac() {
    dir ('sources') {
        sh """
        export CI_REPORT_OUTPUT_PATH="\$WORKSPACE/results/deptrac/"
        mkdir -p "\$CI_REPORT_OUTPUT_PATH"
        scl enable php80 "./tests/deptrac/run.sh"
        """
    }
}

return this;
