<?php

namespace App\Http\Controllers;

use App\Models\ChatUser;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelIdea\Helper\App\Models\_IH_ChatUser_C;

class ChatUserController extends Controller
{
    /**
     * @return ChatUser[]|Collection|_IH_ChatUser_C
     */
    public function index()
    {
        return ChatUser::all();
    }
    public function getAllChatUsers(){
        try{
            $chatUsers = ChatUser::all();
            foreach ($chatUsers as $chatUser) {
                $user = User::find($chatUser->user_id);
                unset($chatUser->ip);
                unset($chatUser->chat_time);
                unset($chatUser->created_at);
                unset($chatUser->updated_at);
                unset($chatUser->id);
                unset($chatUser->user_id);
                $chatUser->userName = $user?->name;
            }
            return returnEntriesAsJSON($chatUsers,'No-one is listening.');
        }catch (Exception $e){
            return returnErrorJSON($e);
        }
    }
    public function getChatUsersCount()
    {
        try{
            $chatUsers = ChatUser::all();
            return response()->json(['chatUserCount' => count($chatUsers)]);
        }catch (Exception $e){
            return returnErrorJSON($e);
        }
    }
    /**
     * @param Request $request
     * @return ChatUser
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users'],
            'ip' => ['required'],
            'fingerprint' => ['required'],
        ]);

        return ChatUser::create($data);
    }

    /**
     * @param ChatUser $chatUser
     * @return ChatUser
     */
    public function show(ChatUser $chatUser)
    {
        return $chatUser;
    }

    public function update(Request $request, ChatUser $chatUser)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users'],
            'ip' => ['required'],
        ]);

        $chatUser->update($data);

        return $chatUser;
    }

    /**
     * @param ChatUser $chatUser
     * @return JsonResponse
     */
    public function destroy(ChatUser $chatUser)
    {
        $chatUser->delete();

        return response()->json();
    }
}
