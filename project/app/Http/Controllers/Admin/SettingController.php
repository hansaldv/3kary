<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index($section = null)
    {
        try {
            $view = $section ? "admin.settings.{$section}" : "admin.settings.general";
    
            if (!view()->exists($view)) {
                return abort(404);
            }
    
            return view($view);

        } catch (\Throwable $th) {
            
            return abort(404);
        }
    }
    
    public function updateGeneral(Request $request)
    {
        // Validate incoming request
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:50'],
            'description' => ['required', 'string', 'min:5', 'max:255'],
            'keywords' => ['required', 'string', 'min:5', 'max:255'],
        ]);
    
        // Retrieve all site settings
        $settings = SiteSetting::pluck('st_value', 'st_key');
    
        // Update settings based on validated data
        foreach ($validatedData as $key => $value) {
            if ($settings->has($key)) {
                SiteSetting::where('st_key', $key)->update(['st_value' => $value]);
            }
        }
    
        // Refresh the cache with updated settings
        Cache::forever('SITE_SETTINGS', SiteSetting::pluck('st_value', 'st_key')->toArray());
    
        return back()->with('notify', ['type' => 'info', 'message' => 'Data is updated']);
    }
    
}
