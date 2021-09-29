<?php

/*
 * This file is part of the Workflow package.
 *
 * (c) Mark Fluehmann dbiz.apps@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace timatanga\Workflow\Providers;

use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // register a controller
        // $this->app->make('dbizapps\Workflow\...');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // register package routes 
        include __DIR__.'/../../routes/routes.php';

        // publish config file
        $this->publishes([
            __DIR__.'/../../config/workflow.php' => config_path('workflow.php')
        ], 'config');
    }
}
