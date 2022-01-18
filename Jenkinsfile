#!/usr/bin/env groovy


pipeline {
    agent {
        label 'docker'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Tests') {
            agent {
                dockerfile {
                    dir 'sources/nix/'
                    filename 'docker-test.nix'
                    reuseNode true
                    args '--tmpfs /tmp/tuleap_realtime_build:rw,noexec,nosuid --read-only'
                }
            }
            steps {
                dir ('sources') {
                    sh '''
                    export HOME=/tmp/tuleap_realtime_build
                    pnpm install
                    pnpm run test
                    '''
                }
            }
        }
    }
}
