define :epel_package do
  execute "yum -y --enablerepo=epel install #{params[:name]}"
end
