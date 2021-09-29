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

use timatanga\Workflow\Condition;

class Transition
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
    private $froms;

    /**
     * @var array
     */
    private $tos;

    /**
     * @var array|null
     */
    private $conditions;

    /**
     * class constants
     */
    public const TRANSITION_EXCEPTION = 'Transition must be delivered as array or comma separated list of name, from, to';
    public const TRANSITION_NAME_EXCEPTION = 'Transition requires a name. None given';
    public const TRANSITION_FROM_EXCEPTION = 'Transition requires a state to come from. None given';
    public const TRANSITION_TO_EXCEPTION = 'Transition requires a state to end on. None given';
    public const TRANSITION_CONDITION_EXCEPTION = 'Transition condition must be of type callable';


    /**
     * Class constructor
     * 
     * @param mixed|array  $attributes    workflow transition attributes
     */
    public function __construct( $attributes )
    { 
        if ( !is_array($attributes) && func_num_args() != 3 && func_num_args() != 4 )
            throw new \InvalidArgumentException(self::TRANSITION_EXCEPTION);

        if ( func_num_args() == 3 || func_num_args() == 4 )
            $attributes = $this->sanitizeAttributes(func_get_args());

        $this->name = $this->setName($attributes);

        $this->description = $this->setDescription($attributes);

        $this->froms = $this->setFroms($attributes);

        $this->tos = $this->setTos($attributes);

        $this->conditions = $this->setConditions($attributes);
    }


    /**
     * Sanitize attributes
     * 
     * @param  array  $attributes 
     * @return array
     */
    private function sanitizeAttributes( $attributes )
    {
        $attrs = [
            'name' => $attributes[0],
            'from' => $attributes[1],
            'to' => $attributes[2],
        ];

        if ( count($attributes) == 4 )
            $attrs['conditions'] = $attributes[3];

        return $attrs;     
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
            throw new \InvalidArgumentException(self::TRANSITION_NAME_EXCEPTION);

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
     * Set Froms
     * 
     * @param  array  $attributes 
     * @return array|exception
     */
    private function setFroms( array $attributes = [] )
    {
        if (! isset($attributes['from']) )
            throw new \InvalidArgumentException(self::TRANSITION_FROM_EXCEPTION);

        $froms = is_array($attributes['from']) ? $attributes['from'] : [$attributes['from']];

        return $froms;
    }


    /**
     * Get Froms
     * 
     * @return array|null
     */
    public function getFroms()
    {
        return $this->froms;
    }


    /**
     * Set Tos
     * 
     * @param  array  $attributes 
     * @return array|exception
     */
    private function setTos( array $attributes = [] )
    {
        if ( !isset($attributes['to']) )
            throw new \InvalidArgumentException(self::TRANSITION_TO_EXCEPTION);

        $tos = is_array($attributes['to']) ? $attributes['to'] : [$attributes['to']];

        return $tos;
    }


    /**
     * Get Tos
     * 
     * @return array|null
     */
    public function getTos()
    {
        return $this->tos;
    }


    /**
     * Set Conditions
     * 
     * @param  array  $attributes 
     * @return array|exception
     */
    public function setConditions( array $attributes = [] )
    {
        if ( !isset($attributes['conditions']) )
            return null;

        $conditions = $attributes['conditions'] instanceof Condition ? 
            [$attributes['conditions']] : [new Condition($attributes['conditions'])];

        return $conditions;
    }


    /**
     * Toggle Condition
     * 
     * Append condition if given callback is not registered for transition
     * Remove condition if given callback is registered for transition
     * 
     * @param  Condition  $condition 
     * @return void|exception
     */
    public function toggleCondition( Condition $condition )
    {
        // initialise conditions register
        if (! is_array($this->conditions) )
            $this->conditions = [];

        // in case callback is registred, remove callback
        if ( $key = $this->conditionExists($condition) ) {

            unset($this->conditions[$key]);
            
            return;
        }

        // append callback as condition
        $this->conditions[] = $condition;
    }


    /**
     * Condition exists
     * 
     * @param  Condition  $condition 
     * @return null|string  returns null when callback is not registered, else array key in conditions array
     */
    private function conditionExists( Condition $condition )
    {
        // loop over registred conditions, retorn array position if found
        foreach ($this->conditions as $key => $lookup) {
            if ( $lookup == $condition )
                return $key;
        }

        // condition not found
        return null;
    }


    /**
     * Get Conditions
     * 
     * @return array|null
     */
    public function getConditions()
    {
        return $this->conditions;
    }

}
