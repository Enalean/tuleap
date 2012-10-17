# -*- coding: utf-8 -*-
# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant::Config.run do |config|
  # Set up a CentOS 5 box.
  # TODO: Move box to tuleap.net
  config.vm.box = 'centos-5.8-x86_64-chef'
  config.vm.box_url = 'https://tuleap.net/file/download.php/101/39/p18_r37/centos-5.8-x86_64-chef.box'

  # Assign this VM to a host-only network IP, allowing you to access it
  # via the IP. Host-only networks can talk to the host machine as well as
  # any other machines on the same network, but cannot be accessed (through this
  # network interface) by any external networks.
  config.vm.network :hostonly, '10.11.13.11'
  
  # Assuming the following layout on the host:
  # 
  #   tuleap
  #   ├── manifest
  #   ├── tuleap
  #   └── vagrant
  #
  # The following folders will be available on the guest:
  #
  #   /mnt/tuleap/manifest
  #   /mnt/tuleap/tuleap
  #   /mnt/tuleap/vagrant
  # 
  # NFS is prefered over VirtualBox shared folders, because it is faster.
  config.vm.share_folder 'v-tuleap',
                         '/mnt/tuleap',
                         '..',
                         :nfs => true
  
  # Ugly NFS hack
  config.nfs.map_uid = Process.uid
  config.nfs.map_gid = Process.gid

  # Fill in the VM
  config.vm.provision :chef_solo do |chef|
    # Standard Chef layout
    chef.cookbooks_path = 'tools/vagrant/chef-solo/cookbooks'
    chef.roles_path     = 'tools/vagrant/chef-solo/roles'
    chef.data_bags_path = 'tools/vagrant/chef-solo/data_bags'
    
    # Get more output
    chef.log_level = :debug
    
    # Available Chef roles:
    #   - tuleap: set up an instance from latest stable RPM
    #   - tuleap_development: set up an instance from your local sources
    #   - tuleap_packaging: set up everything to package and install tuleap
    #                       from local repositories
    chef.add_role 'tuleap_development'
    
    # Additional configuration (may be role-specific)
    chef.json = {:tuleap => {
      # Available PHP versions:
      #   - php
      #   - php53
      :php_base => 'php',
      
      # Use Vagrant user to build packages (should not be root anyway)
      :packaging_user => 'vagrant',
      
      # Passed to setup.sh
      :fqdn       => 'tuleap.local',
      :ip_address => '10.11.13.11',
    }}
  end
end
