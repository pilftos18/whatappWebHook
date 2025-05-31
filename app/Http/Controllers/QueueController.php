<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MappedUsers;
use App\Models\Users;
use App\Models\Queue;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;


class QueueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        // , compact('campaigns')
        $sessionData = session('data');
        // print_r($sessionData);exit;
        $client_id = $sessionData['Client_id'];
        // $queuelist = Queue::where('client_id',$client_id)->whereIn('status', [1,0,2])->latest()->get();
        $queuelist = Queue::where('client_id',$client_id)->whereIn('is_deleted',[0,1])->whereIn('status', [1,0,2])->latest()->get();

        // return view('client.index', compact('client'));
        return view ('queue.index', compact('queuelist'));
        // $queuelist = 
        // return view ('queue.index');
    }

    public function getQueueList(Request $request)
    {
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];

        if ($request->ajax()) {
            $data = Queue::where('client_id',$client_id)->whereIn('is_deleted',[0,1])->whereIn('status', [1,0,2])->latest()->get();
            //$data = Clients::all();
            //echo"<pre>"; print_r($data);die;
            return DataTables::of($data)
                ->addColumn('action', function($row){
                    $editUrl = route('queue.edit', $row->id);
                    $deleteUrl = route('queue.delete',$row->id);
                    $btn = '<a href="'.$editUrl.'" class="btn btn-sm btn-info">Edit</a>';
                    $btn .= '<a href="'.$deleteUrl.'" class="btn btn-sm btn-info status-select" key-value = "'.$row->id.'">Delete</a>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return abort(404);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {   
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $users = Users::where('client_id', $client_id)
        ->where('role', 'user')
        ->whereIn('status', [0, 1])
        ->pluck('name', 'id');

        return view ('queue.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n------Start The queue creation------------");

        $request->validate([
            'queuename' => 'required|unique:queue',
            'VaaniCampaignqueue.user.*' => 'required',
            'status' => 'required',
        ]); 
        // $selectedUsers = $request->input('mappeduser');
        //dd($selectedUsers);exit;

        // $data = $request->all();
        // echo "<pre>";print_r($data);exit;
  
        $mappedUsersData = [];
        $queuename = $request->input('queuename');
        $status = $request->input('status');

        $queue = new Queue();
        $queue->queuename = $queuename ;
        $queue->client_id = $client_id;
        $queue->status = $status;
        $queue->created_at = now();
        $queue->created_by = 1;
        $custom_log->debug(__LINE__."\n\n\n---- queue are save in queue model --------");
        $queue->save();
        $queue_id = $queue->id;
        $selectedUserIds = $request->input('VaaniCampaignqueue.user');
        if($selectedUserIds[0] =='All'){
            $selectedUsers =  Users::where('client_id', $client_id)
            ->where('role', 'user')
            ->whereIn('status', [0, 1])
            ->pluck('name', 'id');
        }else{
            $selectedUsers = Users::whereIn('id', $selectedUserIds)->pluck('name', 'id');
        }

        // echo "<pre>";print_r($selectedUsers);exit;
        $ip = $request->ip();

        foreach ($selectedUsers as $userId => $userName) {
            $mappedUsersData[] = [
                'client_id' => $client_id,
                'user_id' => $userId,
                'mapped_user' => $userName, // Use the user name here
                'queue_id' => $queue_id,
                'status' => $status,
                'created_at' => now(),
                'created_by' => 1,
            ];
        }
        $custom_log->debug(__LINE__."\n\n\n---- mapped users are in array to insert --------");
        MappedUsers::insert($mappedUsersData);
        $custom_log->debug(__LINE__."\n\n\n----Users are mapped as per queue-----");
        $custom_log->debug(__LINE__."\n\n\n----queue create by --------".$ip);
        return redirect()->route('queue.index')->with('success', 'queue, user map successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {       
        $queue = Queue::find($id);
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];

        $mapped_user = MappedUsers::where('client_id', $client_id)
        ->where('queue_id', $id)
        ->whereIn('status', [0,1,2])
        ->pluck('user_id')
        ->all();
        $users = Users::select('users.id', 'users.name')
        ->leftJoin('queue_mapping', function($join) use ($client_id, $id) {
            $join->on('users.client_id', '=', 'queue_mapping.client_id')
                ->on('users.id', '=', 'queue_mapping.user_id')
                ->where('queue_mapping.queue_id', '=', $id);
        })
        ->where('users.client_id', $client_id)
        ->where('users.role', 'user')
        ->whereIn('users.status', [0, 1])
        ->get();

        return view ('queue.edit', compact('queue','users','mapped_user'));
        // return view ('queue.edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n------queue update------------");
        $request->validate([
            'queuename' => [
                'required',
                Rule::unique('queue')->ignore($id),
            ],
            'VaaniCampaignqueue.user.*' => 'required',
            'status' => 'required',
        ], [
            'queuename.required' => 'The queue name is required.',
            'VaaniCampaignqueue.user.*.required' => 'Users is required.',
        ]);
        $custom_log->debug(__LINE__."\n\n\n---- validations are proper --------");
        $ip = $request->ip();
        // Find the queue to update
        $queue = Queue::find($id);
        $mappedusers = MappedUsers::where('queue_id', $id)->first();

        $status = $request->input('status');
        $queue->client_id = $client_id;
        $queue->queuename =  $request->input('queuename');
        $queue->status = $request->input('status');
        $queue->updated_at = now();
        $queue->updated_by = 1;
        $custom_log->debug(__LINE__."\n\n\n---- queue are save in queue model --------");
        $queue->save();

        MappedUsers::where('queue_id', $id)->delete();

        $selectedUserIds = $request->input('VaaniCampaignqueue.user');
        if($selectedUserIds[0] == 'All'){
            $selectedUsers =  Users::where('client_id', $client_id)
            ->where('role', 'user')
            ->whereIn('status', [0, 1])
            ->pluck('name', 'id');
        }else{
            $selectedUsers = Users::whereIn('id', $selectedUserIds)->pluck('name', 'id');
        }

        $mappedUsersData = [];

        foreach ($selectedUsers as $userId => $userName) {
            $mappedUsersData[] = [
                'client_id' => $client_id,
                'user_id' => $userId,
                'mapped_user' => $userName, // Use the user name here
                'queue_id' => $id,
                'status' => $status,
                'updated_at' => now(),
                'updated_by' => 1,
            ];
        }
        $custom_log->debug(__LINE__."\n\n\n---- mapped users are in array to insert --------");
        MappedUsers::insert($mappedUsersData);
        $custom_log->debug(__LINE__."\n\n\n----Users are mapped as per queue-----");
        $custom_log->debug(__LINE__."\n\n\n----queue create by --------".$ip);
        return redirect()->route('queue.index')->with('success', 'queue, user map updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request,$id)
    {
        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n----delete queue -----");
        $queue = Queue::find($id);
        $ip = $request->ip();
        if ($queue) {
            $queue->status = 2;
            $queue->is_deleted = 2;
            $queue->updated_at = now();
            $custom_log->debug(__LINE__."\n\n\n-----set queue status 2 by-----".$ip);
            $queue->save();

            MappedUsers::where('queue_id', $id)->update(['status' => 2]);
            
            $custom_log->debug(__LINE__."\n\n\n-----set MappedUsers status 2 by-----".$ip);
            $custom_log->debug(__LINE__."\n\n\n-----delete queue by-----".$ip);

            return redirect('queue')->with('success', 'queue and related records deleted successfully.');
        } else {
            return redirect('queue')->with('error', 'queue not found.');
        }
    }

    public function delete(Request $request,$id)
    {
        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n----delete queue -----");
        $queue = Queue::find($id);
        $ip = $request->ip();
        if ($queue) {
            $queue->status = 2;
            $queue->is_deleted = 2;
            $queue->updated_at = now();
            $custom_log->debug(__LINE__."\n\n\n-----set queue status 2 by-----".$ip);
            $queue->save();

            MappedUsers::where('queue_id', $id)->update(['status' => 2]);
            
            $custom_log->debug(__LINE__."\n\n\n-----set MappedUsers status 2 by-----".$ip);
            $custom_log->debug(__LINE__."\n\n\n-----delete queue by-----".$ip);

            return redirect('queue')->with('success', 'queue and related records deleted successfully.');
        } else {
            return redirect('queue')->with('error', 'queue not found.');
        }
    }
}
