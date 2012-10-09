define :disable_yum_repository do
  execute "sed -i 's/enabled=1/enabled=0/g' /etc/yum.repos.d/#{params[:name]}.repo"
  
  # script "yum -y erase <#{params[:name]} packages>" do
  #   interpreter 'bash'
  #   code <<-SH
  #     packages=`repoquery --repoid=#{params[:name]} -a`
  #     if [ "0$packages" != "0" ]; then
  #       yum -y erase $packages
  #     fi
  #   SH
  # end
end
