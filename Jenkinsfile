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
                dir ('sources/plugins/mytuleap_contact_support') {
                    sh "../../tests/files_status_checker/verify.sh lockfiles package-lock.json composer.lock"
                }
            } }
            post {
                failure {
                    dir ('sources/plugins/mytuleap_contact_support') {
                        sh 'git diff'
                    }
                }
            }
        }

        stage('Tests') {
            failFast false
            parallel {
                stage('UT SimpleTest PHP 5.6') {
                    steps { script { actions.runSimpleTestTests('56') } }
                    post { always { junit 'results/ut-simpletest/php-56/results.xml' } }
                }
                stage('UT SimpleTest PHP 7.2') {
                    steps { script { actions.runSimpleTestTests('72') } }
                    post { always { junit 'results/ut-simpletest/php-72/results.xml' } }
                }
                stage('UT PHPUnit') {
                    stages {
                        stage('UT PHPUnit PHP 5.6') { steps { script { actions.runPHPUnitTests('56') } } }
                        stage('UT PHPUnit PHP 7.2') { steps { script { actions.runPHPUnitTests('72') } } }
                    }
                    post { always { junit 'results/ut-phpunit/*/phpunit_tests_results.xml' } }
                }
                stage('REST') {
                    stages {
                        stage('REST CentOS 6 PHP 5.6 MySQL 5.6') {
                            steps { script { actions.runRESTTests('c6-php56-mysql56') } }
                        }
                        stage('REST CentOS 6 PHP 7.2 MySQL 5.6') {
                            steps { script { actions.runRESTTests('c6-php72-mysql56') } }
                        }
                    }
                    post { always { junit 'results/api-rest/*/rest_tests.xml' } }
                }
                stage('SOAP') {
                    stages {
                        stage('SOAP PHP 5.6') { steps { script { actions.runSOAPTests('php-56', '3') } } }
                        stage('SOAP PHP 7.2') { steps { script { actions.runSOAPTests('php-72', '4') } } }
                    }
                    post { always { junit "results/api-soap/*/soap_tests.xml" } }
                }
                stage('Distributed SVN integration') {
                    steps { script { actions.runEndToEndTests('distlp') } }
                    post { always { junit 'results/e2e/**/*.xml' } }
                }
                stage('Check translation files') {
                    steps { script {
                        dir ('sources/plugins/mytuleap_contact_support') {
                            sh "../../tests/files_status_checker/verify.sh 'translation files' '*.po\$'"
                        }
                    } }
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
