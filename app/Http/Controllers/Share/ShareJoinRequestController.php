<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use App\Models\ShareJoinRequest;
use App\Models\ShareTopic;
use App\Models\ShareTopicMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ShareJoinRequestController extends Controller
{
    /**
     * POST /api/share/topics/{topic}/join-requests
     * Create a join request for the authenticated user.
     */
    public function store(Request $request, ShareTopic $topic)
    {
        $userId = (int) $request->user()->id;

        // Already a member?
        $isMember = ShareTopicMember::where('topic_id', $topic->id)
            ->where('user_id', $userId)->exists();
        if ($isMember) {
            return response()->json(['data' => ['status' => 'already_member']], 200);
        }

        // If a pending request already exists, return it
        $existing = ShareJoinRequest::where('topic_id', $topic->id)
            ->where('requester_user_id', $userId)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'data' => ['request_id' => $existing->id, 'status' => 'pending'],
            ], 200);
        }

        $payload = $request->validate([
            'message' => 'nullable|string|max:500',
        ]);

        $jr = ShareJoinRequest::create([
            'topic_id'          => $topic->id,
            'requester_user_id' => $userId,
            'status'            => 'pending',
            'invite_token'      => $topic->invite_token,
            'message'           => $payload['message'] ?? null,
        ]);

        return response()->json([
            'data' => ['request_id' => $jr->id, 'status' => 'pending'],
        ], 201);
    }

    /**
     * GET /api/share/topics/{topic}/join-requests
     * Owner-only list (all statuses; UI can filter).
     */
    public function index(Request $request, ShareTopic $topic)
    {
        $this->authorizeOwner($request->user()->id, $topic);

        $items = ShareJoinRequest::with('requester:id,name,email')
            ->where('topic_id', $topic->id)
            ->orderByDesc('id')
            ->get([
                'id','topic_id','requester_user_id','status','message',
                'created_at','decided_by_user_id','decided_at'
            ]);

        return response()->json(['data' => ['requests' => $items]]);
    }

    /**
     * POST /api/share/topics/{topic}/join-requests/{joinRequest}/approve
     */
    public function approve(Request $request, ShareTopic $topic, ShareJoinRequest $joinRequest)
    {
        $this->authorizeOwner($request->user()->id, $topic);
        abort_if($joinRequest->topic_id !== $topic->id, 404, 'Request not found.');

        // Idempotency
        if ($joinRequest->status === 'approved') {
            // Ensure membership exists even if status already approved
            $this->ensureMember($topic->id, $joinRequest->requester_user_id, $joinRequest->requester?->name);
            return response()->json(['data' => ['status' => 'approved']], 200);
        }
        if ($joinRequest->status === 'denied') {
            return response()->json(['data' => ['status' => 'denied']], 200);
        }

        // If another APPROVED row already exists for (topic,user), ensure membership,
        // delete this redundant row and return success (avoid unique index conflict).
        $otherApproved = ShareJoinRequest::where('topic_id', $topic->id)
            ->where('requester_user_id', $joinRequest->requester_user_id)
            ->where('status', 'approved')
            ->where('id', '!=', $joinRequest->id)
            ->exists();

        if ($otherApproved) {
            $this->ensureMember($topic->id, $joinRequest->requester_user_id, $joinRequest->requester?->name);
            $joinRequest->delete();
            return response()->json(['data' => ['status' => 'approved']], 200);
        }

        try {
            DB::transaction(function () use ($request, $topic, $joinRequest) {
                // Ensure requester is a member (idempotent)
                $this->ensureMember($topic->id, $joinRequest->requester_user_id, $joinRequest->requester?->name);

                // Deny any *other* pending requests for the same user/topic
                ShareJoinRequest::where('topic_id', $topic->id)
                    ->where('requester_user_id', $joinRequest->requester_user_id)
                    ->where('status', 'pending')
                    ->where('id', '!=', $joinRequest->id)
                    ->update([
                        'status'             => 'denied',
                        'decided_by_user_id' => $request->user()->id,
                        'decided_at'         => now(),
                        'updated_at'         => now(),
                    ]);

                // Approve THIS request
                $joinRequest->status             = 'approved';
                $joinRequest->decided_by_user_id = $request->user()->id;
                $joinRequest->decided_at         = now();
                $joinRequest->save();
            });
        } catch (QueryException $qe) {
            // Handle unique-index race on (topic_id, requester_user_id, 'approved')
            if ($this->isDuplicateKey($qe)) {
                // Another row got approved concurrently; ensure membership and drop this one if needed.
                $this->ensureMember($topic->id, $joinRequest->requester_user_id, $joinRequest->requester?->name);
                $joinRequest->refresh();
                if ($joinRequest->status !== 'approved') {
                    $joinRequest->delete();
                }
                return response()->json(['data' => ['status' => 'approved']], 200);
            }
            throw $qe;
        }

        return response()->json(['data' => ['status' => 'approved']]);
    }

    /**
     * POST /api/share/topics/{topic}/join-requests/{joinRequest}/deny
     */
    public function deny(Request $request, ShareTopic $topic, ShareJoinRequest $joinRequest)
    {
        $this->authorizeOwner($request->user()->id, $topic);
        abort_if($joinRequest->topic_id !== $topic->id, 404, 'Request not found.');

        // Idempotency
        if ($joinRequest->status === 'denied') {
            return response()->json(['data' => ['status' => 'denied']], 200);
        }
        if ($joinRequest->status === 'approved') {
            return response()->json(['data' => ['status' => 'approved']], 200);
        }

        // If there is *already* another DENIED row, just delete this one to avoid conflict.
        $otherDenied = ShareJoinRequest::where('topic_id', $topic->id)
            ->where('requester_user_id', $joinRequest->requester_user_id)
            ->where('status', 'denied')
            ->where('id', '!=', $joinRequest->id)
            ->exists();

        if ($otherDenied) {
            $joinRequest->delete();
            return response()->json(['data' => ['status' => 'denied']], 200);
        }

        try {
            $joinRequest->status             = 'denied';
            $joinRequest->decided_by_user_id = $request->user()->id;
            $joinRequest->decided_at         = now();
            $joinRequest->save();
        } catch (QueryException $qe) {
            // Race: another process created a DENIED row right now
            if ($this->isDuplicateKey($qe)) {
                $joinRequest->delete(); // make it non-conflicting
                return response()->json(['data' => ['status' => 'denied']], 200);
            }
            throw $qe;
        }

        return response()->json(['data' => ['status' => 'denied']]);
    }

    private function authorizeOwner(int $userId, ShareTopic $topic): void
    {
        abort_if($topic->owner_user_id !== $userId, 403, 'Only the owner can do this.');
    }

    /**
     * Ensure the (topic_id, user_id) appears in share_topic_members.
     */
    private function ensureMember(int $topicId, int $userId, ?string $displayName = null): void
    {
        $exists = ShareTopicMember::where('topic_id', $topicId)
            ->where('user_id', $userId)
            ->exists();

        if (!$exists) {
            ShareTopicMember::create([
                'topic_id'     => $topicId,
                'user_id'      => $userId,
                'display_name' => $displayName ?: 'Member',
                'role'         => 'member',
                'joined_at'    => now(),
            ]);
        }
    }

    /**
     * MySQL duplicate-key helpers:
     * - SQLSTATE code is 23000
     * - Driver error code for duplicate is 1062
     */
    private function isDuplicateKey(QueryException $qe): bool
    {
        $errorInfo = $qe->errorInfo ?? [];
        if (isset($errorInfo[1]) && (int)$errorInfo[1] === 1062) {
            return true;
        }
        return (string)$qe->getCode() === '23000';
    }
}
