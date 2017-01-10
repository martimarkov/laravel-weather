<?php namespace Torann\LaravelWeather;

use Illuminate\Cache\CacheManager;
use Illuminate\View\Factory;

class Weather
{
    /**
     * Cache manager
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * Factory view.
     *
     * @var \Illuminate\View\Factory
     */
    protected $view;

    /**
     * Weather config.
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new command instance.
     *
     * @param \Illuminate\Cache\CacheManager $cache
     * @param \Illuminate\View\Factory       $view
     * @param array                          $config
     */
    public function __construct(CacheManager $cache, Factory $view, $config)
    {
        $this->cache  = $cache;
        $this->view   = $view;
        $this->config = $config;
    }

    /**
     * Render weather widget by location name.
     *
     * @param  string $name
     * @param  string $units
     * @return string
     */
    public function renderByName($name = null, $units = null)
    {
        // Remove commas
        $name = strtolower(str_replace(', ', ',', $name));

        return $this->generate(array(
            'query' => "q={$name}",
            'units' => $units ?: $this->config['defaults']['units']
        ));
    }

    /**
     * Render weather widget by geo point.
     *
     * @param  float  $lat
     * @param  float  $lon
     * @param  string $units
     * @return string
     */
    public function renderByPoint($lat, $lon, $units = null)
    {
        return $this->generate(array(
            'query' => "lat={$lat}&lon={$lon}",
            'units' => $units ?: $this->config['defaults']['units']
        ));
    }

    /**
     * Render weather widget.
     *
     * @param  array  $options
     * @return string
     */
    public function generate($options = array())
    {
        // Get options
        $options = array_merge($this->config['defaults'], $options);

        // Unify units
        $options['units'] = strtolower($options['units']);
        if (! in_array($options['units'], array('metric', 'imperial'))) {
            $options['units'] = 'imperial';
        }

        // Create cache key
        $cacheKey = 'Weather.'.md5(implode($options));

        // Check cache
        if ($this->config['cache'] && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        // Get current weather
        $current = $this->getWeather($options['query'], 0, $options['units'], 1);

        if($current['cod'] !== 200) {
            return 'Unable to load weather';
        }

        // Get forecast
        $forecast = $this->getWeather($options['query'], $options['days'], $options['units']);

        // Render view
        $data = array(
            'current'  => $current,
            'forecast' => $forecast,
            'units'    => $options['units'],
            'date'     => $options['date']
        );

        // Add to cache
        if ($this->config['cache']) {
            $this->cache->put($cacheKey, $data, $this->config['cache']);
        }

        return $data;
    }

    public function getIcon($code)
    {
        switch ($code) {
            case 200 :
            case 201 :
            case 202 :
            case 210 :
            case 211 :
            case 212 :
            case 230 :
            case 221 :
            case 231 :
            case 232 :
                return 'icon-weather-lightning';
            case 300 :
            case 301 :
            case 302 :
            case 310 :
            case 311 :
            case 312 :
            case 313 :
            case 314 :
            case 321 :
            case 511:
            case 520:
            case 521:
            case 522:
            case 531:
                return 'icon-weather-pouring';
            case 500 :
            case 501 :
            case 502 :
            case 503 :
            case 504 :
                return 'icon-weather-rainy';
            case 600:
            case 601:
            case 602:
            case 611:
            case 612:
            case 615:
            case 616:
            case 620:
            case 621:
            case 622:
                return 'icon-weather-snowy';
            case 701:
            case 711:
            case 721:
            case 731:
            case 741:
            case 751:
            case 761:
            case 762:
            case 771:
            case 781:
                return 'icon-weather-fog';
            case 800:
                return 'icon-weather-sunny';
            case 801 :
                return 'icon-weather-partlycloudy';
            case 802 :
            case 803 :
            case 804 :
                return 'icon-weather-cloudy';
        }
    }

    public function getWindDirection($deg)
    {
        if ($deg >= 0 && $deg < 22.5) return 'N';
        elseif ($deg >= 22.5 && $deg < 45) return 'NNE';
        elseif ($deg >= 45 && $deg < 67.5) return 'NE';
        elseif ($deg >= 67.5 && $deg < 90) return 'ENE';
        elseif ($deg >= 90 && $deg < 122.5) return 'E';
        elseif ($deg >= 112.5 && $deg < 135) return 'ESE';
        elseif ($deg >= 135 && $deg < 157.5) return 'SE';
        elseif ($deg >= 157.5 && $deg < 180) return 'SSE';
        elseif ($deg >= 180 && $deg < 202.5) return 'S';
        elseif ($deg >= 202.5 && $deg < 225) return 'SSW';
        elseif ($deg >= 225 && $deg < 247.5) return 'SW';
        elseif ($deg >= 247.5 && $deg < 270) return 'WSW';
        elseif ($deg >= 270 && $deg < 292.5) return 'W';
        elseif ($deg >= 292.5 && $deg < 315) return 'WNW';
        elseif ($deg >= 315 && $deg < 337.5) return 'NW';
        elseif ($deg >= 337.5 && $deg < 360) return 'NNW';
    }

    private function getWeather($query, $days = 1, $units = 'internal', $type = 0, $lang = 'en')
    {
        $forecast = ($type == 0) ? 'forecast/daily?' : 'weather?';
        $apiKey = env('OWM_API_KEY', '');
        return $this->request("http://api.openweathermap.org/data/2.5/{$forecast}{$query}&cnt={$days}&units={$units}&mode=json&lang={$lang}&appid={$apiKey}");
    }

    private function request($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
        curl_setopt($ch, CURLOPT_MAXCONNECTS, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode( $response, true );
    }
}
