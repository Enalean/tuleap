#
# Cookbook Name:: tuleap
# Recipe:: yum
#
# Copyright 2012, Enalean
#
# All rights reserved - Do Not Redistribute
#

## Set up EPEL repo (disabled by default)
include_recipe 'yum::epel'
disable_yum_repository 'epel'
disable_yum_repository 'epel-testing'

## Set up Tuleap stable repo
tuleap_yum_repository 'stable' do
  description 'Official Releases'
  url         'https://tuleap.net/pub/tuleap/yum/4.0/$basearch/'
end

## Set up Tuleap development repo
tuleap_yum_repository 'dev' do
  description 'Main Development Branch'
  url         'ftp://ci.tuleap.net/yum/tuleap/dev/$basearch'
end
