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
        $connectedConnectionsCount = $user->connectedConnections->count();
        $pendingSentConnectionsCount = $user->pendingSentConnections->count();
        $pendingReceivedConnectionsCount = $user->pendingReceivedConnections->count();
        // return $user_ids;
        $suggestedUsersCount = $this->suggestedUsersCount();
        // return count($suggestedUsers);
        return view('home', compact('connectedConnectionsCount', 'pendingSentConnectionsCount', 'pendingReceivedConnectionsCount', 'suggestedUsersCount'));
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
