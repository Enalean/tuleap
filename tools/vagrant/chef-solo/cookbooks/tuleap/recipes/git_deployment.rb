#
# Cookbook Name:: tuleap
# Recipe:: git
#
# Copyright 2012, Enalean
#
# All rights reserved - Do Not Redistribute
#

## Proceed to a normal RPM deployment
include_recipe 'tuleap::rpm_deployment'

## Then remove the Tuleap RPM package, but keep the configuration files

# Disable tuleap repositories to avoid accidental yum update that trash
# tuleap development tree
disable_yum_repository 'tuleap-dev'
disable_yum_repository 'tuleap-local'
disable_yum_repository 'tuleap-stable'

script "Move away tuleap rpm install" do
  user        'root'
  interpreter 'bash'
  environment 'HOME' => '/root'
  code        <<-SHELL
                mv /usr/share/codendi /usr/share/codendi_rpm
              SHELL
end

## Symlink the local git repository
link node['tuleap']['install_dir'] do
  to node['tuleap']['source_dir']
  action :create
end
