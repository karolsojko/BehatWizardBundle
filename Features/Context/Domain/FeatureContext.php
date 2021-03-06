<?php

namespace Hal\Bundle\BehatWizard\Features\Context\Domain;

use Behat\Mink\Behat\Context\MinkContext;
use Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException,
    Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode,
    Behat\Mink\Exception\ResponseTextException,
    AssertException,
    Behat\Behat\Event\FeatureEvent,
    Behat\Behat\Event\SuiteEvent,
    Behat\Behat\Context\Step\Given,
    Behat\Behat\Context\Step\When,
    Behat\Behat\Context\Step\Then;

/**
 * Features context.
 * 
 * @author Jean-François Lépine <jeanfrancois@lepine.pro>
 * @author Karol Sójko <zoja87@gmail.com>
 */
class FeatureContext extends BehatContext
{

    private $currentFeature;
    private static $FOLDER;

    /**
     * Context initialization
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        self::$FOLDER = $parameters['test_features'];
    }

    protected function getMinkSession()
    {
        return $this->getMainContext()->getSession();
    }

    /**
     * @BeforeFeature
     */
    public static function prepare(FeatureEvent $event)
    {
        if (file_exists(self::$FOLDER)) {
            $files = glob(self::$FOLDER . '/*.*');
            foreach ($files as $filename) {
                unlink($filename);
            }
        }
    }

    /**
     * @AfterFeature
     */
    public static function tearDown(FeatureEvent $event)
    {
        $files = glob(self::$FOLDER . '/*.*');
        foreach ($files as $filename) {
            unlink($filename);
        }
    }

    /**
     * @Then /^I should see that the feature "([^"]*)" exists$/
     */
    public function iShouldSeeThatTheFeatureExists($title)
    {
        return array(
            new When('I go to "/behat/wizard/list"')
            , new Then(sprintf('I should see "%s"', $title))
        );
    }

    /**
     * @When /^I would like to add the feature "([^"]*)"$/
     */
    public function iWouldLikeToAddTheFeature($title)
    {
        $table = new TableNode("|title|\n|{$title}|");
        $hash = $table->getHash();
        $this->currentFeature = $hash[0];
        return array(
            new Given('I go to "/behat/wizard/add"')
            , new When('I remove the scenario "My scenario"')
            , new When(sprintf('I fill in "title" with "%s"', $this->currentFeature['title']))
        );
    }

    /**
     * @When /^I save the current feature$/
     */
    public function iSaveTheCurrentFeature()
    {
        $this->getMinkSession()->getDriver()->click("//*[.='Save']");
        $this->getMinkSession()->wait(4000);
    }

    /**
     * @Given /^this feature has the followings scenarios:$/
     */
    public function thisFeatureHasTheFollowingsScenarios(TableNode $table)
    {
        $hash = $table->getHash();
        $steps = array();
        foreach ($hash as $scenario) {
            $steps = array_merge($steps, array(
                new When('I press "New Scenario"')
                , new When(sprintf('I fill in "Title" with "%s"', $scenario['title']))
                , new When('I press "I finished for this scenario"')
                ));
        }
        return $steps;
    }

    /**
     * @Then /^I should see that this feature contains "([^"]*)" scenarios$/
     */
    public function iShouldSeeThatThisFeatureContainsScenarios($nb)
    {
        return array(
            new When(sprintf('I want to modify the feature "%s"', $this->currentFeature['title']))
            , new Then(sprintf('I should see %d ".scenarios .scenario-title" elements', $nb))
        );
    }

    /**
     * @When /^I want to modify the feature "([^"]*)"$/
     */
    public function iWantToModifyTheFeature($title)
    {
        return array(
            new When(sprintf('I follow "%s"', $title))
        );
    }

    /**
     * @When /^I want to modify the scenario "([^"]*)"$/
     */
    public function iWantToModifyTheScenario($title)
    {
        return array(
            new When(sprintf('I follow "%s"', $title))
        );
    }

    /**
     * @Given /^this feature has the scenario "([^"]*)" with the following steps:$/
     */
    public function thisFeatureHasTheScenarioWithTheFollowingSteps($title, TableNode $scenarioSteps)
    {
        $steps = array(
            new When(sprintf('this feature has the scenario "%s"', $title))
            , new When(sprintf('I want to modify the scenario "%s"', $title))
        );

        $mappingButtons = array(
            'given' => 'simple pre-requisite'
            , 'when' => 'simple event'
            , 'then' => 'simple expected result'
        );
        $mappingInput = array(
            'given' => 'Given'
            , 'when' => 'When'
            , 'then' => 'Then'
        );

        $hash = $scenarioSteps->getHash();
        foreach ($hash as $step) {
            $steps = array_merge($steps, array(
                new When(sprintf('I follow "%s"', $mappingButtons[$step['type']]))
                , new When(sprintf('I fill in the last "step-%s" with "%s"', $step['type'], $step['text']))
                )
            );
        }

        $steps = array_merge($steps, array(new When('I press "I finished for this scenario"')));
        return $steps;
    }

    /**
     * @Then /^I should see that the scenario "([^"]*)" contains "([^"]*)" steps$/
     */
    public function iShouldSeeThatTheScenarioContainsSteps($title, $nbSteps)
    {
        return array(
            new When(sprintf('I want to modify the scenario "%s"', $title))
            , new Then(sprintf('I should see %d ".scenario-content .step" elements', $nbSteps))
        );
    }

    /**
     * @Given /^this feature has the scenario "([^"]*)"$/
     */
    public function thisFeatureHasTheScenario($title)
    {
        $table = new TableNode(
                '| title | '
                . PHP_EOL . sprintf('| %s |', $title)
        );
        return array(
            new When('this feature has the followings scenarios:', $table)
        );
    }

}