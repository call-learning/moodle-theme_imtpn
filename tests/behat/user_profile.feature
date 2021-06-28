@theme @javascript @theme_imtpn
Feature: I check that the user profile has the right informaiton
  The user profile should have been modified to include only relevant information

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C2     | student        |
    Given the following config values are set as admin:
      | simplifiedprofilepage      | 1                                                                     | theme_imtpn |
      | profilecomponentsexclusion | report,tool,gradereport,loginactivity,badges,blog,miscellaneous,notes | theme_imtpn |

  Scenario: As a teacher I should see my profiles with my courses
    And I log in as "teacher1"
    And I follow "Profile" in the user menu
    Then I should see "Infos"
    And I should see "Course details"
    And I should see "Course 1"
    And I should not see "Badges"
    And I should not see "Privacy and policies"
    And I should not see "Miscellaneous"
    And I should not see "Login activities"
    And I should not see "Course 2"

  Scenario: As a teacher I should see my profiles with my courses
    And I log in as "student2"
    And I follow "Profile" in the user menu
    Then I should see "Infos"
    And I should see "Course details"
    And I should see "Course 2"
    And I should not see "Course 1"


  Scenario: As a an admin I should be able to switch on and off the profil features
    Given the following config values are set as admin:
      | simplifiedprofilepage | 0 | theme_imtpn |
    And I log in as "student2"
    Then I follow "Profile" in the user menu
    Then I should see "Infos"
    And I should see "Privacy and policies"
    And I should see "Login activity"

  Scenario: As a an admin I should be able to switch on/off some profil features
    Given the following config values are set as admin:
      | profilecomponentsexclusion | report,tool,gradereport,badges,blog,miscellaneous,notes | theme_imtpn |
    And I log in as "student2"
    Then I follow "Profile" in the user menu
    And I should see "Login activity"
    And I should not see "Miscellaneous"
    And I should not see "Privacy and policies"
