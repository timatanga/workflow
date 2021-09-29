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

class TransitionException extends \Exception
{
    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $transition;

    /**
     * @var string
     */
    protected $workflow;

    /**
     * @var array
     */
    protected $blocker;


    /**
     * Class constructor
     * 
     * @param object  $subject
     * @param string  $transition
     * @param Workflow  $workflow
     */
    public function __construct(object $subject, string $transition, WorkflowInterface $workflow, array $blocker)
    {
        parent::__construct('Transition "' . $transition . '" is not enabled for workflow "' . $workflow->getName() . '".');

        $this->subject = $subject;

        $this->transition = $transition;

        $this->workflow = $workflow;

        $this->blocker = $blocker;
    }


    /**
     * Get Subject
     *
     * @return object
     */
    public function getSubject()
    {
        return $this->subject;
    }


    /**
     * Get Transition
     *
     * @return string
     */
    public function getTransition()
    {
        return $this->transition;
    }


    /**
     * Get Workflow
     *
     * @return Workflow
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }


    /**
     * Get Workflow
     *
     * @return Workflow
     */
    public function getBlocker()
    {
        return $this->blocker;
    }
}