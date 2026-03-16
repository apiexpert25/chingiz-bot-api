<?php

namespace App\Http\Controllers;

use App\Entities\PromptEntity;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    public function edit()
    {
        $prompt = PromptEntity::get();
        return view('prompts.edit', compact('prompt'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        PromptEntity::update($request->content);

        return back()->with('success', 'Prompt updated successfully!');
    }
}
