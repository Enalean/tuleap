
When /^I submit a fake preferences form$/ do
    visit('account/preferences.php')
    challenge  = find('input[name=challenge]').value
    local_path = File.dirname(__FILE__)
    File.open(File.expand_path(File.join(local_path, 'form.html')), 'r') { |f|
        contents = f.read.gsub(/<host>/, $tuleap_host).gsub(/<challenge>/, challenge)
        fake_path = '/tmp/fake_pref_form.html'
        File.open(fake_path, 'w') { |fake_script|
            fake_script.puts contents
            fake_script.close
            visit_local("file://"+fake_path)
            find("input[name=Submit]").click
        }
        File.unlink(fake_path)
    }
end

Then /^an error message is displayed for each wrong value$/ do
    page.should have_content('Verify site updates value')
    page.should have_content('Verify additional community mailings value')
    page.should have_content('Verify Font size value')
    page.should have_content('Verify theme value')
    page.should have_content('Verify remember me value')
    page.should have_content('Verify language value')
    page.should have_content('Verify CSV separator value')
    page.should have_content('Verify CSV date format value')
    page.should have_content('Verify username display value')
    page.should have_content('Verify emails format value')
end
