<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Behat\EventDispatcher\Tester;

use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Behat\Tester\StepTester;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\Environment\Environment;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Behat event-dispatching step tester.
 *
 * Step tester dispatching BEFORE/AFTER events during tests.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class EventDispatchingStepTester implements StepTester
{
    /**
     * @var StepTester
     */
    private $baseTester;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Initializes tester.
     *
     * @param StepTester               $baseTester
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(StepTester $baseTester, EventDispatcherInterface $eventDispatcher)
    {
        $this->baseTester = $baseTester;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(Environment $env, FeatureNode $feature, StepNode $step, $skip)
    {
        $setup = $this->baseTester->setUp($env, $feature, $step, $skip);

        $event = new BeforeStepTested($env, $feature, $step, $setup);
        $this->eventDispatcher->dispatch($event::BEFORE, $event);

        return $setup;
    }

    /**
     * {@inheritdoc}
     */
    public function test(Environment $env, FeatureNode $feature, StepNode $step, $skip)
    {
        return $this->baseTester->test($env, $feature, $step, $skip);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(Environment $env, FeatureNode $feature, StepNode $step, $skip, StepResult $result)
    {
        $teardown = $this->baseTester->tearDown($env, $feature, $step, $skip, $result);

        $event = new AfterStepTested($env, $feature, $step, $result, $teardown);
        $this->eventDispatcher->dispatch($event::AFTER, $event);

        return $teardown;
    }
}
