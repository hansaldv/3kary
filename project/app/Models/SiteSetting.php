<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $guarded = ['id'];

    public function scopeGetAll($query)
    {
        // Retrieve settings from cache if available
        $cachedSettings = Cache::get('SITE_SETTINGS');
        if ($cachedSettings) {
            return json_decode($cachedSettings, true);
        }

        // Fetch all settings from the database
        $settings = $query->pluck('st_value', 'st_key')->toArray();

        // Store settings in cache for future use
        Cache::forever('SITE_SETTINGS', json_encode($settings));

        return $settings;
    }
}
