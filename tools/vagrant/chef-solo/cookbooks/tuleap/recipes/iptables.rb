#
# Cookbook Name:: tuleap
# Recipe:: iptables
#
# Copyright 2012, Enalean
#
# All rights reserved - Do Not Redistribute
#

## Disable iptables
service "iptables" do
  action [:stop, :disable]
end
