<?php

namespace Mtr\TestChat\Http\Controllers;

use App\Models\User;
use Mtr\TestChat\Events\MessageSent;
use Mtr\TestChat\Http\Requests\SendMessageRequest;
use Mtr\TestChat\Models\ChatMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ChatController extends Controller
{
    /*
     * Display the chat interface.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $currentUser = $request->user();
        $users = $this->availableUsers($currentUser->getAuthIdentifier());
        $unreadBySender = $this->unreadBySender($currentUser->getAuthIdentifier());

        $selectedUserId = (int) ($request->integer('user') ?: ($users->first()->getAuthIdentifier() ?? 0));
        $selectedUser = $users->firstWhere('id', $selectedUserId);
        $messages = collect();

        if ($selectedUserId > 0 && $selectedUser) {
            $this->markAsRead($currentUser->getAuthIdentifier(), $selectedUserId);
            $messages = $this->conversationMessages($currentUser->getAuthIdentifier(), $selectedUserId);
            $unreadBySender[$selectedUserId] = 0;
        }

        return view('test-chat::chat.index', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'selectedUserId' => $selectedUserId,
            'messages' => $messages,
            'unreadBySender' => $unreadBySender,
            'currentUserId' => (int) $currentUser->getAuthIdentifier(),
        ]);
    }

    /*
     * Get the list of available users to chat with.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function users(Request $request): JsonResponse
    {
        $currentUserId = (int) $request->user()->getAuthIdentifier();
        $users = $this->availableUsers($currentUserId);
        $unreadBySender = $this->unreadBySender($currentUserId);

        return response()->json([
            'users' => $users->map(function ($user) use ($unreadBySender): array {
                return [
                    'id' => (int) $user->id,
                    'name' => (string) $user->name,
                    'email' => (string) ($user->email ?? ''),
                    'unread' => (int) ($unreadBySender[(int) $user->id] ?? 0),
                ];
            })->values(),
        ]);
    }

    /*
     * Get the conversation messages with a specific user.
     *
     * @param Request $request
     * @param int $user
     * @return JsonResponse
     */
    public function conversation(Request $request, int $user): JsonResponse
    {
        $currentUserId = (int) $request->user()->getAuthIdentifier();
        $partner = $this->findUserOrFail($user, $currentUserId);

        $this->markAsRead($currentUserId, (int) $partner->getAuthIdentifier());

        return response()->json([
            'messages' => $this->conversationMessages($currentUserId, (int) $partner->getAuthIdentifier()),
            'user' => [
                'id' => (int) $partner->id,
                'name' => (string) $partner->name,
                'email' => (string) ($partner->email ?? ''),
            ],
        ]);
    }

    /*
     * Send a message to a specific user.
     *
     * @param SendMessageRequest $request
     * @param int $user
     * @return JsonResponse
     */
    public function send(SendMessageRequest $request, int $user): JsonResponse
    {
        $currentUser = $request->user();
        $senderId = (int) $currentUser->getAuthIdentifier();
        $receiver = $this->findUserOrFail($user, $senderId);

        $message = ChatMessage::query()->create([
            'sender_id' => $senderId,
            'receiver_id' => (int) $receiver->getAuthIdentifier(),
            'body' => $request->string('body')->value(),
            'is_read' => false,
        ]);

        event(new MessageSent($message));

        return response()->json([
            'message' => $this->mapMessage($message, $senderId),
        ], 201);
    }

    /*
     * Get the list of available users to chat with.
     *
     * @param int $currentUserId
     * @return Collection
     */
    private function availableUsers(int $currentUserId): Collection
    {
        return User::query()
            ->whereKeyNot($currentUserId)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /*
     * Find a user by ID or fail with 404. 
     * Also checks if the user is not trying to send a message to themselves.
     *
     * @param int $userId
     * @param int $currentUserId
     * @return object
     */
    private function findUserOrFail(int $userId, int $currentUserId): object
    {
        if ($userId === $currentUserId) {
            abort(422, 'Sending message to yourself is not allowed.');
        }

        return User::query()->findOrFail($userId);
    }

    /*
     * Get the conversation messages between the current user and a partner user.
     *
     * @param int $currentUserId
     * @param int $partnerUserId
     * @return Collection
     */
    private function conversationMessages(int $currentUserId, int $partnerUserId)
    {
        $limit = (int) config('test-chat.messages_per_page', 100);

        $messages = ChatMessage::query()
            ->betweenUsers($currentUserId, $partnerUserId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return $messages->map(fn (ChatMessage $message): array => $this->mapMessage($message, $currentUserId));
    }

    /*
     * Get the count of unread messages 
     * grouped by sender ID for the current user.
     *
     * @param int $currentUserId
     * @return array
     */
    private function unreadBySender(int $currentUserId): array
    {
        return ChatMessage::query()
            ->unreadFor($currentUserId)
            ->selectRaw('sender_id, COUNT(*) as unread_count')
            ->groupBy('sender_id')
            ->pluck('unread_count', 'sender_id')
            ->map(fn ($count): int => (int) $count)
            ->toArray();
    }

    /*
     * Mark messages as read in the conversation between the current user and a partner user.
     *
     * @param int $currentUserId
     * @param int $partnerUserId
     * @return void
     */
    private function markAsRead(int $currentUserId, int $partnerUserId): void
    {
        ChatMessage::query()
            ->where('sender_id', $partnerUserId)
            ->where('receiver_id', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /*
     * Map a ChatMessage model instance to an array for JSON response.
     *
     * @param ChatMessage $message
     * @param int $currentUserId
     * @return array
     */
    private function mapMessage(ChatMessage $message, int $currentUserId): array
    {
        return [
            'id' => (int) $message->id,
            'sender_id' => (int) $message->sender_id,
            'receiver_id' => (int) $message->receiver_id,
            'body' => (string) $message->body,
            'is_read' => (bool) $message->is_read,
            'is_self' => (int) $message->sender_id === $currentUserId,
            'created_at' => $message->created_at?->toIso8601String(),
            'created_at' => $message->created_at?->format('Y-m-d H:i'),
        ];
    }

}