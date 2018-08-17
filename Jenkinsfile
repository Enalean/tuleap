#!/usr/bin/env groovy

def npm_credentials = [usernamePassword(credentialsId: 'nexus.enalean.com_readonly', passwordVariable: 'NPM_PASSWORD', usernameVariable: 'NPM_USER')];

def runRESTTests(String version) {
    sh """
    mkdir -p working_copy/api-$version
    cp -R sources/* working_copy/api-$version/
    mkdir -p results/api-$version
    docker run --rm -v \$WORKSPACE/working_copy/api-$version/:/usr/share/tuleap --mount type=tmpfs,destination=/tmp -v \$WORKSPACE/results/api-$version:/output \$DOCKER_REGISTRY/enalean/tuleap-test-rest:$version
    """
    junit "results/api-$version/rest_tests.xml"
}

def runKarmaTests(String name, String path) {
    sh """
    cid="\$(docker create -v \$WORKSPACE/sources:/sources:ro --security-opt seccomp=\$WORKSPACE/sources/tests/karma/seccomp_chrome.json \$DOCKER_REGISTRY/enalean/tuleap-test-karma:latest --path $path)"
    docker start --attach "\$cid" || true
    mkdir -p 'results/karma'
    docker cp "\$cid":/output/test-results.xml results/karma/test-$name-results.xml
    docker rm -fv "\$cid"
    """
    junit "results/karma/test-$name-results.xml"
}

def runSOAPTests(String name, String image_version) {
    sh """
    cid="\$(docker create -v \$WORKSPACE/sources:/usr/share/tuleap:ro \$DOCKER_REGISTRY/enalean/tuleap-test-soap:${image_version})"
    docker start --attach "\$cid" || true
    mkdir -p 'results/api-soap'
    docker cp "\$cid":/output/soap_tests.xml results/api-soap/soap_tests_${name}.xml || true
    docker rm -fv "\$cid"
    """
    junit "results/api-soap/soap_tests_${name}.xml"
}

pipeline {
    agent {
        label 'docker'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout changelog: false, poll: false, scm: [$class: 'GitSCM', branches: [[name: '*/master']], doGenerateSubmoduleConfigurations: false, extensions: [[$class: 'CleanBeforeCheckout'], [$class: 'RelativeTargetDirectory', relativeTargetDir: 'sources']], submoduleCfg: [], userRemoteConfigs: [[credentialsId: 'gitolite-tuleap-net', url: 'ssh://gitolite@tuleap.net/tuleap/tuleap/stable.git']]]
                checkout scm
            }
        }

        stage('Prepare') {
            steps {
                dir ('results') {
                    deleteDir()
                }
                withCredentials(npm_credentials) {
                    sh 'docker run --rm -e NPM_REGISTRY="$NPM_REGISTRY" -e NPM_USER="$NPM_USER" -e NPM_PASSWORD="$NPM_PASSWORD" -e NPM_EMAIL="$NPM_EMAIL" -v "$WORKSPACE/sources/":/tuleap -v "$WORKSPACE/sources/":/output --tmpfs /tmp/tuleap_build:rw,noexec,nosuid --read-only $DOCKER_REGISTRY/tuleap-generated-files-builder dev'
                }
            }
        }

        stage('Check lockfiles') {
            steps {
                dir ('sources/plugins/enalean_licensemanager') {
                    sh '../../tests/files_status_checker/verify.sh lockfiles package-lock.json composer.lock'
                }
            }
            post {
                failure {
                    dir ('sources/plugins/enalean_licensemanager') {
                        sh 'git diff'
                    }
                }
            }
        }

        stage('Tests') {
            steps {
                parallel 'UT SimpleTest 1.1.x PHP 5.6': {
                    sh "make -C $WORKSPACE/sources simpletest11x-56-ci"
                    junit 'results/ut-simpletest11x-php-56/results.xml'
                },
                'UT SimpleTest PHP 7.2': {
                    sh "make -C $WORKSPACE/sources simpletest-72-ci"
                    junit 'results/ut-simpletest-php-72/results.xml'
                },
                'UT PHPUnit PHP 5.6': {
                    sh "make -C $WORKSPACE/sources phpunit-ci-56"
                    junit 'results/ut-phpunit-php-56/phpunit_tests_results.xml'
                },
                'UT PHPUnit PHP 7.2': {
                    sh "make -C $WORKSPACE/sources phpunit-ci-72"
                    junit 'results/ut-phpunit-php-72/phpunit_tests_results.xml'
                },
                'REST CentOS 6 PHP 5.6 MySQL 5.6': { runRESTTests('c6-php56-mysql56') },
                'REST CentOS 6 PHP 7.2 MySQL 5.6': { runRESTTests('c6-php72-mysql56') },
                'SOAP PHP 5.6': { runSOAPTests('php-56', '3') },
                'SOAP PHP 7.2': { runSOAPTests('php-72', '4') },
                'Distributed SVN integration': {
                    dir ('sources') {
                        sh """
                        tests/e2e/distlp/wrap.sh "$WORKSPACE/results/distlp-integration"
                        """
                    }
                    junit 'results/distlp-integration/distlp-svn-cli.xml'
                    junit 'results/distlp-integration/results-*.xml'
                },
                'Check translation files': {
                    dir ('sources') {
                        sh 'tests/files_status_checker/verify.sh "translation files" "*.po\$"'
                    }
                },
                failFast: false
            }
            post {
                always {
                    archiveArtifacts allowEmptyArchive: true, artifacts: 'results/'
                    sh 'rm -rf working_copy || echo "Cleanup of the working copies has failed, please stop writing files as root"'
                }
            }
        }
    }
}
