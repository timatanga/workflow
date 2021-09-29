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

use timatanga\Workflow\Contracts\WorkflowInterface;
use timatanga\Workflow\Contracts\WorkflowStoreInterface;

class Registry
{

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var array
     */
    private $workflows = [];

    /**
     * class constants
     */
    public const REGISTRY_STORE_EXCEPTION = 'To enable a workflow, the class must implement the WorkflowStoreInterface contract';
    public const REGISTRY_NOTFOUND_EXCEPTION = 'Unable to find a workflow for class';
    public const REGISTRY_TOMANY_EXCEPTION = 'Too many workflows found for subject. Set workflow name explicilty as second param';


    /**
     * Class constructor
     * 
     * @param string  $configFile      full path to workflow config file
     */
    public function __construct( string $configFile = null )
    {

        if ( is_null($configFile) || !is_string($configFile) )
            return;

        $this->config = $this->readConfig($configFile);

        foreach ($this->config as $workflow => $config) {

            // build workflow registry for each supported class/object
            $registries = $this->parseConfig($workflow, $config);

            foreach ( $registries as $registry) { $this->addWorkflow($registry['workflow'], $registry['supports']); };
        }
    }


    /**
     * Add Workflow
     * 
     * @param  workflow  $workflow 
     * @param  string  $class 
     * @return void
     */
    public function addWorkflow(WorkflowInterface $workflow, $class)
    {
        $instance = new $class;

        if (! in_array(WorkflowStoreInterface::class, class_implements($instance)) )
            throw new \InvalidArgumentException(self::REGISTRY_STORE_EXCEPTION);

        $this->workflows[] = [$workflow, $instance];
    }


    /**
     * Has
     * 
     * @param  object  $subject 
     * @param  string  $workflowName 
     * @return boolean
     */
    public function has(object $subject, string $workflowName = null)
    {
        foreach ($this->workflows as [$workflow, $class]) {
            if ( $this->supports($workflow, $class, $subject, $workflowName) )
                return true;
        }

        return false;
    }


    /**
     * Get
     * 
     * @param  object  $subject 
     * @param  string  $workflowName 
     * @return workflow|exception
     */
    public function get(object $subject, string $workflowName = null)
    {
        $workflows = [];

        foreach ($this->workflows as [$workflow, $class]) {
            if ( $this->supports($workflow, $class, $subject, $workflowName) )
                $workflows[] = $workflow;
        }

        if ( empty($workflows) )
            throw new \InvalidArgumentException(self::REGISTRY_NOTFOUND_EXCEPTION . ': "' . get_class($subject) . '"');

        if ( count($workflows) >= 2 )
            throw new \InvalidArgumentException(self::REGISTRY_TOMANY_EXCEPTION . ': "' . implode(', ', $workflows) . '"');

        return $workflows[0];
    }


    /**
     * All
     * 
     * @param  object  $subject 
     * @return array
     */
    public function all(object $subject): array
    {
        $workflows = [];

        foreach ($this->workflows as [$workflow, $class]) {
            if ( $this->supports($workflow, $class, $subject, $workflow->getName()) )
                $workflows[] = $workflow;
        }

        return $workflows;
    }


    /**
     * Workflow supports subject
     * 
     * @param  WorkflowInterface  $workflow 
     * @param  class   $class 
     * @param  object  $subject 
     * @param  string  $workflowName 
     * @return boolean
     */
    private function supports(WorkflowInterface $workflow, $class, object $subject, ?string $workflowName)
    {
        if ( $workflowName !== null && $workflowName !== $workflow->getName() )
            return false;

        if (! in_array(WorkflowStoreInterface::class, class_implements($subject)) )
            return false;

        if ( get_class($class) == get_class($subject) )
            return true;

        return false;
    }


    /**
     * Add Workflow
     * 
     * @param  string  $configFile 
     * @return void
     */
    private function readConfig( string $configFile )
    {
        // throw error if file does not exist
        if ( !static::fileExists($configFile) )
            throw new \Exception("Invalid config file: {$configFile}");

        // load config from config file
        return require $configFile;
    }


    /**
     * Determine if config file exists
     * 
     * Load and extract configuration by key
     *
     * @param string      $file
     * @return bool
     */
    private static function fileExists($file)
    {
        if (!is_file($file) || !file_exists($file))
            return false;

        return true;
    }


    /**
     * Parse Config
     * 
     * @param  string  $workflow 
     * @param  array  $attributes 
     * @return void
     */
    private function parseConfig( string $workflowName, array $attributes )
    {
        try {

            // extract supported classes/objects for workflow
            $supports = $attributes['supports'] ?? null;

            // throw exception if no supported classes/objects are configured
            if ( is_null($supports) )
                throw new \InvalidArgumentException('Missing supported class/object in workflow configuration');

            // cast subjects to array
            $supports = is_array($supports) ? $supports : [$supports];

            // build workflow transitions
            $transitions = $attributes['transitions'] ? 
                array_map(function ($name, $transition) {
                    $transition['name'] = $name;
                    return new Transition($transition);
                }, array_keys($attributes['transitions']), $attributes['transitions']) : null;

            // build workflow definition
            $definition = new Definition([
                'name'          => $workflowName,
                'description'   => $attributes['description'] ?? null,
                'states'        => $attributes['states'] ?? null,
                'transitions'   => $transitions,
                'initial'       => $attributes['initial'] ?? $attributes['states'][0],
                'events'        => $attributes['events'] ?? null,
                'listeners'     => $attributes['listeners'] ?? null,
            ]);

            // create dispatcher instance
            $dispatcher = $attributes['dispatcher'] ? new $attributes['dispatcher'](['autoDiscover' => false]) : null;

            // create logger instance
            $logger = $attributes['logger'] ? new $attributes['logger'] : null;

            // workflow collection
            $workflows = [];

            // create workflow registry config for each subject
            foreach ($supports as $subject) {
                $workflows[] = ['workflow' => new Workflow($definition, $dispatcher, $logger), 'supports' => $subject];
            }

            return $workflows;
        }

        catch( \InvalidArgumentException $e ) {
            throw new \InvalidArgumentException($e->getMessage());
        }

        catch( \Exception $e ) {
            throw new \InvalidArgumentException('Invalid workflow configuration. ' . $e->getMessage());
        }
    }
}