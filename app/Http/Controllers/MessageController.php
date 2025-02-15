<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelIdea\Helper\App\Models\_IH_Message_C;
use Log;

class MessageController extends Controller
{
    /**
     * @return Message[]|Collection|_IH_Message_C
     */
    public function index()
    {
        return Message::all();
    }

    /**
     * @return JsonResponse
     */
    public function getAllMessages(){
        try {
            $messages = Message::get();
            return $this->returnMessages($messages);
        }catch (Exception $e){
            return returnErrorJSON($e);
        }
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return JsonResponse
     */
    public function getMessagesBetween($startDate, $endDate = null){
        if(!$endDate) $endDate = Carbon::now();
        try {
            Log::info($startDate);
            $messages = Message::whereBetween('created_at',[$startDate, $endDate])->get();
            return $this->returnMessages($messages);
        }catch (Exception $e){
            return returnErrorJSON($e);
        }
    }

    /**
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
