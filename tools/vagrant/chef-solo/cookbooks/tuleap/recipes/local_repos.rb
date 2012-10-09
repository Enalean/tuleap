#
# Cookbook Name:: tuleap
# Recipe:: local_repos
#
# Copyright 2012, Enalean
#
# All rights reserved - Do Not Redistribute
#

## Create all the local Tuleap repos
node['tuleap']['createrepos'].each do |path|
  createrepo path do
    user node['tuleap']['packaging_user']
  end
end

## Add YUM configuration file
php53 = (node['tuleap']['php_base'] == 'php53' ? '-php53' : '')

tuleap_yum_repository 'local' do
  description 'Local Repository'
  url         "file:///home/vagrant/repos/centos/5/$basearch#{php53}"
end

yum_clean
