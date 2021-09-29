<?php

/*
 * This file is part of the Workflow package.
 *
 * (c) Mark Fluehmann dbiz.apps@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace timatanga\Workflow\Traits;

trait HasWorkflow
{

    /**
     * @var string
     */
    protected $property = 'state';


    /**
     * Get Property
     * 
     * Get property on which the workflow state is stored/persisted
     * 
     * @return string|null
     */
    public function getProperty()
    {
        return $this->property;
    }


    /**
     * Set State
     * 
     * Set workflow state/states
     * 
     * @param  mixed  $states 
     * @return string|exception
     */
    public function setState( $states )
    {
        // cast states to string
        $state = is_array($states) ? implode(',', $states) : $states;

        if ( $this->hasEloquentMutator() )
            return $this->setEloquentMutator($state);

        return $this->{$this->property} = $state;
    }


    /**
     * Get State
     * 
     * Get current workflow state/states
     * 
     * @return string|array
     */
    public function getState()
    {
        $state = $this->{$this->property};

        if ( is_string($state) && strpos($state, ',') )
            $state = explode(',', $state);

        return $state;
    }


    /**
     * Has Eloquent Mutator
     * 
     * Evaluates if an eloquent style mutator on subject exists
     * 
     * @return bool
     */
    private function hasEloquentMutator()
    {
        $method = 'set' . ucfirst($this->property) . 'Attribute';

        return method_exists($this, $method);
    }


    /**
     * Set Eloquent Mutator
     * 
     * Invoke mutator to set state on subject with context options
     * 
     * @param  string  $state 
     * @return bool
     */
    private function setEloquentMutator($state)
    {
        $method = 'set' . ucfirst($this->property) . 'Attribute';

        return $this->{$method}($state);
    }
}