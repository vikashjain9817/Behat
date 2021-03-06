Feature: Locale configuration
  In order to display feature in custom language
  As a feature writer
  I need to be able to the locale inside the configuration file

  Background:
    Given a file named "features/bootstrap/FeatureContext.php" with:
      """
      <?php

      use Behat\Behat\Context\Context,
        Behat\Behat\Tester\Exception\PendingException;
      use Behat\Gherkin\Node\PyStringNode,
        Behat\Gherkin\Node\TableNode;

      class FeatureContext implements Context
      {
      }
      """

  Scenario:
    Given a file named "behat.yml" with:
      """
      default:
        translation:
          locale: fr
      """
    When I run "behat --no-colors -f progress"
    Then it should pass with:
      """
      Pas de scénario
      Pas d'étape
      """

  Scenario:
    Given a file named "behat.yml" with:
      """
      default:
        translation:
          locale: en
      """
    When I run "behat --no-colors -f progress"
    Then it should pass with:
      """
      No scenarios
      No steps
      """
