<?php

require_once 'BrowserController.class.php';

class CreateUserTest extends BrowserController
{

    public function testUserShouldBeAbleToRegisterItself()
    {
        $this->logout();
        $this->open("/");
        $this->click("link=New User");
        $this->waitForPageToLoad("30000");
        $this->type("name=form_loginname", "project_admin_1");
        $this->type("id=form_pw", "welcome0");
        $this->type("name=form_pw2", "welcome0");
        $this->type("name=form_realname", "Project Admin 1");
        $this->type("name=form_email", "project.admin1@foobar.net");
        $this->select("id=timezone", "label=Europe/Paris");
        $this->click("name=Register");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isElementPresent("css=ul.feedback_error"));
        $this->assertTrue($this->isTextPresent("Registration Confirmation"));

        // Validate by Admin
        $this->login('admin', 'siteadmin');
        $this->click("link=P (pending) status");
        $this->waitForPageToLoad("30000");
        $this->click("css=center > table > tbody > tr > td > form > input[name=submit]");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("No Pending User Registration to Approve"));
    }

    public function testSiteAdminShouldBeAbleToRegisterUsers()
    {
        $this->login('admin', 'siteadmin');

        // First user
        $this->open("/admin/");
        $this->click("link=New user");
        $this->waitForPageToLoad("30000");
        $this->type("name=form_loginname", "project_member_1");
        $this->type("id=form_pw", "welcome0");
        $this->type("name=form_realname", "Project Member 1");
        $this->type("name=form_email", "project.member1@foobar.net");
        $this->select("id=timezone", "label=Europe/Paris");
        $this->click("name=Register");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isElementPresent("css=ul.feedback_error"));
        $this->assertTrue($this->isTextPresent("Registration Confirmation"));

        // Second user
        $this->open("/admin/");
        $this->click("link=New user");
        $this->waitForPageToLoad("30000");
        $this->type("name=form_loginname", "lambda_user_1");
        $this->type("id=form_pw", "welcome0");
        $this->type("name=form_realname", "Lambda User 1");
        $this->type("name=form_email", "lambda.user1@foobar.net");
        $this->select("id=timezone", "label=Europe/Paris");
        $this->click("name=Register");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isElementPresent("css=ul.feedback_error"));
        $this->assertTrue($this->isTextPresent("Registration Confirmation"));
    }
}
?>