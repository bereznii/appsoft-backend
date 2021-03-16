<?php

namespace App\Http\Controllers;

use App\Models\Telegram;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Http\ResponseFactory;
use Laravel\Lumen\Routing\Controller as BaseController;

/**
 * Class Controller
 * @package App\Http\Controllers
 */
class Controller extends BaseController
{
    /**
     * @param Request $request
     * @param Telegram $telegram
     * @return Response|int|ResponseFactory
     */
    public function form(Request $request, Telegram $telegram)
    {
        $validated = $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'phone' => 'string|max:255',
            'city' => 'string|max:255',
            'message' => 'string|max:60000',
            'files.*' => 'file|max:5000'
        ]);

        try {
            $files = !empty($validated['files'])
                ? $telegram->prepareRequestWithFiles($validated)
                : null;

            $validated = array_map(fn ($item) => is_string($item) ? trim($item) : $item, $validated);

            $id = DB::table('form_data')->insertGetId($validated);

            $telegram->sendToTelegram($validated, $id, $files);
        } catch (\Throwable $e) {
            Log::error($e->getMessage(), $e->getTrace());
            return response(['success' => false]);
        }

        return response(['success' => (bool) $id]);
    }
}
