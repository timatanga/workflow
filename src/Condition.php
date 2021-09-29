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

class Condition
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
     * @var callable
     */
    private $callback;

    /**
     * class constants
     */
    public const CONDITION_EXCEPTION = 'Invalid condition';
    public const CONDITION_NAME_EXCEPTION = 'Condition must be given a name';
    public const CONDITION_CALLBACK_EXCEPTION = 'Condition must be provided as callback';


    /**
     * Class constructor
     * 
     * @param mixed|array  $attributes
     */
    public function __construct( $attributes )
    { 
        if ( !is_array($attributes) && func_num_args() != 2 )
            throw new \InvalidArgumentException(self::CONDITION_EXCEPTION);

        $attributes = func_num_args() == 2 ? 
            $this->sanitizeAttributes(func_get_args()) :
            $this->sanitizeAttributes($attributes) ;

        $this->name = $this->setName($attributes);

        $this->description = $this->setDescription($attributes);

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
        if ( array_key_exists('name', $attributes) || array_key_exists('callback', $attributes) )
            return $attributes;

        return [
            'name' => $attributes[0],
            'callback' => $attributes[1],
        ];   
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
            throw new \InvalidArgumentException(self::CONDITION_NAME_EXCEPTION);

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
     * Set Callback
     * 
     * @param  array  $attributes 
     * @return array|exception
     */
    private function setCallback( array $attributes = [] )
    {
        if (! isset($attributes['callback']) )
            throw new InvalidArgumentException(self::CONDITION_CALLBACK_EXCEPTION);

        if (! is_callable($attributes['callback']) )
            throw new InvalidArgumentException(self::CONDITION_CALLBACK_EXCEPTION);

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

}