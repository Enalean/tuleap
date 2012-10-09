#
# Cookbook Name:: tuleap
# Recipe:: base
#
# Copyright 2012, Enalean
#
# All rights reserved - Do Not Redistribute
#

## Remove previously installed PHP packages that are incompatible
%w(php php53).each do |php_base|
  package "#{php_base}-common" do
    action :purge
    only_if { php_base != node['tuleap']['php_base'] }
  end
end

## Customize the default CentOS repo to exclude incompatible PHP packages
template "/etc/yum.repos.d/CentOS-Base.repo" do
  mode '0644'
  variables :php_base => node['tuleap']['php_base'],
            :mirror   => node['tuleap']['mirror']
end

## Install PHP
package node['tuleap']['php_base']
