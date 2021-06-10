@api @topic @stability @perfect @critical @DS-2311 @stability-3 @topic-follow-content
Feature: Follow Content
  Benefit: In order receive (email) notification  when a new comments or reply has been placed
  Role: As a Verified
  Goal/desire: I want to be able to subscribe to content

  Scenario: Follow content
    Given I am logged in as an "verified"
    And I am on "user"
    And I click "Topics"
    And I click "Create Topic"
    When I fill in "Title" with "This is a follow topic"
    When I fill in the following:
      | Title | This is a follow topic |
     And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I press "Create topic"
    And I should see "Topic This is a follow topic has been created."
    And I should see "This is a follow topic" in the "Hero block"
    And I should see "Body description text" in the "Main content"
    And I should see the link "Follow content"
    And I should not see the link "Unfollow content"
    And I click "Follow content"
    And I wait for AJAX to finish
    And I should see the link "Unfollow content"
    And I should not see the link "Follow content"
    And I click "Unfollow content"
    And I wait for AJAX to finish
    And I should see the link "Follow content"
    And I should not see the link "Unfollow content"

