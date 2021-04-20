@javascript @theme_imtpn
Feature: Front page blocks
  The front page blocks are setup correctly with content

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
    And the following "activities" exist:
      | activity | name            | intro           | type    | course | idnumber        |
      | forum    | Mur pedagogique | Mur pedagogique | general | C1     | MUR_PEDAGOGIQUE |

  Scenario: As a non logged in user I should see the frontpage blocks
    Given I am on homepage
    Then I should see "Acceptance test site"
    And I should see "Log in"
