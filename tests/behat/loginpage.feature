@theme @javascript @theme_imtpn
Feature: Login page
  The login page has got specific IMTPN features

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    # Fake Shibboleth install.
    Given the following config values are set as admin:
      | user_attribute | username | auth_shibboleth |


  Scenario: I should see the message for external user login and login
    Given I am on homepage
    And I click on "Log in" "link" in the ".logininfo" "css_element"
    Then I wait until the page is ready
    Then I should see "You are an external user ?"
    When I set the field "Username" to "teacher1"
    And I set the field "Password" to "teacher1"
    When I press "Log in"
    Then I should see "You are logged in as"


  Scenario: I should see the message for additional external login methods
    And I log in as "admin"
    And I navigate to "Plugins > Authentication> Manage authentication" in site administration
    And I click on "Enable" "link" in the "Shibboleth" "table_row"
    And I wait until the page is ready
    And I log out
    Given I am on homepage
    And I click on "Log in" "link" in the ".logininfo" "css_element"
    Then I wait until the page is ready
    Then I should see "You are a member of IMT ?"
    Then I should see "Shibboleth Login"
