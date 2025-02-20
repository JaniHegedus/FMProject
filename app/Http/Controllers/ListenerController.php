<?php

namespace App\Http\Controllers;

use App\Models\ChatUser;
use App\Models\Listener;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelIdea\Helper\App\Models\_IH_Listener_C;

class ListenerController extends Controller
{
    /**
     * @return Listener[]|Collection|_IH_Listener_C
     */
    public function index()
    {
        return Listener::all();
    }

    /**
     * @return JsonResponse
     */
    public function getAllListeners(){
        try{
            $listeners = Listener::all();
            foreach ($listeners as $listener) {
                $user = User::find($listener->user_id);
                unset($listener->ip);
                unset($listener->listening_time);
                unset($listener->created_at);
                unset($listener->updated_at);
                unset($listener->id);
                unset($listener->user_id);
                $listener->userName = $user?->name;
            }
            return returnEntriesAsJSON($listeners,'No-one is listening.');
        }catch (Exception $e){
            return returnErrorJSON($e);
        }
    }

    /**
     * @return JsonResponse
     */
    public function getListenerCount()
    {
        try{
            $listeners = Listener::all();
            return response()->json(['listenerCount' => count($listeners)]);
        }catch (Exception $e){
            return returnErrorJSON($e);
        }
    }
    /**
     * @param Request $request
     * @return Listener
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users'],
            'ip' => ['required'],
            'listening_time' => ['required'],
        ]);

        return Listener::create($data);
    }

    /**
     * @param Listener $listener
     * @return Listener
     */
    public function show(Listener $listener)
    {
        return $listener;
    }

    /**
     * @param Request $request
     * @param Listener $listener
     * @return Listener
     */
    public function update(Request $request, Listener $listener)
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users'],
            'ip' => ['required'],
            'listening_time' => ['required'],
        ]);

        $listener->update($data);

        return $listener;
    }

    public function destroy(Listener $listener)
    {
        $listener->delete();

        return response()->json();
    }
}
