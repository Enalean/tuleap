server=$1
port=$2
user=$3
project=$4
contributors=$5
integrators=$6
supermen=$7


gerritserverurl=ssh://$user@$server:$port/$project
scriptdir=$(pwd)/$(dirname $0)

cd /tmp
mkdir update-project-config
cd update-project-config

#pull
git init
git pull $gerritserverurl refs/meta/config

#add groups
sh $scriptdir/add-group-to-groups-file.sh global:Registered-Users "Registered Users"
for group in $contributors $integrators $supermen; do
    uuid=$(sh $scriptdir/get-group-uuid.sh $server $port $user $group)
    sh $scriptdir/add-group-to-groups-file.sh $uuid $group
done

#add rights
sh $scriptdir/add-rights-to-project-config.sh $contributors $integrators $supermen

#add/commit
git add -A
git commit -m"'updated project config'"

#push
git push $gerritserverurl HEAD:refs/meta/config

cd ..
#rm -rf update-project-config