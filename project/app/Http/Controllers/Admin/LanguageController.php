<?php

namespace App\Http\Controllers\Admin;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;


class LanguageController extends Controller
{

    public function changeLanguage(Request $request)
    {
        $request->session()->put('locale', $request->locale);
        $language = Language::where('code', $request->locale)->first();
        flash(translate('Language changed to ') . $language->name)->success();
    }

    public function index()
    {
        $languages = Language::paginate(10);
        return view('admin.languages.index', compact('languages'));
    }

    public function create()
    {
        return view('admin.languages.create');
    }

    public function store(Request $request)
    {
        if (Language::where('code', $request->code)->first()) {
            flash(translate('This code is already used for another language'))->error();
            return back();
        }

        $language = new Language;
        $language->name = $request->name;
        $language->code = $request->code;
        $language->save();

        Cache::forget('app.languages');

        flash(translate('Language has been inserted successfully'))->success();
        return redirect()->route('moder.languages.index');
    }

    public function show(Request $request, $id)
    {
        $sort_search = null;
        $language = Language::findOrFail($id);
        $lang_keys = Translation::where('lang', 'en');

        if ($request->has('search')) {
            $sort_search = $request->search;
            $lang_keys = $lang_keys->where('lang_key', 'like', '%' . preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', strtolower($sort_search))) . '%');
        }
        $lang_keys = $lang_keys->paginate(50);

        return view('admin.languages.language_view', compact('language', 'lang_keys', 'sort_search'));
    }

    public function edit($id)
    {
        $language = Language::findOrFail($id);
        return view('admin.languages.edit', compact('language'));
    }
    public function update(Request $request, $id)
    {
        // Validate request data
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
        ]);

        // Check if the code is already in use by another language
        $existingLanguage = Language::where('code', $request->code)
            ->where('id', '!=', $id)
            ->first();

        if ($existingLanguage) {
            flash(translate('This code is already used for another language'))->error();
            return back();
        }

        $language = Language::findOrFail($id);

        // Restrict updates to the default or English language code
        $defaultLanguage = env('DEFAULT_LANGUAGE', 'en');
        if ($language->code === $defaultLanguage && $defaultLanguage !== $request->code) {
            flash(translate('Default language code cannot be edited'))->error();
            return back();
        }

        if ($language->code === 'en' && $request->code !== 'en') {
            flash(translate('English language code cannot be edited'))->error();
            return back();
        }

        // Update language details
        $language->update([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        flash(translate('Language has been updated successfully'))->success();
        return redirect()->route('moder.languages.index');
    }

    public function key_value_store(Request $request)
    {
        $language = Language::findOrFail($request->id);
        foreach ($request->values as $key => $value) {
            $translation_def = Translation::where('lang_key', $key)->where('lang', $language->code)->latest()->first();
            if ($translation_def == null) {
                $translation_def = new Translation;
                $translation_def->lang = $language->code;
                $translation_def->lang_key = $key;
                $translation_def->lang_value = $value;
                $translation_def->save();
            } else {
                $translation_def->lang_value = $value;
                $translation_def->save();
            }
        }
        Cache::forget('translations-' . $language->code);
        flash(translate('Translations updated for ') . $language->name)->success();
        return back();
    }

    public function update_status(Request $request)
    {
        $language = Language::findOrFail($request->id);
        if ($language->code == env('DEFAULT_LANGUAGE') && $request->status == 0) {
            flash(translate('Default language can not be inactive'))->error();
            return 1;
        }
        $language->status = $request->status;
        if ($language->save()) {
            flash(translate('Status updated successfully'))->success();
            return 1;
        }
        return 0;
    }

    public function update_rtl_status(Request $request)
    {
        $language = Language::findOrFail($request->id);
        $language->rtl = $request->status;
        if ($language->save()) {
            flash(translate('RTL status updated successfully'))->success();
            return 1;
        }
        return 0;
    }

    public function destroy($id)
    {
        $language = Language::findOrFail($id);
        if (env('DEFAULT_LANGUAGE') == $language->code) {
            flash(translate('Default language can not be deleted'))->error();
        } elseif ($language->code == 'en') {
            flash(translate('English language can not be deleted'))->error();
        } else {
            if ($language->code == Session::get('locale')) {
                Session::put('locale', env('DEFAULT_LANGUAGE'));
            }
            Language::destroy($id);
            flash(translate('Language has been deleted successfully'))->success();
        }
        return redirect()->route('moder.languages.index');
    }
}
