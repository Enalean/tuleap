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
                sh 'git clone sources_plugin/ sources/plugins/testmanagement/'
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
                actions.runFilesStatusChangesDetection('plugins/testmanagement', 'lockfiles', 'package-lock.json composer.lock')
            } }
            post {
                failure {
                    dir ('sources/plugins/testmanagement') {
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
                        stage('UT PHPUnit PHP 7.3') {
                            steps { script { actions.runPHPUnitTests('73') } }
                            post {
                                always {
                                    step([$class: 'CloverPublisher', cloverReportDir: 'results/ut-phpunit/php-73/coverage/', cloverReportFileName: 'clover.xml'])
                                }
                            }
                        }
                    }
                    post { always { junit 'results/ut-phpunit/*/phpunit_tests_results.xml' } }
                }
                stage ('Jest') {
                    agent {
                        docker {
                            image 'node:12.6-alpine'
                            reuseNode true
                            args '--network none'
                        }
                    }
                    steps { script { actions.runJestTests('Enalean Helpdesk', 'plugins/testmanagement/scripts/') } }
                    post {
                        always {
                            junit 'results/jest/test-*-results.xml'
                            step([$class: 'CloverPublisher', cloverReportDir: 'results/jest/coverage/', cloverReportFileName: 'clover.xml'])
                        }
                    }
                }
                stage('REST') {
                    stages {
                        stage('REST CentOS 6 PHP 7.3 MySQL 5.7') {
                            steps { script { actions.runRESTTests('c6-php73-mysql57') } }
                        }
                    }
                    post { always { junit 'results/api-rest/*/rest_tests.xml' } }
                }
                stage('SOAP') {
                    stages {
                        stage('SOAP PHP 7.3') { steps { script { actions.runSOAPTests('php-73', '5') } } }
                    }
                    post { always { junit "results/api-soap/*/soap_tests.xml" } }
                }
                stage('Build RPM') {
                    steps {
                        script {
                            dir ('sources/plugins/testmanagement') {
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
                stage('Code conformity') {
                    stages {
                        stage('Check translation files') {
                            steps { script {
                                actions.runFilesStatusChangesDetection('.', 'translation files', '"*.po\$"')
                            } }
                        }
                        stage('Javascript coding standards') {
                            steps { script { actions.runJavascriptCodingStandards() } }
                        }
                        stage('ESLint static analysis') {
                            agent {
                                docker {
                                    image 'node:12.6-alpine'
                                    reuseNode true
                                    args '--network none'
                                }
                            }
                            steps { script { actions.runESLint() } }
                            post {
                                always {
                                    recordIssues enabledForFailure: true, tools: [checkStyle(id: 'checkstyle_eslint', pattern: 'results/eslint/checkstyle.xml')]
                                }
                            }
                        }
                    }
                }
            }
            post {
                always {
                    archiveArtifacts allowEmptyArchive: true, artifacts: 'results/'
                }
            }
        }
    }
}
