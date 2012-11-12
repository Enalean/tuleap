#/bin/bash

contributors=$1
integrators=$2
supermen=$3

# if it is a public project :
git config -f project.config --add access.refs/heads/*.Read 'group Registered Users'
# endif

git config -f project.config --add access.refs/heads/*.Read "group $contributors"
git config -f project.config --add access.refs/heads/*.create "group $integrators"
