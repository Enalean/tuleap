gerritserver=$1
gerritport=$2
gerritusername=$3
groupname=$4

#replace by --format JSON, to remove sed tail and head
ssh $gerritusername@$gerritserver -p $gerritport gerrit gsql -c "SELECT\ group_uuid\ FROM\ account_groups\ WHERE\ name=\'$groupname\'" | tail -2 | head -1 | sed 's/ //'