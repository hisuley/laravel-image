<?php namespace Folklore\Image;

use Illuminate\Support\ServiceProvider;

class ImageServiceProvider extends ServiceProvider
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
        // Config file path
        $configFile = __DIR__ . '/../../resources/config/fimage.php';
        $publicFile = __DIR__ . '/../../resources/assets/';

        // Merge files
        $this->mergeConfigFrom($configFile, 'fimage');

        // Publish
        $this->publishes([
            $configFile => config_path('fimage.php')
        ], 'config');
        
        $this->publishes([
            $publicFile => public_path('vendor/folklore/image')
        ], 'public');

        $app = $this->app;
        $router = $app['router'];
        $config = $app['config'];
        
        $pattern = $app['fimage']->pattern();
        $proxyPattern = $config->get('image.proxy_route_pattern');
        $router->pattern('image_pattern', $pattern);
        $router->pattern('image_proxy_pattern', $proxyPattern ? $proxyPattern:$pattern);

        //Serve image
        $serve = config('fimage.serve');
        if ($serve) {
            // Create a route that match pattern
            $serveRoute = $config->get('fimage.serve_route', '{image_pattern}');
            $router->get($serveRoute, array(
                'as' => 'image.serve',
                'domain' => $config->get('fimage.domain', null),
                'uses' => 'Folklore\Image\ImageController@serve'
            ));
        }
        
        //Proxy
        $proxy = $this->app['config']['fimage.proxy'];
        if ($proxy) {
            $serveRoute = $config->get('fimage.proxy_route', '{image_proxy_pattern}');
            $router->get($serveRoute, array(
                'as' => 'image.proxy',
                'domain' => $config->get('fimage.proxy_domain'),
                'uses' => 'Folklore\Image\ImageController@proxy'
            ));
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('fimage', function ($app) {
            return new ImageManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('fimage');
    }
}
