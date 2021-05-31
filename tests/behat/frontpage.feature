@javascript @theme_imtpn
Feature: Front page blocks
  The front page blocks are setup correctly with content

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
    And the following "activities" exist:
      | activity | name            | intro           | type    | course | idnumber        |
      | forum    | Mur pedagogique | Mur pedagogique | general | C1     | MUR_PEDAGOGIQUE |
    Given the following config values are set as admin:
      | murpedagoidnumber | MUR_PEDAGOGIQUE | theme_imtpn |
      | murpedagoenabled  | 1               | theme_imtpn |

  Scenario: As a non logged in user I should see the frontpage blocks
    Given I am on homepage
    Then I should see "Acceptance test site"
    Then I should see "Catalog" in the ".fixed-top.navbar" "css_element"
    And ".d-inline-block[data-region='drawer-toggle']" "css_element" should not be visible
    Then I should not see "Mur pédagogique" in the ".fixed-top.navbar" "css_element"
    And I should see "Pédagothèque Numérique ?" in the "region-main" "region"
    And I should see "Pour les enseignants" in the "region-main" "region"
    And I should see "Pour les étudiants" in the "region-main" "region"
    And I should see "Cours à la une" in the "region-main" "region"
    And I should see "Quoi de neuf?" in the "region-main" "region"
    And I should see "Access the \"Pédagothèque Numérique\" from anywhere" in the "#page-footer" "css_element"
    And I should see "Log in"


  Scenario: As a logged in user I should see the mur pedagogique link if I am registered in the course
    Given I log in as "student1"
    Then I should see "Catalog" in the ".fixed-top.navbar" "css_element"
    Then I should see "Mur pédagogique" in the ".fixed-top.navbar" "css_element"

  Scenario: As an admin I should see the mur pedagogique link if I am registered in the course
    Given I log in as "admin"
    Then I should see "Catalog" in the ".fixed-top.navbar" "css_element"
    Then I should see "Mur pédagogique" in the ".fixed-top.navbar" "css_element"

  Scenario: As a logged in user I should not see the mur pedagogique link if I am registered in the course
    Given I log in as "student2"
    Then I should see "Catalog" in the ".fixed-top.navbar" "css_element"
    Then I should not see "Mur pédagogique" in the ".fixed-top.navbar" "css_element"
    # TODO Fix the following step
    # And I log out

  Scenario: When the mur pedagogique is disabled I should not see the menu
    Given the following config values are set as admin:
      | murpedagoidnumber | MUR_PEDAGOGIQUE | theme_imtpn |
      | murpedagoenabled  | 0               | theme_imtpn |
    Then I log in as "student1"
    Then I should not see "Mur pédagogique" in the ".fixed-top.navbar" "css_element"

  Scenario: I should be able to login from the main Log in Button
    Given I am on site homepage
    When I click on ".header__button a" "css_element"
    And I should see "Acceptance test site: Log in to the site"