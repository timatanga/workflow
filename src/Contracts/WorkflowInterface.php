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

interface WorkflowInterface
{

    /**
     * @return string
     */
    public function getName();

    /**
     * @return Definition
     */
    public function getDefinition();

    /**
     * Returns the subject's state.
     *
     * @param object  $subject
     * @return string|array
     * @throws NotFoundException
     */
    public function getState(object $subject);

    /**
     * Returns true if the transition is enabled.
     *
     * @param object  $subject
     * @param string  $transitionName
     * @return bool true if the transition is enabled
     */
    public function can(object $subject, string $transitionName);

    /**
     * Returns all enabled transitions.
     *
     * @param object  $subject
     * @param string  $transitionName
     * @return array all enabled transitions
     */
    public function enabledTransitions(object $subject, string $transitionName);

    /**
     * Returns all blocked transitions.
     *
     * @param object  $subject
     * @param string  $transitionName
     * @return array  of TransitionBlocker
     */
    public function blockedTransitions(object $subject, string $transitionName);

    /**
     * Apply a transition.
     *
     * @param object  $subject
     * @param string  $transitionName
     * @param array  $supress
     * @return string|array new state/states
     * @throws TransitionException if the transition is not applicable
     */
    public function apply(object $subject, string $transitionName, array $supress );

}