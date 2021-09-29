<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\EventDispatcher;
use Tests\Subject;
use Tests\WorkflowBuilderTrait;
use timatanga\Events\Dispatcher;
use timatanga\Workflow\Definition;
use timatanga\Workflow\Listener;
use timatanga\Workflow\Traits\HasWorkflow;
use timatanga\Workflow\Transition;
use timatanga\Workflow\Workflow;

class WorkflowListenerTest extends TestCase
{
    use WorkflowBuilderTrait;

    private function createSimpleWorkflowWithListener($listener)
    {
        $states = range('a', 'c');

        $transitions = [];
        $transitions[] = new Transition('t1', 'a', 'b');
        $transitions[] = new Transition('t2', 'b', 'c');

        return new Definition([
            'name' => 'WF_simple', 
            'states' => $states, 
            'initial' => 'a',
            'transitions' => $transitions, 
            'listeners' => $listener,
        ]);

        // The graph looks like:
        // +---+     +----+     +---+     +----+     +---+
        // | a | --> | t1 | --> | b | --> | t2 | --> | c |
        // +---+     +----+     +---+     +----+     +---+
    }


    public function testApplyAutoForward()
    {
        $subject = new Subject();
        $subject->setState('a');

        $cb = function($event) { $event->getSubject()->setValue(200); $event->getWorkflow()->apply($event->getSubject(), 't2'); };
        $listener = new Listener('workflow.WF_simple.entered.b', $cb);

        $definition = $this->createSimpleWorkflowWithListener($listener);

        $eventDispatcher = new Dispatcher(['autoDiscover' => false]);

        $workflow = new Workflow($definition, $eventDispatcher);

        $states = $workflow->apply($subject, 't1');

        $this->assertTrue($subject->getValue() == 200);       
        $this->assertTrue($subject->getState() == 'c');       
    }


    public function testApplyWithCreateNewObject()
    {
        $definitionA = $this->createSimpleWorkflowDefinition();

        $cb = function($event) { return new Subject(); };
        $listener = new Listener('workflow.WF_simple.entered.c', $cb);

        $definitionA = $this->createSimpleWorkflowDefinition();
        $definitionB = $this->createSimpleWorkflowWithListener($listener);

        $eventDispatcher = new Dispatcher(['autoDiscover' => false]);

        $workflowA = new Workflow($definitionA, $eventDispatcher);
        $workflowB = new Workflow($definitionB, $eventDispatcher);

        $subjectA = new Subject();
        $subjectA->setValue(200);
        $subjectA->setState('b');

        $states = $workflowA->apply($subjectA, 't2');

        $subjectB = $listener->result();

        $this->assertTrue($subjectB == new Subject());
        $this->assertTrue($workflowB->getState($subjectB) == 'a');        
        $this->assertTrue($workflowB->apply($subjectB, 't1') == 'b');        
    }
}


class Order
{
    use HasWorkflow;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var int
     */
    public $name = 'initial';


    public function setName( $name )
    {
        if (! is_null($name) )
            $this->name = $name;
    }
}
