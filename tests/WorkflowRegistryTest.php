<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\EventDispatcher;
use Tests\Subject;
use Tests\WorkflowBuilderTrait;
use timatanga\Events\Dispatcher;
use timatanga\Workflow\Contracts\WorkflowStoreInterface;
use timatanga\Workflow\Registry;
use timatanga\Workflow\Traits\HasWorkflow;
use timatanga\Workflow\Workflow;

class WorkflowRegistryTest extends TestCase
{
    use WorkflowBuilderTrait;

    private $registry;

    private $dispatcher;

    private $simple;

    private $complex;

    private $same;

    protected function setUp(): void
    {
        $this->registry = new Registry();

        $this->dispatcher = new Dispatcher(['autoDiscover' => false]);

        $this->simple = $this->createSimpleWorkflowDefinition();

        $this->complex = $this->createComplexWorkflowDefinition();

        $this->same = $this->createWorkflowWithSameNameTransition();

        $this->registry->addWorkflow(new Workflow($this->simple, $this->dispatcher), Subject1::class);
        
        $this->registry->addWorkflow(new Workflow($this->complex, $this->dispatcher), Subject2::class);

        $this->registry->addWorkflow(new Workflow($this->same, $this->dispatcher), Subject2::class);

    }

    protected function tearDown(): void
    {
        $this->registry = null;
    }


    public function testRegistryConfig()
    {
        // build config file path
        $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'Config.php';

        $registry = new Registry($configFile);

        $this->assertTrue($registry->has(new Subject()));

        $this->assertTrue($registry->has(new Subject1()));
    }


    public function testRegistryHasWithMatch()
    {
        $this->assertTrue($this->registry->has(new Subject1()));

        $this->assertTrue($this->registry->has(new Subject1(), 'WF_simple'));

        $this->assertFalse($this->registry->has(new Subject1(), 'WF_complex'));
    }


    public function testRegistryHasWithoutMatch()
    {
        $this->assertFalse($this->registry->has(new Subject1(), 'nope'));

        $this->assertFalse($this->registry->has(new Subject2(), 'WF_simple'));
    }


    public function testGetWithSuccess()
    {
        $workflow = $this->registry->get(new Subject1());
        $this->assertInstanceOf(Workflow::class, $workflow);
        $this->assertSame('WF_simple', $workflow->getName());

        $workflow = $this->registry->get(new Subject1(), 'WF_simple');
        $this->assertInstanceOf(Workflow::class, $workflow);
        $this->assertSame('WF_simple', $workflow->getName());

        $workflow = $this->registry->get(new Subject2(), 'WF_complex');
        $this->assertInstanceOf(Workflow::class, $workflow);
        $this->assertSame('WF_complex', $workflow->getName());
    }


    public function testGetWithMultipleMatch()
    {
        $this->expectException(\InvalidArgumentException::class);

        $w = $this->registry->get(new Subject2());
    }


    public function testGetWithNoMatch()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to find a workflow for class: "stdClass"');

        $w = $this->registry->get(new \stdClass());

        $this->assertInstanceOf(Workflow::class, $w);
        $this->assertSame('workflow1', $w->getName());
    }


    public function testAllWithOneMatchWithSuccess()
    {
        $workflows = $this->registry->all(new Subject1());
        $this->assertIsArray($workflows);
        $this->assertCount(1, $workflows);
        $this->assertInstanceOf(Workflow::class, $workflows[0]);
        $this->assertSame('WF_simple', $workflows[0]->getName());
    }


    public function testAllWithMultipleMatchWithSuccess()
    {
        $workflows = $this->registry->all(new Subject2());
        $this->assertIsArray($workflows);
        $this->assertCount(2, $workflows);
        $this->assertInstanceOf(Workflow::class, $workflows[0]);
        $this->assertInstanceOf(Workflow::class, $workflows[1]);
        $this->assertSame('WF_complex', $workflows[0]->getName());
        $this->assertSame('WF sameNameTransition', $workflows[1]->getName());
    }


    public function testAllWithNoMatch()
    {
        $workflows = $this->registry->all(new \stdClass());
        $this->assertIsArray($workflows);
        $this->assertCount(0, $workflows);
    }

}

class Subject1 implements WorkflowStoreInterface
{
    use HasWorkflow;

    protected $state;
}

class Subject2 implements WorkflowStoreInterface
{
    use HasWorkflow;

    protected $state;
}
