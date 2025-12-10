<?php

namespace App\Http\Controllers\Pool;

use App\Http\Controllers\Controller;
use App\Models\Pool\TaskTemplate;
use Illuminate\Http\Request;

class TaskTemplateController extends Controller
{
    public function index()
    {
        $templates = TaskTemplate::all();
        return view('pool.tasks.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('pool.tasks.templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:daily,weekly,monthly',
            'items' => 'required|json',
            'is_active' => 'boolean',
        ]);

        $items = json_decode($data['items'], true);
        
        // Process items to convert optionsStr to options array
        foreach ($items as &$item) {
            if ($item['type'] === 'select' && isset($item['optionsStr'])) {
                $options = [];
                $pairs = explode(',', $item['optionsStr']);
                foreach ($pairs as $pair) {
                    $parts = explode(':', trim($pair));
                    if (count($parts) >= 2) {
                        $options[trim($parts[0])] = trim($parts[1]);
                    } elseif (!empty(trim($parts[0]))) {
                         $options[trim($parts[0])] = trim($parts[0]);
                    }
                }
                $item['options'] = $options;
            }
        }
        
        $data['items'] = $items;
        $data['is_active'] = $request->has('is_active');

        if ($data['is_active']) {
            TaskTemplate::where('type', $data['type'])->update(['is_active' => false]);
        }

        TaskTemplate::create($data);

        return redirect()->route('pool.tasks.templates.index')->with('success', 'Modèle créé avec succès.');
    }

    public function edit(TaskTemplate $template)
    {
        return view('pool.tasks.templates.edit', compact('template'));
    }

    public function update(Request $request, TaskTemplate $template)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:daily,weekly,monthly',
            'items' => 'required|json',
            'is_active' => 'boolean',
        ]);

        $items = json_decode($data['items'], true);

        // Process items to convert optionsStr to options array
        foreach ($items as &$item) {
            if ($item['type'] === 'select' && isset($item['optionsStr'])) {
                $options = [];
                $pairs = explode(',', $item['optionsStr']);
                foreach ($pairs as $pair) {
                    $parts = explode(':', trim($pair));
                    if (count($parts) >= 2) {
                        $options[trim($parts[0])] = trim($parts[1]);
                    } elseif (!empty(trim($parts[0]))) {
                         $options[trim($parts[0])] = trim($parts[0]);
                    }
                }
                $item['options'] = $options;
            }
        }

        $data['items'] = $items;
        $data['is_active'] = $request->has('is_active');

        if ($data['is_active']) {
            TaskTemplate::where('type', $data['type'])
                ->where('id', '!=', $template->id)
                ->update(['is_active' => false]);
        }

        $template->update($data);

        return redirect()->route('pool.tasks.templates.index')->with('success', 'Modèle mis à jour.');
    }

    public function destroy(TaskTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'Modèle supprimé.');
    }
}
