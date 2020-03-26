#!/usr/bin/env groovy

def actions

pipeline {
    agent {
        label 'docker'
    }

    stages {
        stage('Checkout') {
            steps {
                dir ('sources') {
                    deleteDir()
                }
                checkout changelog: false, poll: false, scm: [$class: 'GitSCM', branches: [[name: '*/master']], doGenerateSubmoduleConfigurations: false, extensions: [[$class: 'CloneOption', depth: 1, noTags: true, reference: '', shallow: true],[$class: 'CleanBeforeCheckout'], [$class: 'RelativeTargetDirectory', relativeTargetDir: 'sources']], submoduleCfg: [], userRemoteConfigs: [[credentialsId: 'gitolite-tuleap-net', url: 'ssh://gitolite@tuleap.net/tuleap/tuleap/stable.git']]]
                checkout scm
                sh 'git clone sources_plugin/ sources/plugins/baseline/'
            }
        }

        stage('Prepare') {
            steps {
                dir ('results') {
                    deleteDir()
                }
                script {
                    actions = load 'sources/tests/actions.groovy'
                    actions.prepareSources('nexus.enalean.com_readonly', 'github-token-composer')
                }
            }
        }

        stage('Check lockfiles') {
            steps { script {
                actions.runFilesStatusChangesDetection('plugins/baseline', 'lockfiles', 'package-lock.json composer.lock')
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
                stage('UT SimpleTest PHP 7.3') {
                    steps { script { actions.runSimpleTestTests('73') } }
                    post { always { junit 'results/ut-simpletest/php-73/results.xml' } }
                }
                stage('UT PHPUnit') {
                    stages {
                        stage('UT PHPUnit PHP 7.3') { steps { script { actions.runPHPUnitTests('73') } } }
                    }
                    post { always { junit 'results/ut-phpunit/*/phpunit_tests_results.xml' } }
                }
                stage('REST') {
                    stages {
                        stage('REST CentOS 6 PHP 7.3 MySQL 5.7') {
                            steps { script { actions.runRESTTests('mysql57', '73') } }
                        }
                    }
                    post { always { junit 'results/api-rest/*/rest_tests.xml' } }
                }
                stage('SOAP') {
                    stages {
                        stage('SOAP PHP 7.3') { steps { script { actions.runSOAPTests('mysql57', '73') } } }
                    }
                    post { always { junit "results/soap/*/soap_tests.xml" } }
                }
                stage ('Jest') {
                    agent {
                        docker {
                            image 'node:13.10-alpine'
                            reuseNode true
                            args '--network none'
                        }
                    }
                    steps { script { actions.runJestTests('Baseline', 'plugins/baseline/') } }
                    post {
                        always {
                            junit 'results/jest/test-*-results.xml'
                            publishCoverage adapters: [istanbulCoberturaAdapter('results/jest/coverage/cobertura-coverage.xml')], tag: 'Javascript'
                        }
                    }
                }
                stage('Check translation files') {
                    steps { script {
                        dir ('sources/plugins/baseline') {
                            sh "../../tests/files_status_checker/verify.sh 'translation files' '*.po\$'"
                        }
                    } }
                }
                stage('PHP coding standards') {
                    steps {
                        script {
                            actions.runPHPCodingStandards(
                                './src/vendor/bin/phpcs',
                                './tests/phpcs/tuleap-ruleset.xml',
                                'plugins/baseline/'
                            )
                        }
                    }
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
        stage('Code conformity') {
            stages {
                stage('Check translation files') {
                    steps { script {
                        actions.runFilesStatusChangesDetection('plugins/baseline', 'translation files', '"*.po\$"')
                    } }
                }
            }
        }
    }
}
