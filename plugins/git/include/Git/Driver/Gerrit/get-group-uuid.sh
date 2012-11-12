gerritserver=$1
gerritport=$2
gerritusername=$3
groupname=$4

ssh $gerritusername@$gerritserver -p $gerritport gerrit gsql  -c "SELECT\ group_uuid\ FROM\ account_groups\ WHERE\ name=\'$groupname\'"
