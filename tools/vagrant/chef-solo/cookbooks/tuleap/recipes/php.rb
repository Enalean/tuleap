#
# Cookbook Name:: tuleap
# Recipe:: base
#
# Copyright (c) Enalean, 2012. All Rights Reserved.
#
# This file is a part of Tuleap.
#
# Tuleap is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Tuleap is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
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
