<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Camping;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    
    // Kemping összes értékelésének lekérése (fő kommentek + válaszok)
     
    public function index($campingId)
    {
        $camping = Camping::findOrFail($campingId);

        // Csak a fő kommenteket kérjük le (amiknek nincs parent_id-ja)
        $comments = Comment::where('camping_id', $campingId)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'camping' => $camping->camping_name,
            'average_rating' => $camping->getAverageRating(),
            'reviews_count' => $camping->getReviewsCount(),
            'comments' => $comments
        ]);
    }

    
    //Új értékelés létrehozása (csak vendég, aki foglalt már)
     
    public function store(Request $request, $campingId)
    {
        $user = Auth::user();
        $camping = Camping::findOrFail($campingId);

        // Ellenőrizzük, hogy a user-nek van-e befejezett foglalása ennél a kempingnél
        $hasBooking = Booking::where('camping_id', $campingId)
            ->where('user_id', $user->id)
            ->whereIn('status', ['checked_in', 'completed'])
            ->exists();

        if (!$hasBooking) {
            return response()->json([
                'message' => 'Csak azok értékelhetnek, akik már foglaltak ennél a kempingnél.'
            ], 403);
        }

        // Ellenőrizzük, hogy már értékelt-e
        $existingComment = Comment::where('camping_id', $campingId)
            ->where('user_id', $user->id)
            ->whereNull('parent_id')
            ->exists();

        if ($existingComment) {
            return response()->json([
                'message' => 'Már értékelted ezt a kempinget.'
            ], 422);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'camping_id' => $campingId,
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'upload_date' => now(),
        ]);

        $comment->load('user');

        return response()->json([
            'message' => 'Értékelés sikeresen létrehozva!',
            'comment' => $comment
        ], 201);
    }


     //Válasz hozzáadása egy értékeléshez (csak a kemping tulajdonosa)

    public function reply(Request $request, $commentId)
    {
        $user = Auth::user();
        $parentComment = Comment::findOrFail($commentId);

        // Ellenőrizzük, hogy ez egy fő komment-e (nem válasz)
        if ($parentComment->parent_id !== null) {
            return response()->json([
                'message' => 'Csak fő értékelésekre lehet válaszolni.'
            ], 422);
        }

        // Ellenőrizzük, hogy a user a kemping tulajdonosa-e
        $camping = Camping::findOrFail($parentComment->camping_id);
        if ($camping->user_id !== $user->id) {
            return response()->json([
                'message' => 'Csak a kemping tulajdonosa válaszolhat az értékelésekre.'
            ], 403);
        }

        // Ellenőrizzük, hogy már válaszolt-e erre a kommentre
        $existingReply = Comment::where('parent_id', $commentId)
            ->where('user_id', $user->id)
            ->exists();

        if ($existingReply) {
            return response()->json([
                'message' => 'Már válaszoltál erre az értékelésre.'
            ], 422);
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $reply = Comment::create([
            'camping_id' => $parentComment->camping_id,
            'user_id' => $user->id,
            'parent_id' => $commentId,
            'comment' => $validated['comment'],
            'upload_date' => now(),
        ]);

        $reply->load('user');

        return response()->json([
            'message' => 'Válasz sikeresen hozzáadva!',
            'reply' => $reply
        ], 201);
    }

    
     //Saját értékelés szerkesztése
   
    public function update(Request $request, $commentId)
    {
        $user = Auth::user();
        $comment = Comment::findOrFail($commentId);

        // Ellenőrizzük, hogy a user-é a komment
        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'Nincs jogosultságod szerkeszteni ezt az értékelést.'
            ], 403);
        }

        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|string|max:1000',
        ]);

        // Ha főkomment, akkor a ratinget is lehet módosítani
        if ($comment->parent_id === null && isset($validated['rating'])) {
            $comment->rating = $validated['rating'];
        }

        if (isset($validated['comment'])) {
            $comment->comment = $validated['comment'];
        }

        $comment->save();
        $comment->load('user');

        return response()->json([
            'message' => 'Értékelés sikeresen frissítve!',
            'comment' => $comment
        ]);
    }

    
     //Saját értékelés törlése
     
    public function destroy($commentId)
    {
        $user = Auth::user();
        $comment = Comment::findOrFail($commentId);

        // Ellenőrizzük, hogy a user-é a komment VAGY a kemping tulajdonosa
        $camping = Camping::findOrFail($comment->camping_id);
        
        if ($comment->user_id !== $user->id && $camping->user_id !== $user->id) {
            return response()->json([
                'message' => 'Nincs jogosultságod törölni ezt az értékelést.'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Értékelés sikeresen törölve!'
        ]);
    }
}
