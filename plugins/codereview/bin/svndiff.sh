#!/bin/bash

# > ./svn-export revision-from revision-to repository target-directory
# For example, to export changes between revisions 4 and 105 from the repository svn://localhost/myrepository to the current directory:
# >./svn-export 4 105 svn://localhost/myrepository .
if [ ! $1 ] || [ ! $2 ] || [ ! $3 ] || [ ! $4 ]; then
    echo "Please enter a revision from, revision to, SVN repository, and target directory"
    exit
fi

# set up nice names for the incoming parameters to make the script more readable
revision_from=$1
revision_to=$2
repository=$3
target_directory=$4

#@Todo properly handle target path
 svn diff -r$revision_from:$revision_to $repository > $target_directory'svn.diff'

# for html interface, display summarized diff:

# svn diff --summarize -r$revision_from:$revision_to $repository > $target_directory.'svn.diff'


# to summarize any deleted files or directories at the end of the script uncomment the following line
filename=`svn diff --summarize -r$revision_from:$revision_to $repository `
echo $filename
