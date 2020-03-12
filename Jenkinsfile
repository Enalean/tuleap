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
                        }
                    }
                    post { always { junit 'results/ut-phpunit/*/phpunit_tests_results.xml' } }
                }
                stage ('Jest') {
                    agent {
                        docker {
                            image 'node:13.3-alpine'
                            reuseNode true
                            args '--network none'
                        }
                    }
                    steps { script { actions.runJestTests('TestManagement', 'plugins/testmanagement/scripts/') } }
                    post {
                        always {
                            junit 'results/jest/test-*-results.xml'
                            publishCoverage adapters: [istanbulCoberturaAdapter('results/jest/coverage/cobertura-coverage.xml')], tag: 'Javascript'
                        }
                    }
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
                        stage('ESLint static analysis') {
                            agent {
                                docker {
                                    image 'node:13.3-alpine'
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
                        stage('Psalm static analysis') {
                            agent {
                                dockerfile {
                                    dir 'sources/tests/psalm/'
                                    reuseNode true
                                    args '--network none'
                                }
                            }
                            steps {
                                script {
                                    actions.runPsalm('plugins/testmanagement/tests/psalm/psalm.xml', '.', 'plugins/testmanagement/')
                                }
                            }
                            post {
                                always {
                                    recordIssues enabledForFailure: true, minimumSeverity: 'NORMAL', tools: [checkStyle(id: 'checkstyle_psalm', pattern: 'results/psalm/checkstyle.xml')]
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
