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

/**
 * A reason why a transition cannot be performed for a subject.
 */
class TransitionBlocker
{

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $message;

    /**
     * @var mixed
     */
    private $parameters;

    /**
     * class constants
     */
    public const BLOCKED_NOTEXIST_CODE = 'NOT_EXIST';
    public const BLOCKED_NOTEXIST_MESSAGE = 'The provided transition does not exist';

    public const BLOCKED_BY_STATE_CODE = 'BLOCKED_BY_STATE';
    public const BLOCKED_BY_STATE_MESSAGE = 'The subject state does not enable the transition.';

    public const BLOCKED_BY_CONDITION_CODE = 'BLOCKED_BY_CONDITION';
    public const BLOCKED_BY_CONDITION_MESSAGE = 'The transition has been blocked by a condition';


    /**
     * Class constructor
     * 
     * @param string  $code       reason code why transition cannot be performed
     * @param string  $message    reason message 
     * @param mixed  $parameters  blocker parameters
     */
    public function __construct(string $code, string $message, $parameters = null )
    { 
        $this->code = $code;

        $this->message = $message;
        
        $this->parameters = $parameters;
    }


    /**
     * Create a blocker that says the transition does not exist
     * 
     * @param string|array  $state  state/states that block transition
     * @return self      
     */
    public static function blockedNotExists( $transition )
    {
        return new static(self::BLOCKED_NOTEXIST_CODE, self::BLOCKED_NOTEXIST_MESSAGE, $transition);
    }


    /**
     * Create a blocker that says the transition cannot be made because it is not enabled.
     * To apply a transition, the subjects needs to be in a predefined state
     * 
     * @param string|array  $state  state/states that block transition
     * @return self      
     */
    public static function blockedByState( $state )
    {
        return new static(self::BLOCKED_BY_STATE_CODE, self::BLOCKED_BY_STATE_MESSAGE, $state);
    }


    /**
     * Create a blocker that says the transition cannot be made because it a condition is not fulfilled
     * To apply a transition callback conditions need to be fulfilled
     * 
     * @param string|array  $state  state/states that block transition
     * @return self      
     */
    public static function blockedByCondition( Condition $condition )
    {
        return new static(self::BLOCKED_BY_CONDITION_CODE, self::BLOCKED_BY_CONDITION_MESSAGE, $condition->getName() );
    }


    /**
     * Get Code
     * 
     * @return string
     */ 
    public function getCode()
    {
        return $this->code;
    }


    /**
     * Get Message
     * 
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
    }


    /**
     * Get Parameters
     * 
     * @return array|null
     */
    public function getParameters()
    {
        return $this->parameters;
    }

}