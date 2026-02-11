<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function survey(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        Survey::UpdateOrCreate([
            'telegram_id'   => $data['telegram_id'],
            'items'         => $data['items'],
            ],
        );
        return 200;
    }

    public function voice(Request $request)
    {
        //
    }

    public function showVoice(string $voice_id)
    {
        if (!$voice_id) {
            return 400;
        }

        return 200;
    }


}
