<?php

/*
 * This file is part of the Workflow package.
 *
 * (c) Mark Fluehmann dbiz.apps@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace timatanga\Workflow\Exceptions;

use timatanga\Workflow\Contracts\WorkflowInterface;
use timatanga\Workflow\Exceptions\TransitionException;

class UndefinedTransitionException extends TransitionException
{

    /**
     * Class constructor
     * 
     * @param object  $subject
     * @param string  $transition
     * @param Workflow  $workflow
     * @param array  $events
     */
    public function __construct(object $subject, string $transition, WorkflowInterface $workflow)
    {
        $message = 'Transition '.$transition.' is not defined for workflow '. $workflow->getName();

        parent::__construct($subject, $transition, $workflow, $message);
    }
}

