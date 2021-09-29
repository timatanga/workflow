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

class State
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
     * @var array/null
     */
    private $properties;

    /**
     * @var array|null
     */
    private $context;

    /**
     * class constants
     */
    public const STATE_NAME_EXCEPTION = 'Name of state is required. None given';


    /**
     * Class constructor
     * 
     * @param array  $attributes    workflow state attributes
     */
    public function __construct( array $attributes = [] )
    { 
        $this->name = $this->setName($attributes);

        $this->description = $this->setDescription($attributes);

        $this->properties = $this->setProperties($attributes);

        $this->context = $this->setContext($attributes);
    }


    /**
     * Get State
     * 
     * @return array
     */
    public function get()
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'properties' => $this->getProperties(),
            'context' => $this->getContext(),
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
            throw new InvalidArgumentException(self::STATE_NAME_EXCEPTION);

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
     * Set Properties
     * 
     * @param  array  $attributes 
     * @return array|exception
     */
    private function setProperties( array $attributes = [] )
    {
        if ( !isset($attributes['properties']) )
            return null;

        return $attributes['properties'];
    }


    /**
     * Get Properties
     * 
     * @return array|null
     */
    public function getProperties()
    {
        return $this->properties;
    }



    /**
     * Set Context
     * 
     * @param  array  $attributes 
     * @return array|exception
     */
    private function setContext( array $attributes = [] )
    {
        if ( !isset($attributes['context']) )
            return null;

        $context = is_array($attributes['context']) ? $attributes['context'] : [$attributes['context']];

        return context;
    }


    /**
     * Get Context
     * 
     * @return array|null
     */
    public function getContext()
    {
        return $this->context;
    }

}