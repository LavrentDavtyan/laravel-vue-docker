<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use App\Http\Requests\Share\StoreShareTopicRequest;
use App\Models\ShareTopic;
use App\Models\ShareTopicMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShareTopicController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) auth()->id();
        if (!$userId) return response()->json(['message' => 'Unauthenticated'], 401);

        $topics = ShareTopic::query()
            ->leftJoin('share_topic_members as m', 'm.topic_id', '=', 'share_topics.id')
            ->where(function ($q) use ($userId) {
                $q->where('share_topics.owner_user_id', $userId)
                  ->orWhere('m.user_id', $userId);
            })
            ->selectRaw('share_topics.id, share_topics.title, share_topics.currency, share_topics.status')
            ->selectRaw('(select count(*) from share_topic_members where topic_id = share_topics.id) as members')
            ->groupBy('share_topics.id', 'share_topics.title', 'share_topics.currency', 'share_topics.status')
            ->orderByDesc('share_topics.id')
            ->get();

        return response()->json(['data' => ['topics' => $topics]]);
    }

    public function store(StoreShareTopicRequest $request)
    {
        $userId = (int) auth()->id();
        if (!$userId) return response()->json(['message' => 'Unauthenticated'], 401);

        $payload = $request->validated();
        $payload['owner_user_id'] = $userId;
        $payload['invite_token']  = Str::random(40);

        return DB::transaction(function () use ($payload, $userId) {
            $topic = ShareTopic::create($payload);

            ShareTopicMember::create([
                'topic_id'     => $topic->id,
                'user_id'      => $userId,
                'display_name' => auth()->user()->name ?? 'Owner',
                'role'         => 'owner',
                'joined_at'    => now(),
            ]);

            return response()->json(['data' => ['topic_id' => $topic->id]], 201);
        });
    }

    public function members(Request $request, ShareTopic $topic)
    {
        $userId = (int) auth()->id();

        $inTopic = ShareTopicMember::where('topic_id', $topic->id)
            ->where('user_id', $userId)
            ->exists();

        abort_unless($inTopic, 403, 'Not part of this topic.');

        return response()->json([
            'data' => [
                'members'      => ShareTopicMember::where('topic_id', $topic->id)->get(),
                'invite_token' => $topic->invite_token,
                'is_owner'     => $topic->owner_user_id === $userId,
                'status'       => $topic->status,
            ],
        ]);
    }

    public function rotateInvite(Request $request, ShareTopic $topic)
    {
        $this->authorizeOwner((int) auth()->id(), $topic);

        $topic->invite_token = Str::random(40);
        $topic->save();

        return response()->json(['data' => ['invite_token' => $topic->invite_token]]);
    }

    public function joinByToken(Request $request, string $token)
    {
        $userId = (int) auth()->id();
        $topic = ShareTopic::where('invite_token', $token)->firstOrFail();

        $exists = ShareTopicMember::where('topic_id', $topic->id)
            ->where('user_id', $userId)
            ->exists();

        if (!$exists) {
            ShareTopicMember::create([
                'topic_id'     => $topic->id,
                'user_id'      => $userId,
                'display_name' => auth()->user()->name ?? 'Member',
                'role'         => 'member',
                'joined_at'    => now(),
            ]);
        }

        return response()->json(['data' => ['topic_id' => $topic->id]]);
    }

    public function leave(Request $request, ShareTopic $topic)
    {
        $userId = (int) auth()->id();
        ShareTopicMember::where('topic_id', $topic->id)->where('user_id', $userId)->delete();

        return response()->json(['message' => 'Left topic successfully.']);
    }

    public function close(Request $request, ShareTopic $topic)
    {
        $uid = (int) auth()->id();
        abort_if($topic->owner_user_id !== $uid, 403, 'Only the owner can close the topic.');

        if ($topic->status !== 'closed') {
            $topic->status = 'closed';
            $topic->save();
        }

        return response()->json(['data' => ['topic_id' => $topic->id, 'status' => $topic->status]]);
    }

    public function open(Request $request, ShareTopic $topic)
    {
        $uid = (int) auth()->id();
        abort_if($topic->owner_user_id !== $uid, 403, 'Only the owner can reopen the topic.');

        if ($topic->status !== 'open') {
            $topic->status = 'open';
            $topic->save();
        }

        return response()->json(['data' => ['topic_id' => $topic->id, 'status' => $topic->status]]);
    }

    private function authorizeOwner(int $userId, ShareTopic $topic): void
    {
        abort_if($topic->owner_user_id !== $userId, 403, 'Only the owner can do this.');
    }
}
