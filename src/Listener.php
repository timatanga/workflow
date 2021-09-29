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

class Listener
{
    /**
     * @var string
     */
    private $event;

    /**
     * @var callable
     */
    private $callback;

    /**
     * @var mixed
     */
    private $result;

    /**
     * class constants
     */
    public const LISTENER_EXCEPTION = 'Listener must be provides as array or comma-separeted list of event, callback';
    public const LISTENER_EVENT_EXCEPTION = 'A event listener requires an event to listen on. None given';
    public const LISTENER_CALLBACK_EXCEPTION = 'Callback must be of type callable';


    /**
     * Class constructor
     * 
     * @param mixed  $attributes    workflow listener attributes
     */
    public function __construct( $attributes )
    { 
        if ( !is_array($attributes) && func_num_args() != 2 )
            throw new \InvalidArgumentException($this->LISTENER_EXCEPTION);

        if ( func_num_args() == 2 )
            $attributes = $this->sanitizeAttributes(func_get_args());

        $this->event = $this->setEvent($attributes);

        $this->callback = $this->setCallback($attributes);
    }


    /**
     * Sanitize attributes
     * 
     * @param  array  $attributes 
     * @return array
     */
    private function sanitizeAttributes( $attributes )
    {
        return [
            'event' => $attributes[0],
            'callback' => $attributes[1],
        ];   
    }


    /**
     * Set Event
     * 
     * @param  array  $attributes 
     * @return string|exception
     */
    private function setEvent( array $attributes = [] )
    {
        if ( !isset($attributes['event']) )
            throw new InvalidArgumentException($this->LISTENER_EVENT_EXCEPTION);

        return $attributes['event'];
    }


    /**
     * Get Event
     * 
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }


    /**
     * Set Callback
     * 
     * @param  array  $attributes 
     * @return array|exception
     */
    private function setCallback( array $attributes = [] )
    {
        if (! isset($attributes['callback']) )
            throw new InvalidArgumentException($this->LISTENER_CALLBACK_EXCEPTION);

        if (! is_callable($attributes['callback']) )
            throw new InvalidArgumentException($this->LISTENER_CALLBACK_EXCEPTION);

        return $attributes['callback'];
    }


    /**
     * Get Context
     * 
     * @return array|null
     */
    public function getCallback()
    {
        return $this->callback;
    }


    /**
     * Execute callback
     * 
     * @return array|null
     */
    public function execute()
    {
        return function($event) {
            $this->result = call_user_func($this->callback, $event);
        };
    }


    /**
     * Get Result
     * 
     * @return mixed
     */
    public function result()
    {
        return $this->result;
    }

}