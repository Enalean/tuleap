define :yum_clean do
  execute 'yum clean all'
  execute 'yum clean expire-cache'
end
