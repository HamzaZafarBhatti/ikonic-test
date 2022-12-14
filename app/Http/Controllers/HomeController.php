<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

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
        $connectedUsersCount = $this->connectedUsersCount();
        $pendingSentConnectionsCount = $user->pendingSentConnections->count();
        $pendingReceivedConnectionsCount = $user->pendingReceivedConnections->count();
        // return $user_ids;
        $suggestedUsersCount = $this->suggestedUsersCount();
        // return count($suggestedUsers);
        return view('home', compact('connectedUsersCount', 'pendingSentConnectionsCount', 'pendingReceivedConnectionsCount', 'suggestedUsersCount'));
    }

    public function getSuggestions(Request $request)
    {
        $limit = $request->limit;
        $skip = $request->skip;
        $suggestedUsers = $this->suggestedUsers($limit, $skip);
        return json_encode([
            'count' => count($suggestedUsers),
            'data' => view('components.suggestion', ['suggestions' => $suggestedUsers])->render()
        ]);
    }

    public function getConnections(Request $request)
    {
        $limit = $request->limit;
        $skip = $request->skip;
        $connectedUsers = $this->connectedUsers(auth()->user()->id, $limit, $skip);
        foreach($connectedUsers as $user) {
            $connectionConnectedUsersCount = $this->connectionConnectedUsersCount($user->id, auth()->user()->id);
            $user->commonUsers = $connectionConnectedUsersCount;
        }
        return json_encode([
            'count' => count($connectedUsers),
            'data' => view('components.connection', ['connected' => $connectedUsers])->render()
        ]);
    }

    public function getRequest(Request $request)
    {
        $limit = $request->limit;
        $skip = $request->skip;
        $mode = $request->mode;
        $user = auth()->user();
        switch($mode) {
            case 'sent':
                $pendingSentConnections = $user->pendingSentConnections;
                return json_encode([
                    'count' => count($pendingSentConnections),
                    'data' => view('components.request', ['requests' => $pendingSentConnections, 'mode' => $mode])->render()
                ]);
                break;
            case 'received':
                $pendingReceivedConnections = $user->pendingReceivedConnections;
                return json_encode([
                    'count' => count($pendingReceivedConnections),
                    'data' => view('components.request', ['requests' => $pendingReceivedConnections, 'mode' => $mode])->render()
                ]);
                break;
            default:
                return view('components.request', ['requests' => []])->render();
        }
    }

    public function getConnectionsInCommon(Request $request)
    {
        return $request;
    }

    public function sendConnectionRequest(Request $request)
    {
        
        DB::table('user_connections')->insert([
            'user_id' => auth()->user()->id,
            'connection_id' => $request->connectionId,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        return response()->json([
            'status' => 1,
            'message' => 'Connection Request sent!'
        ]);
    }

    public function deleteConnectionRequest(Request $request)
    {
        
        $result = DB::table('user_connections')->where('user_id', auth()->user()->id)->where('connection_id', $request->connectionId)->delete();
        if($result) {
            return response()->json([
                'status' => 1,
                'message' => 'Connection Request withdrawed!'
            ]);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Error: Something went wrong!'
            ]);
        }
    }

    public function removeConnectionRequest(Request $request)
    {
        $userId = auth()->user()->id;
        $connectionId = $request->connectionId;
        $result = DB::table('user_connections')->where(function($q) use ($userId, $connectionId) {
            $q->where('user_id', $userId)->where('connection_id', $connectionId);
        })->orWhere(function($q) use ($userId, $connectionId) {
            $q->where('user_id', $connectionId)->where('connection_id', $userId);
        })->delete();
        if($result) {
            return response()->json([
                'status' => 1,
                'message' => 'Connection removed!'
            ]);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Error: Something went wrong!'
            ]);
        }
    }

    public function acceptConnectionRequest(Request $request)
    {
        
        $result = DB::table('user_connections')->where('user_id', $request->connectionId)->where('connection_id', auth()->user()->id)->update(['status' => User::CONNECTED_STATUS]);
        if($result) {
            return response()->json([
                'status' => 1,
                'message' => 'Connection Request accepted!'
            ]);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Error: Something went wrong!'
            ]);
        }
    }

    private function suggestedUsers($limit, $skip)
    {
        $userId = auth()->user()->id;
        $sentRequestUserIds = User::join('user_connections', 'users.id', '=', 'user_connections.user_id')
                    ->where('users.id', $userId)->where('user_connections.status', 'pending')->pluck('user_connections.connection_id');
        $receivedRequestUserIds = DB::table('user_connections')->where('connection_id', $userId)->where('user_connections.status', 'pending')->pluck('user_id');
        $userIds = Arr::collapse([$sentRequestUserIds, $receivedRequestUserIds]);
        return User::select('id', 'name', 'email')->where('id', '!=', $userId)->whereNotIn('id', $userIds)->limit($limit)->offset($skip)->get();
    }
    private function suggestedUsersCount()
    {
        $userId = auth()->user()->id;
        $sentRequestUserIds = User::join('user_connections', 'users.id', '=', 'user_connections.user_id')
                    ->where('users.id', $userId)->where('user_connections.status', 'pending')->pluck('user_connections.connection_id');
        $receivedRequestUserIds = DB::table('user_connections')->where('connection_id', $userId)->where('user_connections.status', 'pending')->pluck('user_id');
        $userIds = Arr::collapse([$sentRequestUserIds, $receivedRequestUserIds]);
        return User::where('id', '!=', $userId)->whereNotIn('id', $userIds)->count();
    }
    private function connectedUsers($userId, $limit = null, $skip = null)
    {
        $forwardConnections = DB::table('user_connections')->where('user_id', $userId)->whereStatus(User::CONNECTED_STATUS)->pluck('connection_id');
        $reverseConnections = DB::table('user_connections')->where('connection_id', $userId)->whereStatus(User::CONNECTED_STATUS)->pluck('user_id');
        $userIds = Arr::collapse([$forwardConnections, $reverseConnections]);
        return User::select('id', 'name', 'email')->where('id', '!=', $userId)->whereIn('id', $userIds)->limit($limit)->offset($skip)->get();
    }
    private function connectionConnectedUsersCount($userId, $connectionId)
    {
        $forwardConnections = DB::table('user_connections')->where('user_id', $userId)->whereStatus(User::CONNECTED_STATUS)->pluck('connection_id');
        $reverseConnections = DB::table('user_connections')->where('connection_id', $userId)->whereStatus(User::CONNECTED_STATUS)->pluck('user_id');
        $userIds = Arr::collapse([$forwardConnections, $reverseConnections]);
        $users = User::select('id', 'name', 'email')->where('id', '!=', $userId)->where('id', '!=', $connectionId)->whereIn('id', $userIds);
        return $users->count();
    }
    public function connectedUsersCount()
    {
        $userId = auth()->user()->id;
        return DB::table('user_connections')->whereStatus(User::CONNECTED_STATUS)->where(function($q) use ($userId) {
            $q->where('user_id', $userId)->orWhere('connection_id', $userId);
        })->count();
    }
}
