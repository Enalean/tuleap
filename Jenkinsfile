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
                checkout changelog: false, poll: false, scm: [$class: 'GitSCM', branches: [[name: '*/master']], doGenerateSubmoduleConfigurations: false, extensions: [[$class: 'CloneOption', depth: 1, noTags: true, reference: '', shallow: true],[$class: 'CleanBeforeCheckout'], [$class: 'RelativeTargetDirectory', relativeTargetDir: 'sources']], submoduleCfg: [], userRemoteConfigs: [[credentialsId: 'aci_agent-gitolite-tuleap-net', url: 'ssh://gitolite@tuleap.net/tuleap/tuleap/stable.git']]]
                checkout scm
                sh 'git clone sources_plugin/ sources/plugins/botmattermost/'
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
                actions.runFilesStatusChangesDetection('plugins/botmattermost', 'lockfiles', 'package-lock.json composer.lock')
            } }
            post {
                failure {
                    dir ('sources/plugins/botmattermost') {
                        sh 'git diff'
                    }
                }
            }
        }

        stage('Tests') {
            failFast false
            parallel {
                stage('UT PHPUnit') {
                    stages {
                        stage('UT PHPUnit PHP 7.3') { steps { script { actions.runPHPUnitTests('73') } } }
                    }
                    post { always { junit 'results/ut-phpunit/*/phpunit_tests_results.xml' } }
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
