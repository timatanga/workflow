# Workflow

The Workflow module provides capabilites for managing a workflow. This package is heavily inspired by Symfony Workflow component with some specific extensions like:
- instead if guarding a transition by event, condition callbacks allow for more control
- listeners allow for automated transitions 



## Installation
composer require dbisapps/workflow



## Registration
n a Laravel application's config/app.php configuration file, the providers option defines a list of service providers that should be loaded by Laravel. When someone installs your package, you will typically want your service provider to be included in this list. Instead of requiring users to manually add your service provider to the list, auto discovery is enabled by this packages. 



## Class/Object Configuration

To enable a class or object for workflow management some configurations are required:
- implements WorkflowStoreInterface
- uses HasWorkflow trait

		class Subject implements WorkflowStoreInterface
		{ 
		    use HasWorkflow;
		}



### Configuration with Configuration File

For usage in a Laravel application, the config/workflow.php can be used to provide workflow configurations.
Please refer to the tests directory, Config.php for an workflow configuration example. 

Then the path to the configuration file can be passed into the registry class constructor.

    	$registry = new Registry(config_path('workflow.php'));



### Configuration without Configuration File

Building a worklow registry can be done without a configuration file.

		// create registry instance
	    $registry = new Registry;

	    // create event dispatcher which is optional but required for events, listeners
	    $dispatcher = new EventDispatcher;

	    // build transitions
	    $transitions = [];
	    $transitions[] = new Transition('t1', 'a', 'b');
	    $transitions[] = new Transition('t2', 'b', 'c');

	    // build workflow definition
	    $definition = new Definition([
	        'name' => 'workflow name', 
	        'states' => range('a', 'c'),
	        'initial' => 'a',
	        'transitions' => $transitions, 
	    ]);

	    // add workflow and its supported class/object to registry
	    $registry->addWorkflow(new Workflow($definition, $dispatcher), Subject::class);



## Basic Usage

		// retrieve workflow for subject by accessing registry. 
		$workflow = $registry->get(new Subject());

		// when more than one workflow is assigned to subject, the specific workflow must be named 
		$workflow = $registry->get(new Subject(), 'workflow name');

		// retrieve array of enabled transitions, that are applicable on current state and conditions
		$transitions = $workflow->enabledTransitions($subject);

		// retrieve array of blocked transitions as TransitionBlocker 
		$blocked = $workflow->blockedTransitions($subject);

		// evaluate if transition is allowed on subjects current state
		$can = $workflow->can($subject, 't2')

		// apply a transition on subject, supress events (optional). New state/states are returned
		$state = $workflow->apply($subject, 't2', [Workflow::LEAVE])



## Transition Events

Several events are fired before, during and after the transition. Applying an event dispatcher it's possible to hook into the events: Leave > Transition > Enter > (change state) > Entered
Subjects state is changed after enter and befored entered.

Dispatched events can be limited 

in workflow configuration file:

		return [
		    'workflow name' => [
		        'supports' => Subject::class,
		        ...
		        'events' => [Workflow::ENTER_LEAVE, Workflow::ENTER_EVENT],
		]

in workflow definition:

	    return new Definition([
	        'name' => 'workflow name', 
	        ...
	        'events' => [Workfow::LEAVE_EVENT, Workflow::ENTERED]
	    ]);

while applying a transition:

		$state = $workflow->apply($subject, 't2', [Workfow::LEAVE_EVENT, Workflow::ENTERED])



## Transition Conditions

If conditions are assigned to a transition, they all must be fulfilled before the transition is allowed to fire.
Conditions can be configured upfront in the workflow configuration or during runtime.

		// build a condition
	    $fn = new Condition('blocked', function($subject) { return $subject->blocked == false; });

	    // extend a workflow transition with additional condition
	    $workflow->extend('t2', $fn );

	    // remove condition from transition
	    $workflow->reduce('t2', $fn );



## Transition Listeners

Imagine to have an invoice workflow which has arrived in the latest state. While the latest state is reached a payment workflow should be started and payment instance should be persisted in database. This requirement needs some kind of connection between different workflows.

This is where transition listeners come into play. While listening for an event, a workflow transition can be triggered automatically. Basically this is possible within a workflow but also between different workflows.

		// create a callback function 
        $cb = function($event) { $event->getWorkflow()->apply($event->getSubject(), 't2'); };

        // create a listener, when worklfow A has entered state completed, invoke callback
        $listener = new Listener('workflow.workflow_A.entered.completed', $cb);

        // configure listener in workflow definition
        $definitionB = new Definition([
            'name' => 'workflow_B', 
            ...
            'listeners' => $listener,
        ]);

        // create workflows, with shared event dispatcher 
        $eventDispatcher = new EventDispatcher;
        $workflowA = new Workflow($definitionA, $eventDispatcher);
        $workflowB = new Workflow($definitionB, $eventDispatcher);

        // apply transition t2 on subjectA
        $subjectA = new Subject();
        $states = $workflowA->apply($subjectA, 't2');

        // the result of the listener callback can be accessed by listener method
    	// in this case, a new subject has been created
        $subjectB = $listener->result();



## Context Model


	                           					  ----------			   ---------
	CONTROLS                   					   Condition    		   Condition
	                           					  ----------    		   ---------
	                                   				   v     				   v

	                                 ------------            ------------            ------------                  
	PROCESS               x----------    State   ------------    State   ------------    State   
	                      ^           ------------     ^     ------------      ^     ------------           
						  |							   |					   |
						  							   |					   |
	TRANSITION 			  							   O					   O	
						  
						  |
						  |
	LISTENER 			  O
	                       
						  ^							   ^					   ^
	                  ----------				  ----------			  ----------
	RESOURCE            Subject					    Subject 			    Subject
	                  ----------				  ----------			  ----------



	Registry		Workflows must be assigned to supported classes/objects. These assignments are stored
					in the workflow registry.

	Workflow		A workflow manages transitions between states of a supported class/object. It therefor
					contains workflow definitions and capabilities like event dispatcher or logger.

	Definition 		Workflow configurations are provides as definitions, like
					- name	    	workflow name
					- states    	states of the workflow
					- initial   	initial state of the workflow
					- transitions   transitions between states
					- events 		events to dispatch, null to dispatch all events
					- listeners     listeners to automate transitions

	State 			State of a workflow. A state is also called as place.

	Transition 		A Transitions defines the source (froms) and destination (tos) states.
					In case of multiple source destinations all must be met before a transition can be applied.
					Multiple destination states are allowed to be transitioned at once,

    Conditions		Conditions are logical rules (boolean) which must be fulfilled before a transition can take place.
    				A condition normaly is related to its subject attributes.

	Listener 		Listeners extend workflow capabilites to allow for automated transitions.
					State changes are firing events and can trigger subsequent transitions without user interactions.