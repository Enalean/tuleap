#!/usr/bin/env groovy

def runInsideNixShell(String command, String tool_flavor = 'build') {
    sh """
    nix-shell -I nixpkgs="\$(pwd)/tools/utils/nix/pinned-nixpkgs.nix" "\$(pwd)/tools/utils/nix/${tool_flavor}-tools/" --run "${command}"
    """
}

def prepareSources(String prepare_flavor) {
    runInsideNixShell("tools/utils/scripts/generated-files-builder.sh ${prepare_flavor}")
}

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

def runJSUnitTests(Boolean with_coverage = false) {
    def coverage_env=''
    if (with_coverage) {
        coverage_env='COLLECT_COVERAGE=true'
    }
    sh("mkdir -p ${WORKSPACE}/results/")
    dir ('sources') {
      runInsideNixShell("${coverage_env} lib/frontend/build-system-configurator/bin/run-js-units-ci.sh")
    }
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

def runEndToEndTests(String flavor, String db) {
    dir ('sources') {
        sh "tests/e2e/${flavor}/wrap.sh '${db}' '$WORKSPACE/results/e2e/${flavor}/'"
    }
}

def runBuildAndRun(String os) {
    dir ('sources') {
        sh "OS='${os}' tests/build_and_run/test.sh"
    }
}

def runESLint() {
    sh("mkdir -p ${WORKSPACE}/results/eslint/")
    dir ('sources') {
        runInsideNixShell("pnpm run eslint --quiet --format=checkstyle --output-file=${WORKSPACE}/results/eslint/checkstyle.xml .")
    }
}

def runStylelint() {
    dir ('sources') {
        runInsideNixShell('pnpm run stylelint **/*.scss **/*.vue')
    }
}

def runPsalm(String configPath, String root='.') {
    withEnv(['XDG_CACHE_HOME=/tmp/psalm_cache/']) {
        dir ('sources') {
            sh """
            mkdir -p ../results/psalm/
            scl enable php82 "src/vendor/bin/psalm --find-unused-code --show-info=false --report-show-info=false --config='${configPath}' --no-cache --root='${root}' --report=../results/psalm/checkstyle.xml"
            """
        }
    }
}

def runPsalmTaintAnalysis(String configPath, String root='.') {
    withEnv(['XDG_CACHE_HOME=/tmp/psalm_cache/']) {
        dir ('sources') {
            sh """
            mkdir -p ../results/psalm/
            scl enable php82 "src/vendor/bin/psalm --taint-analysis --memory-limit=4096M --threads=1 --config='${configPath}' --no-cache --root='${root}' --report=../results/psalm/checkstyle.xml"
            """
        }
    }
}

def runPHPCodingStandards(String phpcsPath, String rulesetPath, String filesToAnalyze) {
    if (filesToAnalyze == '') {
        return;
    }
    sh """
    docker run --rm -v $WORKSPACE/sources:/sources:ro -w /sources --network none \${DOCKER_REGISTRY:-ghcr.io}/enalean/tuleap-test-phpunit:c7-php82 \
        scl enable php82 "php -d memory_limit=1024M ${phpcsPath} --extensions=php,phpstub --encoding=utf-8 --standard="${rulesetPath}" -p ${filesToAnalyze}"
    """
}

def runDeptrac() {
    dir ('sources') {
        sh """
        export CI_REPORT_OUTPUT_PATH="\$WORKSPACE/results/deptrac/"
        mkdir -p "\$CI_REPORT_OUTPUT_PATH"
        scl enable php82 "./tests/deptrac/run.sh"
        """
    }
}

return this;
