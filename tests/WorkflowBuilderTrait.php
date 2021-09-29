<?php

namespace Tests;

use Tests\Subject;
use timatanga\Workflow\Definition;
use timatanga\Workflow\Listener;
use timatanga\Workflow\Transition;

trait WorkflowBuilderTrait
{
    private function createComplexWorkflowDefinition()
    {
        $states = range('a', 'g');

        $transitions = [];
        $transitions[] = new Transition('t1', 'a', ['b', 'c']);
        // $transitions[] = new Transition('t2', ['b', 'c'], 'd');
        $transitions[] = new Transition('t2', ['b', 'c'], 'd', ['Value gt 100', function ($subject) { return $subject->value >= 100; }] );
        $transitions[] = new Transition('t3', 'd', 'e');
        $transitions[] = new Transition('t4', 'd', 'f');
        $transitions[] = new Transition('t5', 'e', 'g');
        $transitions[] = new Transition('t6', 'f', 'g');

        return new Definition([
            'name' => 'WF_complex', 
            'states' => $states, 
            'initial' => 'a',
            'transitions' => $transitions, 
        ]);

        // The graph looks like:
        // +---+     +----+     +---+     +----+     +----+     +----+     +----+     +----+     +---+
        // | a | --> | t1 | --> | c | --> | t2 | --> | d  | --> | t4 | --> | f  | --> | t6 | --> | g |
        // +---+     +----+     +---+     +----+     +----+     +----+     +----+     +----+     +---+
        //             |                    ^          |                                           ^
        //             |                    |          |                                           |
        //             v                    |          v                                           |
        //           +----+                 |        +----+     +----+     +----+                  |
        //           | b  | ----------------+        | t3 | --> | e  | --> | t5 | -----------------+
        //           +----+                          +----+     +----+     +----+
    }

    private function createSimpleWorkflowDefinition()
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
        ]);

        // The graph looks like:
        // +---+     +----+     +---+     +----+     +---+
        // | a | --> | t1 | --> | b | --> | t2 | --> | c |
        // +---+     +----+     +---+     +----+     +---+
    }

    private function createSimpleWorkflowWithSubscriber()
    {
        $states = range('a', 'c');

        $transitions = [];
        $transitions[] = new Transition('t1', 'a', 'b');
        $transitions[] = new Transition('t2', 'b', 'c');

        $cb = function($event) { return new Subject(); };
        $subscriber = new Listener('workflow.WF_simple.completed.t2', $cb);
        $subscribers = [];
        $subscribers[] = $subscriber;

        return new Definition([
            'name' => 'WF_simple_listeners', 
            'states' => $states, 
            'initial' => 'a',
            'transitions' => $transitions, 
            'subscribers' => $subscribers, 
        ]);

        // The graph looks like:
        // +---+     +----+     +---+     +----+     +---+
        // | a | --> | t1 | --> | b | --> | t2 | --> | c |
        // +---+     +----+     +---+     +----+     +---+
    }

    private function createWorkflowWithSameNameTransition()
    {
        $states = range('a', 'c');

        $transitions = [];
        $transitions[] = new Transition('a_to_bc', 'a', ['b', 'c']);
        $transitions[] = new Transition('b_to_c', 'b', 'c');
        $transitions[] = new Transition('to_a', 'b', 'a');
        $transitions[] = new Transition('to_a', 'c', 'a');

        return new Definition([
            'name' => 'WF sameNameTransition', 
            'states' => $states, 
            'initial' => 'a',
            'transitions' => $transitions, 
        ]);

        // The graph looks like:
        //   +------------------------------------------------------------+
        //   |                                                            |
        //   |                                                            |
        //   |         +----------------------------------------+         |
        //   v         |                                        v         |
        // +---+     +---------+     +---+     +--------+     +---+     +------+
        // | a | --> | a_to_bc | --> | b | --> | b_to_c | --> | c | --> | to_a | -+
        // +---+     +---------+     +---+     +--------+     +---+     +------+  |
        //   ^                         |                                  ^       |
        //   |                         +----------------------------------+       |
        //   |                                                                    |
        //   |                                                                    |
        //   +--------------------------------------------------------------------+
    }
}