#!/usr/bin/env groovy

def actions

pipeline {
    agent {
        label 'docker'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout changelog: false, poll: false, scm: [$class: 'GitSCM', branches: [[name: '*/master']], doGenerateSubmoduleConfigurations: false, extensions: [[$class: 'CleanBeforeCheckout'], [$class: 'RelativeTargetDirectory', relativeTargetDir: 'sources']], submoduleCfg: [], userRemoteConfigs: [[credentialsId: 'gitolite-tuleap-net', url: 'ssh://gitolite@tuleap.net/tuleap/tuleap/stable.git']]]
                checkout scm
                sh 'GIT_DIR=sources_plugin/.git/ git checkout-index -f -a --prefix=sources/plugins/baseline/'
            }
        }

        stage('Prepare') {
            steps {
                dir ('results') {
                    deleteDir()
                }
                script {
                    actions = load 'sources/tests/actions.groovy'
                    actions.prepareSources('nexus.enalean.com_readonly')
                }
            }
        }

        stage('Check lockfiles') {
            steps { script {
                dir ('sources/plugins/baseline') {
                    sh "../../tests/files_status_checker/verify.sh lockfiles package-lock.json composer.lock"
                }
            } }
            post {
                failure {
                    dir ('sources/plugins/baseline') {
                        sh 'git diff'
                    }
                }
            }
        }

        stage('Tests') {
            failFast false
            parallel {
                stage('UT SimpleTest PHP 7.2') {
                    steps { script { actions.runSimpleTestTests('72') } }
                    post { always { junit 'results/ut-simpletest/php-72/results.xml' } }
                }
                stage('UT PHPUnit') {
                    stages {
                        stage('UT PHPUnit PHP 7.2') { steps { script { actions.runPHPUnitTests('72') } } }
                    }
                    post { always { junit 'results/ut-phpunit/*/phpunit_tests_results.xml' } }
                }
                stage('REST') {
                    stages {
                        stage('REST CentOS 6 PHP 7.2 MySQL 5.7') {
                            steps { script { actions.runRESTTests('c6-php72-mysql57') } }
                        }
                    }
                    post { always { junit 'results/api-rest/*/rest_tests.xml' } }
                }
                stage('SOAP') {
                    stages {
                        stage('SOAP PHP 7.2') { steps { script { actions.runSOAPTests('php-72', '4') } } }
                    }
                    post { always { junit "results/api-soap/*/soap_tests.xml" } }
                }
                stage('Check translation files') {
                    steps { script {
                        dir ('sources/plugins/baseline') {
                            sh "../../tests/files_status_checker/verify.sh 'translation files' '*.po\$'"
                        }
                    } }
                }
                stage('Build RPM') {
                    steps {
                        script {
                            dir ('sources/plugins/baseline') {
                                sh "TULEAP_PATH=${WORKSPACE}/sources ./build-rpm.sh"
                            }
                        }
                    }
                    post {
                        always {
                            archiveArtifacts "*.rpm"
                        }
                    }
                }
            }
            post {
                always {
                    archiveArtifacts allowEmptyArchive: true, artifacts: 'results/'
                }
                failure {
                    withCredentials([string(credentialsId: 'email-notification-baseline-plugin-team', variable: 'email')]) {
                        mail to: email,
                        subject: "${currentBuild.fullDisplayName} is broken",
                        body: "See ${env.BUILD_URL}"
                    }
                }
                unstable {
                    withCredentials([string(credentialsId: 'email-notification-baseline-plugin-team', variable: 'email')]) {
                        mail to: email,
                        subject: "Tuleap ${currentBuild.fullDisplayName} is unstable",
                        body: "See ${env.BUILD_URL}"
                    }
                }
            }
        }
    }
}
