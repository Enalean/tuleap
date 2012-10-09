#
# Cookbook Name:: tuleap
# Recipe:: base
#
# Copyright 2012, Enalean
#
# All rights reserved - Do Not Redistribute
#

package 'yum-utils'
include_recipe 'tuleap::selinux'
include_recipe 'tuleap::iptables'
include_recipe 'tuleap::php'
include_recipe 'tuleap::yum'
include_recipe 'tuleap::external_dependencies'
