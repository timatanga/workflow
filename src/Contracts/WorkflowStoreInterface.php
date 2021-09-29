<?php

/*
 * This file is part of the Workflow package.
 *
 * (c) Mark Fluehmann dbiz.apps@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace timatanga\Workflow\Contracts;

interface WorkflowStoreInterface
{

    /**
     * Get Property
     * 
     * Get property on which the workflow state is stored/persisted
     * 
     * @return string|null
     */
    public function getProperty();

    /**
     * Set State
     * 
     * Set workflow state on subject
     * 
     * @param  mixed  $states 
     * @return string|exception
     */
    public function setState( $states );

    /**
     * Get State
     * 
     * Get current workflow state (as string) or states (as array)
     * 
     * @return string|array
     */
    public function getState();

}