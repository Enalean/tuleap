def visit_local(url)
    old_app_host = Capybara.app_host
    Capybara.app_host = ''
    visit(url)
    Capybara.app_host = old_app_host
end
