name 'development'
description 'Tuleap development environment'
run_list 'recipe[tuleap::base]',
         'recipe[tuleap::git_deployment]'
default_attributes 'tuleap' => {'yum_repo' => 'dev'}