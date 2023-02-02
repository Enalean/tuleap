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
                checkout changelog: false, poll: false, scm: [$class: 'GitSCM', branches: [[name: '*/master']], doGenerateSubmoduleConfigurations: false, extensions: [[$class: 'CloneOption', depth: 1, noTags: true, reference: '', shallow: true],[$class: 'CleanBeforeCheckout'], [$class: 'RelativeTargetDirectory', relativeTargetDir: 'sources_plugin_botmattermost_main']], submoduleCfg: [], userRemoteConfigs: [[credentialsId: 'jenkins-gerrit-tuleap-net', url: 'ssh://gerrit.tuleap.net:29418/plugin-bot-mattermost']]]
                checkout scm
                sh 'git clone sources_plugin_botmattermost_main/ sources/plugins/botmattermost/'
                sh 'git clone sources_plugin/ sources/plugins/botmattermost_agiledashboard/'
            }
        }

        stage('Prepare') {
            agent {
                dockerfile {
                    dir 'sources/tools/utils/nix/'
                    filename 'build-tools.dockerfile'
                    reuseNode true
                    args '--tmpfs /tmp/tuleap_build:rw,noexec,nosuid --read-only -v /nix -v /root'
                }
            }
            steps {
                dir ('results') {
                    deleteDir()
                }
                dir ('sources') {
                    withCredentials([
                        usernamePassword(
                            credentialsId: 'nexus.enalean.com_readonly',
                            passwordVariable: 'NPM_PASSWORD',
                            usernameVariable: 'NPM_USER'
                        ),
                        string(credentialsId: 'github-token-composer', variable: 'COMPOSER_GITHUB_AUTH')
                    ]) {
                        sh """
                        # nix-daemon needs root (for now)
                        su-exec-nixdaemon root sh -c 'TMPDIR=/tmp/tuleap_build/ nix-daemon' &
                        tools/utils/scripts/generated-files-builder.sh dev
                        """
                    }
                }
            }
        }

        stage('Check lockfiles') {
            steps { script {
                actions = load 'sources/tests/actions.groovy'
                actions.runFilesStatusChangesDetection('plugins/botmattermost_agiledashboard', 'lockfiles', 'composer.lock')
            } }
            post {
                failure {
                    dir ('sources/plugins/botmattermost_agiledashboard') {
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
                        stage('UT PHPUnit PHP 8.0') { steps { script { actions.runPHPUnitTests('80') } } }
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
