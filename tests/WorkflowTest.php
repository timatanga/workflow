<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\EventDispatcher;
use Tests\WorkflowBuilderTrait;
use \LogicException;
use timatanga\Workflow\Contracts\EventDispatcherInterface;
use timatanga\Workflow\Events\Event;
use timatanga\Workflow\Events\GuardEvent;
use timatanga\Workflow\Events\TransitionEvent;
use timatanga\Workflow\Exceptions\NotEnabledTransitionException;
use timatanga\Workflow\Exceptions\TransitionException;
use timatanga\Workflow\Exceptions\UndefinedTransitionException;
use timatanga\Workflow\Condition;
use timatanga\Workflow\Definition;
use timatanga\Workflow\Transition;
use timatanga\Workflow\TransitionBlocker;
use timatanga\Workflow\Workflow;

class WorkflowTest extends TestCase
{
    use WorkflowBuilderTrait;


    /** ****************** Workflow ****************** */


    public function testCreateWorkflow()
    {
        $definition = $this->createSimpleWorkflowDefinition();

        $workflow = new Workflow($definition);

        $this->assertTrue($workflow->getName() == 'WF_simple');

        $this->assertTrue($workflow->getInitial() == 'a');
    }


    public function testCreateWorkflowWithoutName()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->expectExceptionMessage('A workflow requires a name. None given');

        $workflow = new Workflow(new Definition([], []));
    }


    /** ****************** Subject ****************** */


    public function testGetSubjectState()
    {
        $subject = new Subject();

        $this->assertTrue($subject->getProperty() == 'state');
    }


    public function testGetStateOnEmptyInitialState()
    {
        $subject = new Subject();

        $definition = $this->createComplexWorkflowDefinition();

        $workflow = new Workflow($definition);

        $state = $workflow->getState($subject);

        $this->assertTrue($state == 'a');

        $this->assertTrue($subject->getState() == 'a');
    }


    public function testGetStateOnPresetState()
    {
        $subject = new Subject();
        $subject->setState(['b', 'c']);

        $definition = $this->createComplexWorkflowDefinition();
                
        $workflow = new Workflow($definition);

        $state = $workflow->getState($subject);

        $this->assertTrue($state == ['b', 'c']);

        $this->assertTrue($subject->getState() == ['b', 'c']);
    }


    /** ****************** Transitions ****************** */


    public function testGetEnabledTransitions()
    {  
        $subject = new Subject();

        $definition = $this->createComplexWorkflowDefinition();

        $workflow = new Workflow($definition);
        $this->assertTrue($workflow->enabledTransitions($subject)[0]->getName() == 't1');

        $subject->setState('d');
        $transitions = $workflow->enabledTransitions($subject);

        $this->assertCount(2, $transitions);
        $this->assertSame('t3', $transitions[0]->getName());
        $this->assertSame('t4', $transitions[1]->getName());

        $subject->setState(['c', 'e']);
        $transitions = $workflow->enabledTransitions($subject);
        $this->assertCount(1, $transitions);
        $this->assertSame('t5', $transitions[0]->getName());

        $subject->setState(['b', 'c']);
        $transitions = $workflow->enabledTransitions($subject);
        $this->assertCount(1, $transitions);
        $this->assertSame('t2', $transitions[0]->getName());
    }


    public function testGetEnabledTransition()
    {
        $subject = new Subject();
        $subject->setState(['d']);

        $definition = $this->createComplexWorkflowDefinition();

        $workflow = new Workflow($definition);

        $transition = $workflow->enabledTransitions($subject, 't3');
        $this->assertInstanceOf(Transition::class, $transition);
        $this->assertSame('t3', $transition->getName());

        $transition = $workflow->enabledTransitions($subject, 'does_not_exist');
        $this->assertNull($transition);
    }

    public function testGetBlockedTransitions()
    {
        $subject = new Subject();
        $subject->setState(['d']);

        $definition = $this->createComplexWorkflowDefinition();

        $workflow = new Workflow($definition);

        $blocker = $workflow->blockedTransitions($subject, 't3');
        $this->assertTrue($blocker instanceof TransitionBlocker);
        $this->assertTrue($blocker->getCode() == 'BLOCKED_BY_STATE');
        $this->assertTrue($blocker->getParameters() == 'd');

        $blocker = $workflow->blockedTransitions($subject, 'does_not_exist');
        $this->assertTrue($blocker instanceof TransitionBlocker);
        $this->assertTrue($blocker->getCode() == 'NOT_EXIST');
        $this->assertTrue($blocker->getParameters() == 'does_not_exist');
    }


    public function testGetEnabledTransitionsWithSameNameTransition()
    {
        $subject = new Subject();

        $definition = $this->createWorkflowWithSameNameTransition();

        $workflow = new Workflow($definition);
 
        // $transitions = $workflow->enabledTransitions($subject);
        // $this->assertCount(1, $transitions);
        // $this->assertSame('a_to_bc', $transitions[0]->getName());

        $subject->setState(['b', 'c']);
        $transitions = $workflow->enabledTransitions($subject);
        $this->assertCount(3, $transitions);
        $this->assertSame('b_to_c', $transitions[0]->getName());
        $this->assertSame('to_a', $transitions[1]->getName());
        $this->assertSame('to_a', $transitions[2]->getName());
    }


    /** ****************** Can ****************** */


    public function testCanOnUnexistingTransition()
    {
        $subject = new Subject();
        $subject->setState('a');

        $definition = $this->createComplexWorkflowDefinition();
        
        $workflow = new Workflow($definition);

        $this->assertFalse($workflow->can($subject, 'foobar'));
    }


    public function testCanOnExistingTransition()
    {
        $subject = new Subject();
        $subject->setState('a');

        $definition = $this->createComplexWorkflowDefinition();
    
        $workflow = new Workflow($definition);

        $this->assertTrue($workflow->can($subject, 't1'));
    }


    public function testCanOnMultipleRequiredStates()
    {
        $subject = new Subject();
        $subject->setState(['b', 'c']);

        $definition = $this->createComplexWorkflowDefinition();
                
        $workflow = new Workflow($definition);

        $this->assertTrue($workflow->can($subject, 't2'));
    }


    public function testCanOnDifferentStates()
    {
        $subject = new Subject();

        $definition = $this->createComplexWorkflowDefinition();
        
        $workflow = new Workflow($definition);

        $this->assertTrue($workflow->can($subject, 't1'));
        $this->assertFalse($workflow->can($subject, 't2'));

        $subject->setState('b');
        $this->assertFalse($workflow->can($subject, 't1'));
        $this->assertFalse($workflow->can($subject, 't2'));

        $subject->setState(['b', 'c']);
        $this->assertFalse($workflow->can($subject, 't1'));
        $this->assertTrue($workflow->can($subject, 't2'));

        $subject->setState('f');
        $this->assertFalse($workflow->can($subject, 't5'));
        $this->assertTrue($workflow->can($subject, 't6'));
    }


    public function testCanOnFulfilledConditions()
    {
        $subject = new Subject();
        $subject->setState(['b', 'c']);

        $definition = $this->createComplexWorkflowDefinition();
        
        $workflow = new Workflow($definition);

        $this->assertTrue($workflow->can($subject, 't2'));
    }


    public function testCanOnUnfulfilledConditions()
    {
        $subject = new Subject();
        $subject->setState(['b', 'c']);
        $subject->setValue(80);

        $definition = $this->createComplexWorkflowDefinition();
        
        $workflow = new Workflow($definition);

        $this->assertFalse($workflow->can($subject, 't2'));
    }


   public function testCanOnExtendedFulfilledConditions()
    {
        $subject = new Subject();
        $subject->setState(['b', 'c']);

        $definition = $this->createComplexWorkflowDefinition();
        
        $workflow = new Workflow($definition);
        $workflow->extend('t2', new Condition('blocked', function($subject) { return $subject->blocked == true; }) );

        $this->assertTrue($workflow->can($subject, 't2'));
    }


    public function testCanOnExtendedUnFulfilledConditions()
    {
        $subject = new Subject();
        $subject->setState(['b', 'c']);

        $definition = $this->createComplexWorkflowDefinition();

        $workflow = new Workflow($definition);
        $workflow->extend('t2', new Condition('blocked', function($subject) { return $subject->blocked == false; }) );

        $this->assertFalse($workflow->can($subject, 't2'));
    }


    public function testCanOnToggledConditions()
    {
        $subject = new Subject();
        $subject->setState(['b', 'c']);

        $definition = $this->createComplexWorkflowDefinition();

        $workflow = new Workflow($definition);

        $fn = new Condition('blocked', function($subject) { return $subject->blocked == false; });

        $workflow->extend('t2', $fn );
        $this->assertFalse($workflow->can($subject, 't2'));

        $workflow->reduce('t2', $fn );
        $this->assertTrue($workflow->can($subject, 't2'));

    }


    public function testCanWithSameNameTransition()
    {
        $subject = new Subject();

        $definition = $this->createWorkflowWithSameNameTransition();

        $workflow = new Workflow($definition);
        $this->assertTrue($workflow->can($subject, 'a_to_bc'));
        $this->assertFalse($workflow->can($subject, 'b_to_c'));
        $this->assertFalse($workflow->can($subject, 'to_a'));

        $subject->setState('b');
        $this->assertFalse($workflow->can($subject, 'a_to_bc'));
        $this->assertTrue($workflow->can($subject, 'b_to_c'));
        $this->assertTrue($workflow->can($subject, 'to_a'));
    }


    /** ****************** Apply ****************** */


    public function testApplyWithNotExisingTransition()
    {
        $subject = new Subject();

        $definition = $this->createComplexWorkflowDefinition();

        $workflow = new Workflow($definition);

        try {
            $workflow->apply($subject, '404 Not Found');

        } catch (TransitionException $e) {

            $this->assertSame('Transition "404 Not Found" is not enabled for workflow "WF_complex".', $e->getMessage());

        }
    }


    public function testApplyWithNotEnabledTransition()
    {
        $subject = new Subject();

        $definition = $this->createComplexWorkflowDefinition();

        $workflow = new Workflow($definition);

        try {
            $workflow->apply($subject, 't2');

        } catch (TransitionException $e) {

            $this->assertSame('Transition "t2" is not enabled for workflow "WF_complex".', $e->getMessage());
            $this->assertCount(1, $e->getBlocker());

            $list = $e->getBlocker();
            $this->assertSame('The subject state does not enable the transition.', $list[0]->getMessage());
            $this->assertSame($e->getWorkflow(), $workflow);
            $this->assertSame($e->getSubject(), $subject);
            $this->assertSame($e->getTransition(), 't2');
        }
    }


    public function testApply()
    {
        $subject = new Subject();

        $definition = $this->createComplexWorkflowDefinition();

        $workflow = new Workflow($definition);

        $state = $workflow->apply($subject, 't1');

        $this->assertTrue($state == ['b', 'c']);
    }


    public function testApplyWithSameNameTransition()
    {
        $subject = new Subject();

        $definition = $this->createWorkflowWithSameNameTransition();

        $workflow = new Workflow($definition);

        $state = $workflow->apply($subject, 'a_to_bc');
        $this->assertTrue($state == ['b', 'c']);

        $state = $workflow->apply($subject, 'to_a');
        $this->assertTrue($state == 'a');

        $workflow->apply($subject, 'a_to_bc');

        $state = $workflow->apply($subject, 'b_to_c');
        $this->assertTrue($state == 'c');

        $state = $workflow->apply($subject, 'to_a');
        $this->assertTrue($state == 'a');
    }


    public function testApplyWithSameNameTransition2()
    {
        $subject = new Subject();
        $subject->setState(['a', 'b']);

        $states = range('a', 'd');
        $transitions = [];
        $transitions[] = new Transition('t', 'a', 'c');
        $transitions[] = new Transition('t', 'b', 'd');
        
        $definition =  new Definition([
            'name' => 'WF', 
            'states' => $states, 
            'initial' => 'a',
            'transitions' => $transitions, 
            'supports' => 'Subject'
        ]);

        $workflow = new Workflow($definition);

        $state = $workflow->apply($subject, 't');
        $this->assertTrue($state == ['c','d']);
    }

    public function testApplyWithSameNameTransition3()
    {
        $subject = new Subject();
        $subject->setState('a');

        $states = range('a', 'd');

        $transitions = [];
        $transitions[] = new Transition('t', 'a', 'b');
        $transitions[] = new Transition('t', 'b', 'c');
        $transitions[] = new Transition('t', 'c', 'd');

        $definition =  new Definition([
            'name' => 'WF', 
            'states' => $states, 
            'initial' => 'a',
            'transitions' => $transitions, 
            'supports' => 'Subject'
        ]);

        $workflow = new Workflow($definition);

        $state = $workflow->apply($subject, 't');
        // We want to make sure we do not end up in "d"
        $this->assertTrue($state == 'b');
    }

}
