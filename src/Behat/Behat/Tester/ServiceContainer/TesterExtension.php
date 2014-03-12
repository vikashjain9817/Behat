<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Behat\Tester\ServiceContainer;

use Behat\Behat\Definition\ServiceContainer\DefinitionExtension;
use Behat\Testwork\Call\ServiceContainer\CallExtension;
use Behat\Testwork\Cli\ServiceContainer\CliExtension;
use Behat\Testwork\Environment\ServiceContainer\EnvironmentExtension;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\ServiceProcessor;
use Behat\Testwork\Specification\ServiceContainer\SpecificationExtension;
use Behat\Testwork\Suite\ServiceContainer\SuiteExtension;
use Behat\Testwork\Tester\ServiceContainer\TesterExtension as BaseExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Behat tester extension.
 *
 * Provides gherkin testers.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class TesterExtension extends BaseExtension
{
    /*
     * Available services
     */
    const SCENARIO_TESTER_ID = 'tester.scenario';
    const OUTLINE_TESTER_ID = 'tester.outline';
    const EXAMPLE_TESTER_ID = 'tester.example';
    const BACKGROUND_TESTER_ID = 'tester.background';
    const STEP_TESTER_ID = 'tester.step';

    /**
     * Available extension points
     */
    const SCENARIO_TESTER_WRAPPER_TAG = 'tester.scenario.wrapper';
    const OUTLINE_TESTER_WRAPPER_TAG = 'tester.outline.wrapper';
    const EXAMPLE_TESTER_WRAPPER_TAG = 'tester.example.wrapper';
    const BACKGROUND_TESTER_WRAPPER_TAG = 'tester.background.wrapper';
    const STEP_TESTER_WRAPPER_TAG = 'tester.step.wrapper';

    /**
     * @var ServiceProcessor
     */
    private $processor;

    /**
     * Initializes extension.
     *
     * @param null|ServiceProcessor $processor
     */
    public function __construct(ServiceProcessor $processor = null)
    {
        $this->processor = $processor ? : new ServiceProcessor();

        parent::__construct($this->processor);
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        parent::load($container, $config);

        $this->loadRerunController($container);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        parent::process($container);

        $this->processScenarioTesterWrappers($container);
        $this->processOutlineTesterWrappers($container);
        $this->processExampleTesterWrappers($container);
        $this->processBackgroundTesterWrappers($container);
        $this->processStepTesterWrappers($container);
    }

    /**
     * Loads exercise controller.
     *
     * @param ContainerBuilder $container
     * @param Boolean          $strict
     * @param Boolean          $skip
     */
    protected function loadExerciseController(ContainerBuilder $container, $strict = false, $skip = false)
    {
        $definition = new Definition('Behat\Behat\Tester\Cli\ExerciseController', array(
            new Reference(SuiteExtension::REGISTRY_ID),
            new Reference(SpecificationExtension::FINDER_ID),
            new Reference(self::EXERCISE_ID),
            new Reference(self::RESULT_INTERPRETER_ID),
            $strict,
            $skip
        ));
        $definition->addTag(CliExtension::CONTROLLER_TAG, array('priority' => 0));
        $container->setDefinition(CliExtension::CONTROLLER_TAG . '.exercise', $definition);
    }

    /**
     * Loads specification tester.
     *
     * @param ContainerBuilder $container
     */
    protected function loadSpecificationTester(ContainerBuilder $container)
    {
        $definition = new Definition('Behat\Behat\Tester\Runtime\RuntimeFeatureTester', array(
            new Reference(self::SCENARIO_TESTER_ID),
            new Reference(self::OUTLINE_TESTER_ID),
            new Reference(EnvironmentExtension::MANAGER_ID)
        ));
        $container->setDefinition(self::SPECIFICATION_TESTER_ID, $definition);

        $this->loadScenarioTester($container);
        $this->loadOutlineTester($container);
        $this->loadBackgroundTester($container);
        $this->loadStepTester($container);
    }

    /**
     * Loads scenario tester.
     *
     * @param ContainerBuilder $container
     */
    protected function loadScenarioTester(ContainerBuilder $container)
    {
        $definition = new Definition('Behat\Behat\Tester\Runtime\RuntimeScenarioTester', array(
            new Reference(self::STEP_TESTER_ID),
            new Reference(self::BACKGROUND_TESTER_ID)

        ));
        $container->setDefinition(self::SCENARIO_TESTER_ID, $definition);
    }

    /**
     * Loads outline tester.
     *
     * @param ContainerBuilder $container
     */
    protected function loadOutlineTester(ContainerBuilder $container)
    {
        $definition = new Definition('Behat\Behat\Tester\Runtime\RuntimeOutlineTester', array(
            new Reference(self::EXAMPLE_TESTER_ID)
        ));
        $container->setDefinition(self::OUTLINE_TESTER_ID, $definition);

        $this->loadExampleTester($container);
    }

    /**
     * Loads example tester.
     *
     * @param ContainerBuilder $container
     */
    protected function loadExampleTester(ContainerBuilder $container)
    {
        $definition = new Definition('Behat\Behat\Tester\Runtime\RuntimeScenarioTester', array(
            new Reference(self::STEP_TESTER_ID),
            new Reference(self::BACKGROUND_TESTER_ID)
        ));
        $container->setDefinition(self::EXAMPLE_TESTER_ID, $definition);
    }

    /**
     * Loads background tester.
     *
     * @param ContainerBuilder $container
     */
    protected function loadBackgroundTester(ContainerBuilder $container)
    {
        $definition = new Definition('Behat\Behat\Tester\Runtime\RuntimeBackgroundTester', array(
            new Reference(self::STEP_TESTER_ID)
        ));
        $container->setDefinition(self::BACKGROUND_TESTER_ID, $definition);
    }

    /**
     * Loads step tester.
     *
     * @param ContainerBuilder $container
     */
    protected function loadStepTester(ContainerBuilder $container)
    {
        $definition = new Definition('Behat\Behat\Tester\Runtime\RuntimeStepTester', array(
            new Reference(DefinitionExtension::FINDER_ID),
            new Reference(CallExtension::CALL_CENTER_ID)
        ));
        $container->setDefinition(self::STEP_TESTER_ID, $definition);
    }

    /**
     * Loads rerun controller.
     *
     * @param ContainerBuilder $container
     */
    protected function loadRerunController(ContainerBuilder $container)
    {
        $definition = new Definition('Behat\Behat\Tester\Cli\RerunController', array(
            new Reference(EventDispatcherExtension::DISPATCHER_ID)
        ));
        $definition->addTag(CliExtension::CONTROLLER_TAG, array('priority' => 40));
        $container->setDefinition(CliExtension::CONTROLLER_TAG . '.rerun', $definition);
    }

    /**
     * Processes all registered scenario tester wrappers.
     *
     * @param ContainerBuilder $container
     */
    protected function processScenarioTesterWrappers(ContainerBuilder $container)
    {
        $references = $this->processor->findAndSortTaggedServices($container, self::SCENARIO_TESTER_WRAPPER_TAG);

        foreach ($references as $reference) {
            $wrappedTester = $container->getDefinition(self::SCENARIO_TESTER_ID);
            $wrappingTester = $container->getDefinition((string) $reference);
            $wrappingTester->replaceArgument(0, $wrappedTester);

            $container->setDefinition(self::SCENARIO_TESTER_ID, $wrappingTester);
        }
    }

    /**
     * Processes all registered outline tester wrappers.
     *
     * @param ContainerBuilder $container
     */
    protected function processOutlineTesterWrappers(ContainerBuilder $container)
    {
        $references = $this->processor->findAndSortTaggedServices($container, self::OUTLINE_TESTER_WRAPPER_TAG);

        foreach ($references as $reference) {
            $wrappedTester = $container->getDefinition(self::OUTLINE_TESTER_ID);
            $wrappingTester = $container->getDefinition((string) $reference);
            $wrappingTester->replaceArgument(0, $wrappedTester);

            $container->setDefinition(self::OUTLINE_TESTER_ID, $wrappingTester);
        }
    }

    /**
     * Processes all registered example tester wrappers.
     *
     * @param ContainerBuilder $container
     */
    protected function processExampleTesterWrappers(ContainerBuilder $container)
    {
        $references = $this->processor->findAndSortTaggedServices($container, self::EXAMPLE_TESTER_WRAPPER_TAG);

        foreach ($references as $reference) {
            $wrappedTester = $container->getDefinition(self::EXAMPLE_TESTER_ID);
            $wrappingTester = $container->getDefinition((string) $reference);
            $wrappingTester->replaceArgument(0, $wrappedTester);

            $container->setDefinition(self::EXAMPLE_TESTER_ID, $wrappingTester);
        }
    }

    /**
     * Processes all registered background tester wrappers.
     *
     * @param ContainerBuilder $container
     */
    protected function processBackgroundTesterWrappers(ContainerBuilder $container)
    {
        $references = $this->processor->findAndSortTaggedServices($container, self::BACKGROUND_TESTER_WRAPPER_TAG);

        foreach ($references as $reference) {
            $wrappedTester = $container->getDefinition(self::BACKGROUND_TESTER_ID);
            $wrappingTester = $container->getDefinition((string) $reference);
            $wrappingTester->replaceArgument(0, $wrappedTester);

            $container->setDefinition(self::BACKGROUND_TESTER_ID, $wrappingTester);
        }
    }

    /**
     * Processes all registered step tester wrappers.
     *
     * @param ContainerBuilder $container
     */
    protected function processStepTesterWrappers(ContainerBuilder $container)
    {
        $references = $this->processor->findAndSortTaggedServices($container, self::STEP_TESTER_WRAPPER_TAG);

        foreach ($references as $reference) {
            $wrappedTester = $container->getDefinition(self::STEP_TESTER_ID);
            $wrappingTester = $container->getDefinition((string) $reference);
            $wrappingTester->replaceArgument(0, $wrappedTester);

            $container->setDefinition(self::STEP_TESTER_ID, $wrappingTester);
        }
    }
}
