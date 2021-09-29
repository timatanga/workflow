<?php

/*
 * This file is part of the Workflow package.
 *
 * (c) Mark Fluehmann dbiz.apps@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace timatanga\Workflow;

use timatanga\Workflow\Contracts\DefinitionInterface;
use timatanga\Workflow\Listener;
use timatanga\Workflow\State;
use timatanga\Workflow\Transition;

class Definition implements DefinitionInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var array
     */
    private $states;

    /**
     * @var string
     */
    private $initial;

    /**
     * @var array|null
     */
    private $transitions;

    /**
     * @var array|null
     */
    private $events;

    /**
     * @var array|null
     */
    private $listeners;

    /**
     * class constants
     */
    public const DEFINITION_NAME_EXCEPTION = 'A workflow requires a name. None given';
    public const DEFINITION_SUPPORT_EXCEPTION = 'A workflow must be assigned to a supported class. None given';
    public const DEFINITION_STATE_EXCEPTION = 'A workflow must have assigned states. None given';
    public const DEFINITION_INITIAL_EXCEPTION = 'A workflow must have an initial states. None given';
    public const DEFINITION_INITIAL_ERROR_EXCEPTION = 'The given initial state is not available';
    public const DEFINITION_TRANSITION_CALLABLE = 'Condition does not fulfill callable type';
    public const DEFINITION_TRANSITION_EXCEPTION = 'A workflow must have assigned transitions. None given';
    public const DEFINITION_TRANSITION_REFERENCE_EXCEPTION = 'State referenced in transition does not exist';
    public const DEFINITION_TRANSITION_NOTFOUND_EXCEPTION = 'Transition with given name does not exist';


    /**
     * Class constructor
     * 
     * @param array  $attributes    workflow definition attributes
     */
    public function __construct( array $attributes = [] )
    { 
        $this->name = $this->setName($attributes);

        $this->description = $this->setDescription($attributes);

        $this->setStates($attributes);

        $this->setInitial($attributes);

        $this->setTransitions($attributes);

        $this->setEvents($attributes);

        $this->setListeners($attributes);
    }


    /**
     * Set Name
     * 
     * @param  array  $attributes 
     * @return string|exception
     */
    private function setName( array $attributes = [] )
    {
        if ( !isset($attributes['name']) )
            throw new \InvalidArgumentException(self::DEFINITION_NAME_EXCEPTION);

        return $attributes['name'];
    }


    /**
     * Get Name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Set Description
     * 
     * @param  array  $attributes 
     * @return string|exception
     */
    private function setDescription( array $attributes = [] )
    {
        if ( !isset($attributes['description']) )
            return null;

        return $attributes['description'];
    }


    /**
     * Get Description
     * 
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }


    /**
     * Set States
     * 
     * @param  array  $attributes 
     * @return string|exception
     */
    private function setStates( array $attributes = [] )
    {
        if ( !isset($attributes['states']) )
            throw new \InvalidArgumentException(self::DEFINITION_STATE_EXCEPTION);

        foreach( $attributes['states'] as $state ) {
           $this->addState($state);
        }
    }


    /**
     * Add state
     * 
     * @param  mixed  $state 
     * @return void
     */
    public function addState( $state )
    {
        if (! $state instanceof State )
            $state = new State(['name' => $state]);

        $this->states[$state->getName()] = $state;
    }


    /**
     * Get States
     * 
     * @param string  $state
     * @return string
     */
    public function getStates( $state = null )
    {
        if ( is_null($state) ) 
            return $this->states;

        $filtered = array_filter($this->states, function($item) use ($state) {
            return $item->getName() == $state;
        });

        if (! isset($filtered) )
            throw new \InvalidArgumentException('The state %s is not available within definition');

        return $filtered;
    }


    /**
     * Jas States
     * 
     * @param string  $state
     * @return string
     */
    private function hasState( $state = null )
    {
        if ( is_null($state) ) 
            return false;

        $filtered = array_filter($this->states, function($item) use ($state) {
            return $item->getName() == $state;
        });

        if ( empty($filtered) )
            return false;

        return true;
    }


    /**
     * Set Initial
     * 
     * @param  array  $attributes 
     * @return string|exception
     */
    private function setInitial( array $attributes = [] )
    {
        if (! isset($attributes['initial']) )
            throw new \InvalidArgumentException(self::DEFINITION_INITIAL_EXCEPTION);

        foreach ($this->states as $state) {

            if ( $state->getName() == $attributes['initial'] ) {
                $this->initial = $attributes['initial'];
                return;
            }

        }

        throw new \InvalidArgumentException(self::DEFINITION_INITIAL_ERROR_EXCEPTION);

    }


    /**
     * Get Initial
     * 
     * @return string
     */
    public function getInitial()
    {
        return $this->initial;
    }


    /**
     * Set Transitions
     * 
     * @param  array  $attributes 
     * @return string|exception
     */
    private function setTransitions( array $attributes = [] )
    {
        if (! isset($attributes['transitions']) )
            throw new \InvalidArgumentException(self::DEFINITION_TRANSITION_EXCEPTION);

        foreach( $attributes['transitions'] as $transition ) {
           $this->addTransition($transition);
        }
    }


    /**
     * Add Transition
     * 
     * @param  Transition  $transition 
     * @return void
     */
    public function addTransition( Transition $transition )
    {
        foreach ($transition->getFroms() as $from) {
            if (! $this->hasState($from) )
                throw new \InvalidArgumentException(self::DEFINITION_TRANSITION_REFERENCE_EXCEPTION .': '. $from);
        }

        foreach ($transition->getTos() as $to) {
            if (! $this->hasState($to) )
                throw new \InvalidArgumentException(self::DEFINITION_TRANSITION_REFERENCE_EXCEPTION .': '. $to);
        }

        $this->transitions[] = $transition;
    }


    /**
     * Extend Transition
     * 
     * @param  string  $transitionName 
     * @param  Condition $condition 
     * @return void
     */
    public function extendTransition( string $transitionName, Condition $condition )
    {
        foreach ($this->transitions as $key => $transition) {
            if ( $transition->getName() == $transitionName )
                return $this->transitions[$key]->toggleCondition($condition);
        }

        throw new \InvalidArgumentException(self::DEFINITION_TRANSITION_NOTFOUND_EXCEPTION);
    }


    /**
     * Get Transitions
     * 
     * @param string  $transitionName
     * @return string
     */
    public function getTransitions( $transitionName = null )
    {
        if ( is_null($transitionName) ) 
            return $this->transitions;

        foreach ($this->transitions as $transition) {
            if ( $transition->getName() == $transitionName )
                return $transition;
        }

        throw new \InvalidArgumentException('The given transition does not exist');
    }


    /**
     * Set Events
     * 
     * @param  array  $attributes 
     * @return string|exception
     */
    private function setEvents( array $attributes = [] )
    {
        if ( !isset($attributes['events']) )
            return null;

        $this->events = $attributes['events'];
    }


    /**
     * Get Events
     * 
     * @return string
     */
    public function getEvents()
    {
        return $this->events;
    }


    /**
     * Set Listeners
     * 
     * @param  mixed  $attributes 
     * @return string|exception
     */
    private function setListeners( $attributes )
    {
        if ( !isset($attributes['listeners']) )
            return null;

        $listeners = $attributes['listeners'] instanceof Listener ? [$attributes['listeners']] : $attributes['listeners'];

        foreach( $listeners as $listener ) {
           $this->addListener($listener);
        } 
    }


    /**
     * Add Listener
     * 
     * @param  Listener  $listener 
     * @return void
     */
    public function addListener( Listener $listener )
    {
        $this->listeners[] = $listener;
    }


    /**
     * Get Listeners
     * 
     * @return string
     */
    public function getListeners()
    {
        return $this->listeners;
    }
}
