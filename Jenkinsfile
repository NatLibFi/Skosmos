pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '10', artifactNumToKeepStr: '10'))
    }

    stages {
        stage('Trigger SKOSMOS CICD job') {
            when {
                anyOf {
                    branch 'master'
                }
            }
            steps {
                sh "docker build -t skosmos:test . -f dockerfiles/Dockerfile.ubuntu"
                }
            }
        }
}
