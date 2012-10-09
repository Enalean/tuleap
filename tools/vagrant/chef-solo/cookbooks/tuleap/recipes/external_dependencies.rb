#
# Cookbook Name:: tuleap
# Recipe:: external_dependencies
#
# Copyright 2012, Enalean
#
# All rights reserved - Do Not Redistribute
#

# FIXME: These dependencies should be mirrored in Tuleap yum repositories,
#        so that people don't need to use other RPM sources.

## Install some packages from EPEL
epel_package 'git'
epel_package 'perl-HTML-Template'

## Download and install openfire directly from website
remote_rpm 'openfire-3.6.4-1' do
  source 'http://download.igniterealtime.org/openfire/openfire-3.6.4-1.i386.rpm'
end
