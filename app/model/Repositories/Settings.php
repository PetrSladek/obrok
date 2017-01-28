<?php

namespace App;

use App\Model\Repositories\SettingsRepository;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;

/**
 * Class SettingsRepository
 * @package App\Model\Repositories
 * @author  peggy <petr.sladek@skaut.cz>
 */
class Settings
{

    /**
     * @var SettingsRepository
     */
    private $settings;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * CachedSettingsRepository constructor.
     * @param SettingsRepository $settings
     * @param IStorage $cacheStorage
     */
    public function __construct(SettingsRepository $settings, IStorage $cacheStorage)
    {
        $this->settings = $settings;
        $this->cache = new Cache($cacheStorage);
    }


    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (($value = $this->cache->load($key)) !== null)
        {
           return $value;
        }

        $value = $this->settings->get($key, $value);

        $this->cache->save($key, $value);

        return $value;
    }


    /**
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    public function set($key, $value)
    {
        $this->cache->save($key, $value);
        $this->settings->set($key, $value);

        return $value;
    }


}