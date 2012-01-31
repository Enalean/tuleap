Given /^I commit in svn a file that contains accented letters$/ do
    repository = 'http://valid.cro.enalean.com/svnroot/garden'
    dir        = Dir.mktmpdir
    begin
        svn = Svn.new('project_member', 'project_member')
        svn.checkout(repository, dir)
        open("#{dir}/accented/test.txt", "w") { |f| f.puts "ùù èè éé çç àà" }
        svn.add(dir, 'accented/test.txt')
        svn.commit(dir, "Test accented letters")
    ensure
      # remove the directory.
      FileUtils.remove_entry_secure dir
    end
end

Then /^I should see those characters in viewvc interface$/ do
    steps %Q{
        When I go to The Garden Project
        When I go to the service Subversion
    }
    find(:xpath, "//a[text()='Browse SVN Tree']").click
    find(:xpath, "//a[@name='accented']").click
    find(:xpath, "//a[@name='test.txt']").click
    find(:xpath, "//a[text()='view']").click
    page.should have_content("ùù èè éé çç àà")
    
    # remove the test file
    repository = 'http://valid.cro.enalean.com/svnroot/garden'
    dir        = Dir.mktmpdir
    begin
        svn = Svn.new('project_member', 'project_member')
        svn.checkout(repository, dir)
        svn.del(dir, 'accented/test.txt')
        svn.commit(dir, "Remove test")
    ensure
      # remove the directory.
      FileUtils.remove_entry_secure dir
    end
end

