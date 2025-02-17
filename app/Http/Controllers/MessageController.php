<?php

namespace App\Http\Controllers;

use App\Models\ChatUser;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LaravelIdea\Helper\App\Models\_IH_Message_C;
use Log;

class MessageController extends Controller
{
    /**
     * Returns all the Message Objects currently in the database.
     * @return Message[]|Collection|_IH_Message_C
     */
    public function index()
    {
        return Message::all();
    }

    /**
     * Returns all messages.
     * @return JsonResponse
     */
    public function getAllMessages(Request $request){
        try {
            deleteInactiveUsers('ChatUsers');
            $userId = $request->query('user_id');
            $listener = ChatUser::where('user_id', $userId)
                ->where('ip', $request->query('ip'))
                ->first();

            if ($listener) {
                $inactiveSeconds = $listener->updated_at->diffInSeconds(now());
                if ($inactiveSeconds > 15) {
                    // Delete the listener if inactive for more than 15 seconds
                    $listener->delete();
                    $chat_time = 0;
                } else {
                    // Calculate new listening time:
                    // Add the time from the last update until now to the existing listening_time.
                    $elapsedSinceLastUpdate =$listener->updated_at->diffInSeconds(now());
                    $chat_time = $listener->chat_time + $elapsedSinceLastUpdate;
                }
            } else {
                $chat_time = 0;
            }

            $sessionToken = Str::uuid();
            $fingerprint = hash('sha256', $request->query('ip') . request()->header('User-Agent'));
            // Then update or create the record
            ChatUser::updateOrCreate(
                [
                    'fingerprint' => $sessionToken,
                ],
                [
                    'user_id' => $userId ?? null,
                    'ip' => $request->query('ip'),
                    'chat_time' => $chat_time,
                    // updated_at will automatically be set to now() if you save the model,
                ]
            );
            $messages = Message::get();
            return $this->returnMessages($messages);
        }catch (Exception $e){
            return returnErrorJSON($e);
        }
    }

    /**
     * Returns all messages filtered by the created_at column.
     * @param $startDate
     * @param $endDate
     * @return JsonResponse
     */
    public function getMessagesBetween(Request $request,$startDate, $endDate = null){
        if(!$endDate) $endDate = Carbon::now();
        try {
            deleteInactiveUsers('ChatUsers');
            $userId = $request->query('user_id');
            $listener = ChatUser::where('user_id', $userId)
                ->where('ip', $request->query('ip'))
                ->first();

            if ($listener) {
                $inactiveSeconds = $listener->updated_at->diffInSeconds(now());
                if ($inactiveSeconds > 15) {
                    // Delete the listener if inactive for more than 15 seconds
                    $listener->delete();
                    $chat_time = 0;
                } else {
                    // Calculate new listening time:
                    // Add the time from the last update until now to the existing listening_time.
                    $elapsedSinceLastUpdate =$listener->updated_at->diffInSeconds(now());
                    $chat_time = $listener->chat_time + $elapsedSinceLastUpdate;
                }
            } else {
                $chat_time = 0;
            }

            $sessionToken = Str::uuid();
            $fingerprint = hash('sha256', $request->query('ip') . request()->header('User-Agent'));
            // Then update or create the record
            ChatUser::updateOrCreate(
                [
                    'fingerprint' => $sessionToken,
                ],
                [
                    'user_id' => $userId ?? null,
                    'ip' => $request->query('ip'),
                    'chat_time' => $chat_time,
                    // updated_at will automatically be set to now() if you save the model,
                ]
            );
            $messages = Message::whereBetween('created_at',[$startDate, $endDate])->get();
            return $this->returnMessages($messages);
        }catch (Exception $e){
            return returnErrorJSON($e);
        }
    }

    /**
     * Saves a message in the database.
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request)
    {
        try {
            // Validate the incoming request data.
            // The sender is now optional (nullable) for non-logged in users.
            $validatedData = $request->validate([
                'sender'  => 'nullable|integer|exists:users,id',
                'content' => 'required|string|max:1000',
            ]);

            // Create a new message.
            $message = Message::create([
                'sender'  => $validatedData['sender'] ?? null,
                'content' => $validatedData['content']
            ]);
            return response()->json([
                'message'     => 'Message sent successfully',
                'content'     => $message
            ]);
        } catch (Exception $e) {
            return returnErrorJSON($e);
        }
    }

    /**
     * Returns the current number of chats from a date to a date.
     * @param $startDate
     * @param $endDate
     * @return JsonResponse
     */
    public function messagesCount($startDate, $endDate = null){
        if(!$endDate) $endDate = Carbon::now();
        try {
            $messages = Message::whereBetween('created_at',[$startDate, $endDate])->get();
            return response()->json(['messagesCount' => count($messages)]);
        }catch (Exception $e){
            return returnErrorJSON($e);
        }
    }

    /**
     * Stores a new Message.
     * @param Request $request
     * @return Message
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'content' => ['required'],
            'sender' => ['required', 'exists:users'],
        ]);

        return Message::create($data);
    }

    /**
     * @param Message $message
     * @return Message
     */
    public function show(Message $message)
    {
        return $message;
    }

    /**
     * Updates a DB entry for the Messages.
     * @param Request $request
     * @param Message $message
     * @return Message
     */
    public function update(Request $request, Message $message)
    {
        $data = $request->validate([
            'content' => ['required'],
            'sender' => ['required', 'exists:users'],
        ]);

        $message->update($data);

        return $message;
    }

    /**
     * @param Message $message
     * @return JsonResponse
     */
    public function destroy(Message $message) : JSONResponse
    {
        $message->delete();

        return response()->json();
    }

    /**
     * @param $messages
     * @return JsonResponse
     */
    private function returnMessages($messages) : JSONResponse
    {
        foreach ($messages as $message) {
            $user = User::find($message->sender);
            $message->userName = $user?->name;
        }
        return returnEntriesAsJSON($messages,'Messages are empty.');
    }
}
