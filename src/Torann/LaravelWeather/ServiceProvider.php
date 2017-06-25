<?php namespace Torann\LaravelWeather;

use Illuminate\Foundation\AliasLoader;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		// Auto create app alias with boot method.
		AliasLoader::getInstance()->alias('Weather', 'Torann\LaravelWeather\Facade');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['torann.weather'] = $this->app->singleton(function($app)
		{
            		// Get config
            		$config = config('weather');

			return new Weather($app->cache, $app->view, $config);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}
}
