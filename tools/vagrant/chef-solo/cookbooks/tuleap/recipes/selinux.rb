#
# Cookbook Name:: tuleap
# Recipe:: selinux
#
# Copyright 2012, Enalean
#
# All rights reserved - Do Not Redistribute
#

## Disable SElinux
cookbook_file '/etc/selinux/config' do
  source 'selinux'
  mode '0644'
end
