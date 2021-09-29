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

use Psr\Log\LoggerInterface;
use timatanga\Events\Contracts\EventDispatcherInterface;
use timatanga\Workflow\Condition;
use timatanga\Workflow\Contracts\DefinitionInterface;
use timatanga\Workflow\Contracts\WorkflowInterface;
use timatanga\Workflow\Events\EnterEvent;
use timatanga\Workflow\Events\EnteredEvent;
use timatanga\Workflow\Events\LeaveEvent;
use timatanga\Workflow\Events\TransitionEvent;
use timatanga\Workflow\Exceptions\TransitionException;
use timatanga\Workflow\Transition;
use timatanga\Workflow\TransitionBlocker;

class Workflow implements WorkflowInterface
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var Definition
     */
    private $definition;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Class errors
     */
    public const TRANSITION_NOTALLOWED_EXCEPTION = 'Applied transition is not allowed';
    public const WORKFLOW_LISTENER_EXCEPTION = 'Event listeners can not be registered without an event dispatcher';

    /**
     * Class events
     */
    public const LEAVE_EVENT = 'workflow.leave';
    public const TRANSITION_EVENT = 'workflow.transition';
    public const ENTER_EVENT = 'workflow.enter';
    public const ENTERED_EVENT = 'workflow.entered';


    /**
     * Class constructor
     * 
     * @param Definition  $definition
     * @param EventDispatcher  $dispatcher
     * @param Logger  $logger
     * @param array  $events
     */
    public function __construct(
        DefinitionInterface $definition, 
        EventDispatcherInterface $dispatcher = null, 
        LoggerInterface $logger = null, 
    )
    {
        $this->name = $definition->getName();

        $this->definition = $definition;

        $this->dispatcher = $dispatcher;

        $this->logger = $logger;

        $this->registerListeners($definition->getListeners());
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @return Definition
     */
    public function getDefinition()
    {
        return $this->definition;
    }


    /**
     * Returns the workflows initial state
     *
     * @return string
     */
    public function getInitial()
    {
        return $this->definition->getInitial();
    }


    /**
     * Returns the subject's state.
     *
     * @param object  $subject
     * @return string|array
     * @throws NotFoundException
     */
    public function getState( object $subject )
    {
        $state = $subject->getState();

        // check if the subject is already in the workflow else set initial state
        if ( is_null($state) ) {

            $state = $this->definition->getInitial();

            $subject->setState( $state );
        }

        if ( is_string($state) && strpos($state, ',') )
            $state = explode(',', $state);

        return $state;
    }


    /**
     * Returns all enabled transitions.
     *
     * @param object  $subject
     * @param string  $transitionName
     * @return array all enabled transitions
     */
    public function enabledTransitions( object $subject, string $transitionName = null )
    {
        $enabled = $this->getEnabledTransitions( $subject, $transitionName );

        if ( empty($enabled) ) 
            return null;

        if ( !is_null($transitionName) ) 
            return $enabled[0];

        return $enabled;
    }


    /**
     * Returns all blocked transitions.
     *
     * @param object  $subject
     * @param string  $transitionName
     * @return array all blocked transitions
     */
    public function blockedTransitions( object $subject, string $transitionName = null )
    {
        $blocked = $this->getBlockedTransitions( $subject, $transitionName );

        if ( empty($blocked) ) 
            return null;

        if ( !is_null($transitionName) ) 
            return $blocked[0];

        return $blocked;
    }


    /**
     * Extend workflow transition conditions
     *
     * @param string  $transition
     * @param Condition  $condition
     * @return bool true if the transition is enabled
     */
    public function extend( string $transition, Condition $condition )
    {
        $this->definition->extendTransition($transition, $condition);
    }


    /**
     * Reduce workflow transition conditions
     *
     * @param string  $transition
     * @param Condition  $condition
     * @return bool true if the transition is enabled
     */
    public function reduce( string $transition, Condition $condition )
    {
        $this->definition->extendTransition($transition, $condition);
    }


    /**
     * Returns true if the transition is enabled.
     *
     * @param object  $subject
     * @param string  $transitionName
     * @return bool true if the transition is enabled
     */
    public function can( object $subject, string $transitionName )
    {
        // resolve workflow transitions
        $enabled = $this->getEnabledTransitions( $subject, $transitionName );

        if (! empty($enabled) )
            return true;

        return false;
    }


    /**
     * Apply transition
     *
     * @param object  $subject
     * @param string  $transitionName   
     * @param array   $supress          events to supress
     * @return string|array new state/states
     * @throws TransitionException if the transition is not applicable
     */
    public function apply( object $subject, string $transitionName, array $supress = [] )
    {
        // lookup for allowed and enabled transitions
        $enabledTransitions = $this->getEnabledTransitions( $subject, $transitionName );

        if (! $enabledTransitions )
            throw new TransitionException($subject,$transitionName,$this,$this->getBlockedTransitions($subject,$transitionName));

        foreach ($enabledTransitions as $transition) {

            $this->leave($subject, $transition, $supress);

            $states = $this->transition($subject, $transition, $supress);

            $this->enter($subject, $transition, $supress);

            $this->setState($subject, $transition, $states);

            $this->entered($subject, $transition, $supress);
        }

        return $subject->getState();
    }


    /**
     * Evalute if transition exists by name
     *   
     * @param string  $transitionName
     * @return boolean
     */
    private function transitionExists( string $transitionName )
    {
        foreach ($this->definition->getTransitions() as $transition) {
            if ( $transition->getName() == $transitionName )
                return true;
        }

        return false;
    }


    /**
     * Get Enabled Transition
     * 
     * To be an enabled transition the following conditions must be met
     * - next transitions based on subjects current state
     * - filtered based on given transition name (if given)
     * - conditions for transitions are met
     *   
     * @param object  $subject
     * @param string  $transitionName
     * @return array
     */
    private function getEnabledTransitions( object $subject, string $transitionName = null )
    {
        // based on current workflow state, get allowed transitions
        $availableTransitions = $this->getAvailableTransitions( $subject );

        // build result array
        $enabledTransitions = [];

        foreach ($availableTransitions as $transition) {

            // if transitionName is provided, only allow for given transition name
            if ( !is_null($transitionName) && $transition->getName() != $transitionName )
                continue;

            // when transition has no conditions, append transition to result array
            if ( $transition->getConditions() == null ) {

                $enabledTransitions[] = $transition;
                
                continue;
            }

            // call for each condition, if passed append transition to enabled transition
            $fulfilled = true;

            foreach ($transition->getConditions() as $condition) {
                if (! call_user_func($condition->getCallback(), $subject) ) 
                    $fulfilled = false;
            }

            if ( $fulfilled )
                $enabledTransitions[] = $transition;
        }

        return $enabledTransitions;
    }


    /**
     * Get Blocked Transition
     * 
     * To be a blocked transition the following conditions applay
     * - next transitions based on subjects current state
     * - filtered based on given transition name (if given)
     * - conditions for transitions fail
     *   
     * @param object  $subject
     * @param string  $transitionName
     * @return array
     */
    private function getBlockedTransitions( object $subject, string $transitionName = null )
    {
        if (! $this->transitionExists($transitionName) )
            return [TransitionBlocker::blockedNotExists($transitionName)];

        // based on current workflow state, get allowed transitions
        $available = $this->getAvailableTransitions( $subject );

        // build result array
        $blockedTransition = [];

        foreach ($available as $transition) {

            // if transitionName is provided, only allow for given transition name
            if ( !is_null($transitionName) && $transition->getName() != $transitionName )
                $blockedTransition[] = TransitionBlocker::blockedByState($subject->getState());

            // when transition has no conditions, append transition to result array
            if ( $transition->getConditions() == null )
                continue;

            // call for each condition, if failed append transition to blocked transitions
            foreach ($transition->getConditions() as $condition) {
                if (! call_user_func($condition->getCallback(), $subject) )
                    $blockedTransition[] = TransitionBlocker::blockedByCondition($condition);
            }
        }

        return $blockedTransition;
    }


    /**
     * Get Allowed Transition
     * 
     * Get next transitions from current state 
     * 
     * @param object  $subject
     * @return array
     */
    private function getAvailableTransitions( $subject )
    {
        // resolve current workflow state
        $current = $this->getState($subject);

        // cast current states to array
        $current = is_array($current) ? $current : explode(',', $current);

        // resolve workflow transitions
        $transitions = $this->definition->getTransitions();

        return array_filter($transitions, function($transition, $key) use ($current) {

            $froms = $transition->getFroms();

            if ( count($froms) == 1 )
                return in_array($froms[0], $current);

            return $current == $froms;

        }, ARRAY_FILTER_USE_BOTH);
    }


    /**
     * Leave State
     * 
     * @param object  $subject
     * @param Transition  $transition
     * @param array   $supress          events to supress
     */
    private function leave( object $subject, Transition $transition, array $supress )
    {
        // resolve from states
        $states = $transition->getFroms();

        // only registred events get dispatches
        if (! $this->shouldDispatchEvent(self::LEAVE_EVENT, $supress) ) 
            return;

        // dispatch general leave event
        $this->dispatcher->dispatch(new LeaveEvent(self::LEAVE_EVENT, $subject, $transition, $this));

        // dispatch leave event for named workflow
        $this->dispatcher->dispatch(new LeaveEvent('workflow.'.$this->name.'.leave', $subject, $transition, $this));

        // dispatch leave event for left workflow state
        foreach ($states as $state) {
            $this->dispatcher->dispatch(new LeaveEvent('workflow.'. $this->name .'.leave.'. $state, $subject, $transition, $this));
        }
    }


    /**
     * Transition
     * 
     * @param object  $subject
     * @param Transition  $transition
     * @param array   $supress          events to supress
     * @return array   transition to states
     */
    private function transition( object $subject, Transition $transition, array $supress )
    {
        // only registred events get dispatches
        if (! $this->shouldDispatchEvent(self::TRANSITION_EVENT, $supress) ) 
            return $transition->getTos();

        // dispatch general transition event
        $this->dispatcher->dispatch(new TransitionEvent(self::TRANSITION_EVENT, $subject, $transition, $this));

        // dispatch transition event for named workflow
        $this->dispatcher->dispatch(new TransitionEvent('workflow.'. $this->name .'.transition', $subject, $transition, $this));

        // dispatch transition event for transiton name
        $this->dispatcher->dispatch(new TransitionEvent('workflow.'. $this->name .'.transition.' . $transition->getName(), $subject, $transition, $this));

        return $transition->getTos();
    }


    /**
     * Enter
     * 
     * @param object  $subject
     * @param Transition  $transition
     * @param array   $supress          events to supress
     */
    private function enter( object $subject, ?Transition $transition, array $supress )
    {
        // only registred events get dispatches
        if (! $this->shouldDispatchEvent(self::ENTER_EVENT, $supress) )
            return;

        // dispatch general enter event
        $this->dispatcher->dispatch(new EnterEvent(self::ENTER_EVENT, $subject, $transition, $this));

        // dispatch enter event for named workflow
        $this->dispatcher->dispatch(new EnterEvent('workflow.'. $this->name .'.enter', $subject, $transition, $this));

        // resolve state
        $states = $transition->getTos();

        // cast state to array
        $states = is_array($states) ? $states : [$states];

        // dispatch enter event for transiton name
        foreach ($states as $state) {
            $this->dispatcher->dispatch(new EnterEvent('workflow.'. $this->name .'.enter.' . $state, $subject, $transition, $this));
        }
    }


    /**
     * Set Workflow State on Subject
     * 
     * @param object  $subject
     * @param Transition  $transition 
     * @param string|array  $states
     */
    private function setState( object $subject, $transition, $states )
    {
        // get current subject state
        $state = $subject->getState();

        // cast state to array
        $state = is_array($state) ? $state : ['state'];

        // resolve transitions from value(s)
        $froms = $transition->getFroms();

        // if there is only one current state, it could get overwritten
        if ( count($state) == 1 )
            return $subject->setState($states);

        // remove transitions from states
        $state = array_diff($state, $froms);

        // append new workflow states
        return $subject->setState(array_unique(array_merge($state, $states)));
    }


    /**
     * Entered
     * 
     * @param object  $subject
     * @param Transition  $transition
     * @param array   $supress          events to supress
     */
    private function entered( object $subject, Transition $transition, array $supress )
    {
        // only registred events get dispatches
        if (! $this->shouldDispatchEvent(self::ENTERED_EVENT, $supress) )
            return;

        // dispatch general entered event
        $this->dispatcher->dispatch(new EnteredEvent(self::ENTERED_EVENT, $subject, $transition, $this));

        // dispatch entered event for named workflow
        $this->dispatcher->dispatch(new EnteredEvent('workflow.'. $this->name .'.entered', $subject, $transition, $this));

        // resolve state
        $states = $transition->getTos();

        // cast state to array
        $states = is_array($states) ? $states : [$states];

        // dispatch entered event for transiton name
        foreach ($states as $state) {
            $this->dispatcher->dispatch(new EnteredEvent('workflow.'. $this->name .'.entered.' . $state, $subject, $transition, $this));
        }
    }


    /**
     * Should Dispatch Event
     *
     * @param string  $eventName
     * @param array   $supress          events to supress
     * @return boolean
     */
    private function shouldDispatchEvent( string $eventName, array $supress )
    {
        if ( $this->dispatcher === null || $this->definition->getEvents() === [])
            return false;

        if ( !empty($supress) && in_array($eventName, $supress) )
            return false;

        if ( is_null($this->definition->getEvents()) )
            return true;

        return in_array($eventName, $this->definition->getEvents(), true);
    }


    /**
     * Register Listeners
     *
     * @param null|array  $listeners
     * @param array   $supress          events to supress
     * @return boolean
     */
    private function registerListeners( $listeners )
    {
        if ( is_null($this->dispatcher) && !is_null($listeners) )
            throw new \InvalidArgumentException(self::WORKFLOW_LISTENER_EXCEPTION);

        if ( is_null($listeners) )
            return;

        foreach ($listeners as $listener) {
            $this->dispatcher->listen($listener->getEvent(), $listener->execute());        
        }
    }


    /**
     * Return workflow name on class call
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}