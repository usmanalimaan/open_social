@api @embed @embed-consent @stability @perfect @critical @TB-5671 @stability-4
Feature: Embed
  Benefit: Provides user feature allow/disallow embedded content
  Role: As a SM
  Goal/desire: Change the consent settings as Site mananger and check consent button on Embedded objects in posts, comments and nodes with or without WYSIWYG fields

  Scenario: Check the working of consent settings
    Given I enable the module "social_embed"
    Given I am logged in as a user with the "administer social embed settings" permission
    And I am on "admin/config/opensocial/embed-consent"
    When I check the box "edit-settings"
    And I press the "Save configuration" button
    Then I should see the text "The configuration options have been saved."
    And I logout

    # Create a topic with embedded content.
    Given I am logged in as an "authenticated user"
    Given I am on "node/add/topic"
    And I click radio button "Discussion"
    When I fill in the following:
      | Title | Embed consent |
    And I click on the embed icon in the WYSIWYG editor
    And I wait for AJAX to finish
    And I fill in "URL" with "https://www.youtube.com/watch?v=kgE9QNX8f3c"
    And I click the xth "0" element with the css ".url-select-dialog .form-actions .ui-button"
    And I wait for AJAX to finish
    And I wait for "3" seconds
    And I press "Create topic"
    Then I should see "Topic Embed consent has been created."
    And The iframe in the body description should have the src "Show content"
    And I click "Show content"
    And I wait for AJAX to finish
    And I wait for "3" seconds
    And The iframe in the body description should have the src "https://www.youtube.com/embed/kgE9QNX8f3c"
    And I logout

    # Restore the settings
    Given I am logged in as a user with the "administer social embed settings" permission
    And I am on "admin/config/opensocial/embed-consent"
    When I uncheck the box "edit-settings"
    And I press the "Save configuration" button
    Then I should see the text "The configuration options have been saved."
    And I logout

    # Check the content as LU
    Given I am logged in as an "authenticated user"
    And I am on the homepage
    And I click "Embed consent"
    And The iframe in the body description should have the src "https://www.youtube.com/embed/kgE9QNX8f3c"
