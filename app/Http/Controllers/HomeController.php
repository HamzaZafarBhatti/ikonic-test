<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();
        $connectedConnectionsCount = $user->connectedConnections->count();
        $pendingSentConnectionsCount = $user->pendingSentConnections->count();
        $pendingReceivedConnectionsCount = $user->pendingReceivedConnections->count();
        $rejectedConnectionsCount = $user->rejectedConnections->count();
        // return $user_ids;
        $suggestedUsersCount = $this->suggestedUsersCount();
        // return count($suggestedUsers);
        return view('home', compact('connectedConnectionsCount', 'pendingSentConnectionsCount', 'pendingReceivedConnectionsCount', 'rejectedConnectionsCount', 'suggestedUsersCount'));
    }

    public function getSuggestedConnections(Request $request)
    {
        $limit = $request->limit;
        $skip = $request->skip;
        $suggestedUsers = $this->suggestedUsers($limit, $skip);
        return json_encode([
            'count' => count($suggestedUsers),
            'data' => view('components.suggestion', ['suggestions' => $suggestedUsers])->render()
        ]);
    }

    public function getSentRequest(Request $request)
    {
        $limit = $request->limit;
        $skip = $request->skip;
        $mode = $request->mode;
        $user = auth()->user();
        switch($mode) {
            case 'sent':
                $sentConnections = $user->pendingSentConnections;
                return json_encode([
                    'count' => count($sentConnections),
                    'data' => view('components.request', ['requests' => $sentConnections, 'mode' => $mode])->render()
                ]);
                // return view('components.request', ['requests' => $sentConnections, 'mode' => $mode])->render();
                break;
            case 'received':
                $rejectedConnections = $user->rejectedConnections;
                return $rejectedConnections;
                break;
            default:
                return view('components.request', ['requests' => []])->render();
        }
    }

    public function sendConnectionRequest(Request $request)
    {
        
        DB::table('user_connections')->insert([
            'user_id' => auth()->user()->id,
            'connection_id' => $request->suggestionId,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        return response()->json([
            'status' => 1,
            'message' => 'Connection Request sent!'
        ]);
    }

    private function suggestedUsers($limit, $skip)
    {
        $user = auth()->user();
        $sentRequestUserIds = User::join('user_connections', 'users.id', '=', 'user_connections.user_id')
                    ->where('users.id', $user->id)->where('user_connections.status', 'pending')->pluck('user_connections.connection_id');
        $receivedRequestUserIds = DB::table('user_connections')->where('connection_id', $user->id)->where('user_connections.status', 'pending')->pluck('user_id');
        // return $sentRequestUserIds;
        $userIds = Arr::collapse([$sentRequestUserIds, $receivedRequestUserIds]);
        return User::select('id', 'name', 'email')->where('id', '!=', $user->id)->whereNotIn('id', $userIds)->limit($limit)->offset($skip)->get();
    }
    private function suggestedUsersCount()
    {
        $user = auth()->user();
        $sentRequestUserIds = User::join('user_connections', 'users.id', '=', 'user_connections.user_id')
                    ->where('users.id', $user->id)->where('user_connections.status', 'pending')->pluck('user_connections.connection_id');
        $receivedRequestUserIds = DB::table('user_connections')->where('connection_id', $user->id)->where('user_connections.status', 'pending')->pluck('user_id');
        // return $sentRequestUserIds;
        $userIds = Arr::collapse([$sentRequestUserIds, $receivedRequestUserIds]);
        return User::where('id', '!=', $user->id)->whereNotIn('id', $userIds)->count();
    }
}
