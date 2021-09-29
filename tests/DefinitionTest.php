<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use timatanga\Workflow\Definition;
use timatanga\Workflow\Transition;
use \LogicException;

class DefinitionTest extends TestCase
{
    public function testAddPStates()
    {
        $states = range('a', 'e');

        $definition = new Definition([
            'name' => 'WF',
            'states' => $states, 
            'initial' => 'b',
            'transitions' => [], 
            'supports' => 'Party'
        ]);

        $this->assertCount(5, $definition->getStates());

        $this->assertEquals('b', $definition->getInitial());
    }

    public function testSetInitialState()
    {
        $states = range('a', 'e');

        $definition = new Definition([
            'name' => 'WF',
            'states' => $states, 
            'initial' => 'c',
            'transitions' => [], 
            'supports' => 'Party'
        ]);

        $this->assertEquals('c', $definition->getInitial());
    }

    public function testSetInitialStateAndStateIsNotDefined()
    {
        $this->expectException(\InvalidArgumentException::class);

        $states = range('a', 'e');

        $definition = new Definition([
            'name' => 'WF',
            'states' => $states, 
            'initial' => 'f',
            'transitions' => [], 
            'supports' => 'Party'
        ]);
    }

    public function testAddTransition()
    {
        $states = range('a', 'e');

        $transition = new Transition(['name' => 'name', 'from' => 'a', 'to' => 'b']);

        $definition = new Definition([
            'name' => 'WF',
            'states' => $states, 
            'initial' => 'c',
            'transitions' => [$transition], 
            'supports' => 'Party'
        ]);

        $this->assertCount(1, $definition->getTransitions());

        $this->assertSame($transition, $definition->getTransitions()[0]);
    }

    public function testAddTransitionAndFromStateIsNotDefined()
    {
        $this->expectException(\InvalidArgumentException::class);

        $states = range('a', 'e');

        $transition = new Transition(['name' => 'name', 'from' => 'a', 'to' => 'g']);

        $definition = new Definition([
            'name' => 'WF',
            'states' => $states, 
            'initial' => 'c',
            'transitions' => [$transition], 
            'supports' => 'Party'
        ]);

    }

    public function testAddTransitionAndToStateIsNotDefined()
    {
        $this->expectException(\InvalidArgumentException::class);

        $states = range('a', 'e');

        $transition = new Transition(['name' => 'name', 'from' => 'g', 'to' => 'a']);

        $definition = new Definition([
            'name' => 'WF',
            'states' => $states, 
            'initial' => 'c',
            'transitions' => [$transition], 
            'supports' => 'Party'
        ]);
    }
}