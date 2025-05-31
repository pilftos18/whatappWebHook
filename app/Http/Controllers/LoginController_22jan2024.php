<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Break_log;
use App\Models\Clients;
use App\Models\Campaign;
use App\Models\Users;
use App\Models\Chats;
use Illuminate\Support\Facades\Hash;
use App\Models\Session_Log;
use Carbon\Carbon;
use App\Models\Breaks;

class LoginController extends Controller
{   
    // public function __construct()
    // {
    //     $this->middleware('auth'); // This middleware requires authentication.
    // }
    public function indexfun(Request $request)
    {   
        $sessionData = Session::get('data');

       //print_r($sessionData);exit;
        if(session()->has('data'))
        {			    
            if (isset($sessionData) && $sessionData['userRole'] == 'super_admin' ||$sessionData['userRole'] == 'manager') {

                //print_r($sessionData);exit;
                $client_id = $sessionData['Client_id'];

                

                // $activeuser = Users::whereIn('status',[0,1])
                // ->where('role','=','user')
                // ->where('login_status','=',1)
                // ->where('client_id',$client_id)
                // ->count();

                // $Readyuser = Users::whereIn('status',[0,1])
                // ->where('role','=','user')
                // ->where('login_status',2)
                // ->where('client_id',$client_id)
                // ->count();

                // $onbreak = Users::whereIn('status',[0,1])
                // ->where('role','=','user')
                // ->where('login_status',3)
                // ->where('client_id',$client_id)
                // ->count();

                // $openchat = Chats::whereIn('is_closed',[0,1])
                // ->where('client_id',$client_id)
                // ->count();

                // $closechat = Chats::where('is_closed',2)
                // ->where('client_id',$client_id)
                // ->count();


                // $nonassign = Chats::where('is_closed',[0,1])
                // ->whereNotNull('assigned_to')
                // ->where('client_id',$client_id)
                // ->count();
                

                return view('dashboard');
                // return view('dashboard',compact('activeuser','onbreak','Readyuser','openchat','closechat','nonassign'));
            }
            else if(isset($sessionData) && $sessionData['userRole'] == 'admin' || $sessionData['userRole'] == 'supervisor' || $sessionData['userRole'] == 'mis'){

                $client_id = $sessionData['Client_id'];

                // $activeuser = Users::whereIn('status',[0,1])
                // ->where('role','=','user')
                // ->where('login_status','=',1)
                // ->where('client_id',$client_id)
                // ->count();

                // $Readyuser = Users::whereIn('status',[0,1])
                // ->where('role','=','user')
                // ->where('login_status',2)
                // ->where('client_id',$client_id)
                // ->count();

                // $onbreak = Users::whereIn('status',[0,1])
                // ->where('role','=','user')
                // ->where('login_status',3)
                // ->where('client_id',$client_id)
                // ->count();

                // $openchat = Chats::whereIn('is_closed',[0,1])
                // ->where('client_id',$client_id)
                // ->count();

                // $closechat = Chats::where('is_closed',2)
                // ->where('client_id',$client_id)
                // ->count();


                // $nonassign = Chats::where('is_closed',[0,1])
                // ->whereNotNull('assigned_to')
                // ->where('client_id',$client_id)
                // ->count();

                return view('dashboard');
                // return view('dashboard',compact('activeuser','onbreak','Readyuser','openchat','closechat','nonassign'));
            }
            else if(isset($sessionData) && $sessionData['userRole'] == 'user'){
                if(isset($sessionData['campaignID'])){
                    $breaks = Breaks::where('status', 1)->where('client_id', $sessionData['Client_id'])->where('campaign_id', $sessionData['campaignID'])->get();
                }else{
                    $breaks = Breaks::where('status', 1)->where('client_id', $sessionData['Client_id'])->get();
                }
                
            
                return view('dashboard', compact('breaks'));
            }
        }
        else{
            return view('login');
        } 
		//return view('dashboard');
    }
    
    public function signin(Request $request)
    {
        if(session()->has('data'))
        {	  
            return redirect('/dashboard');
        }
        $this->isSessionActive();  
        $sessionData    = session('data');
        
        $currentDateTime = Carbon::now('Asia/Kolkata');
        $formattedDateTime = $currentDateTime->format('Y-m-d H:i:s');
        $ip = $request->ip();
        
        $username = $request->input('username');
        $password = $request->input('passwd');
		
         $userExist = DB::table('users')
         ->leftJoin('clients', 'users.client_id', '=', 'clients.id')
         ->select('users.*', 'clients.id as Client_id', 'clients.name as clientName')
         ->whereIn('users.status', [0,1])
         //->whereIn('clients.status', [0,1])
         ->where('users.username', $username)
         ->latest()
         ->first();

        if($userExist)
        {
			//echo "asdf" exit();
            if($userExist->role == 'user' && isset($userExist->Client_id) && empty($userExist->Client_id))
            {
                return Redirect()->back()->with('error','Not Authorized');
            }
			//echo $password."<pre> "; print_r($userExist);die;
            if(!password_verify($password, $userExist->password))
            {
                return Redirect()->back()->with('error','invalid username or password!');
            }

            $userID         = $userExist->id;
            $userSessionID  = Session::getId();
            $clientid = $userExist->Client_id;
            $data = [
                'userID' => $userID,
                'userRole' => $userExist->role,
                'Client_id' => $userExist->Client_id,
                'Name' => $userExist->name,
                'Username' => $userExist->username,
                'userEmail' => $userExist->email,
                'userMobile' => $userExist->mobile,
                'userGender' => $userExist->gender,
                'userSessionID' => $userSessionID,
                'clientName' => $userExist->clientName,
                'ip_address' => $ip,
            ];

            $userToCheck = DB::table('users')
            ->where('id', $userID)
            ->where('role', 'user')
            ->where('status', 1)
            ->where('login_status', '!=', 5)
            ->first();

            if($userToCheck){
                $dummyRequest = new Request([
                    'agentid' => $userID,
                    'agentrole' => 'user', // or any appropriate role
                    'clientid' => $clientid
                    // Add other required request parameters here
                ]);
                $this->signout($dummyRequest);
            }
            

                // $getUserCampaign = Users::where('id', $userID)->get();
                // if($getUserCampaign){
                //     $campaignID = $getUserCampaign[0]->campaign_id;
                //     $campaignName = $getUserCampaign[0]->campaign_name;
                //     // Set the campaign ID in session data
                //     $data['campaignID'] = $campaignID;
                //     $data['campaignName'] = $campaignName;
                // }

                // Fetch unique campaign IDs and names
                $uniqueCampaigns = DB::table('queue_mapping')
                    ->join('campaign', function ($join) {
                        $join->on('campaign.queue', 'LIKE', DB::raw("CONCAT('%', queue_mapping.queue_id, '%')"));
                        $join->where('campaign.status', '=', 1);
                    })
                    ->where('queue_mapping.client_id', $clientid)
                    ->where('queue_mapping.user_id', $userID)
                    ->where('queue_mapping.status', 1)
                    ->distinct()
                    ->select('campaign.id', 'campaign.name')
                    ->get();
                if($uniqueCampaigns){
                    if ($uniqueCampaigns->count() === 1) {
                    $campaignID = $uniqueCampaigns[0]->id;
                    $campaignName = $uniqueCampaigns[0]->name;
                    DB::table('users')
                        ->where('id', $userID)
                        ->update([
                            'campaign_id' => $campaignID, 
                            'campaign_name' => $campaignName 
                        ]);
                    $data['campaignID'] = $campaignID;
                    $data['campaignName'] = $campaignName;
                    }
                }

                // Store unique campaign IDs and names in the session if not empty
                if (!$uniqueCampaigns->isEmpty()) {
                    $request->session()->put('campaignData', ['campaignArr' => $uniqueCampaigns]);
                }


            $request->session()->put('data', $data);
            $checkUserSession = DB::select("SELECT * FROM `session_log` WHERE user_id='$userID' AND login_status = 1 ORDER BY id DESC LIMIT 1");
            if($checkUserSession)
            {
                DB::table('session_log')
                    ->where('user_id', $userID)
                    ->update([
                        'login_status' => '2'
                    ]);
            }
            $timeout = 2; // in seconds
            sleep($timeout);
            $this->logInSession();

            DB::table('users')
                ->where('id', $userID)
                ->update([
                    'series_id' => Str::random(16),
                    'remember_token' => Hash::make(Str::random(20)),
					'login_status' => 1
                ]);

                if($userExist->role == 'user'){
                    $currentDateTime = now('Asia/Kolkata')->format('Y-m-d H:i:s');
                    $currentDate = Carbon::now('Asia/Kolkata')->toDateString();
                    $Client_id = $userExist->Client_id;
                    $breakId = 'L1';
                    $breakName = 'Login';
                    // Create a new BreakLog instance and save to the database
                    $breakLog = new Break_log(); // Create a new instance

                    // Assign attributes
                    $breakLog->client_id = $userExist->Client_id;
                    $breakLog->break_id = $breakId;
                    $breakLog->break_name = $breakName;
                    $breakLog->user_id = $userID;
                    $breakLog->start_time = $currentDateTime;

                    // Save to the database
                    $breakLog->save();

                }
            
                return redirect('/dashboard');
                // return view('dashboard')->with('campaignArr', $campaignArr);
        }else{
            return Redirect()->back()->with('error','Not Authorized!');
        }
    }

    public function signout(Request $request)
    {

        $sessionData = Session::get('data');
        if($sessionData){
            $userID = $sessionData['userID'];
            $userRole = $sessionData['userRole'];
            $Client_id = $sessionData['Client_id'];
        }else{
            $userID = $request->agentid;
            $userRole = $request->agentrole;
            $Client_id = $request->clientid;
        }
		
		
        if($userRole=='user'){
            $currentDateTime = now('Asia/Kolkata')->format('Y-m-d H:i:s');
            $currentDate = Carbon::now('Asia/Kolkata')->toDateString(); // Get today's date


            $lastBreakLog1 = Break_log::where('client_id', $Client_id)
            ->where('user_id', $userID)
            ->where('break_id', '!=', 'L1')
            ->whereDate('start_time', $currentDate) // Filter by today's date
            ->where('activity_status', 1)
            ->latest() // Get the latest record based on start_time
            ->first();

            // If a record is found, update its end_time
            if ($lastBreakLog1 !== null) {
                $lastBreakLog1->end_time = $currentDateTime;
                $lastBreakLog1->activity_status = 2;
                $time_diff = strtotime($currentDateTime) - strtotime($lastBreakLog1->start_time);
                $hours = floor($time_diff / 3600);
                $minutes = floor(($time_diff % 3600) / 60);
                $seconds = $time_diff % 60;
                $lastBreakLog1->break_time = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                $lastBreakLog1->save();
            }

            // Check if there is an existing break log record that needs to be updated
            // Fetch all records for today where break_id is 'L1'
            $breakLogsToUpdate = Break_log::where('client_id', $Client_id)
            ->where('user_id', $userID)
            ->where('break_id', 'L1')
            ->whereDate('start_time', $currentDate) // Filter by today's date
            ->where('activity_status', 1)
            ->latest('created_at')
            ->first();
            //->get(); // Retrieve all matching records

            // Check if there is an existing break log record that needs to be updated
            if ($breakLogsToUpdate !== null) {
                $breakLogsToUpdate->end_time = $currentDateTime;
                $breakLogsToUpdate->activity_status = 2;
                $time_diff = strtotime($currentDateTime) - strtotime($breakLogsToUpdate->start_time);
                $hours = floor($time_diff / 3600);
                $minutes = floor(($time_diff % 3600) / 60);
                $seconds = $time_diff % 60;
                $breakLogsToUpdate->break_time = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                $breakLogsToUpdate->save();
                }

        
        }

        $this->logOutSession();
        Session::flush();
		
		DB::table('users')
		->where('id', $userID)
		->update([
			'login_status' => 5
		]);
		
        return redirect('/login');
    }

    public function isSessionActive()
    {
        $sessionData    = session('data');
        $userSessionID  = $sessionData['userSessionID'];
        $userID         = $sessionData['userID'];
       // echo "sdfasdf<pre>"; print_r($sessionData);die;
        if(isset($sessionData['userSessionID'])) {
            $SessionTableData = DB::select("SELECT user_id, session_id, login_status FROM `session_log` WHERE session_id='$userSessionID' and user_id='$userID' AND login_status='1' ORDER BY id DESC");
            if($SessionTableData)
            {
                return redirect('/dashboard');
            }
            else{
                return redirect('/login');
            }
        }
        return true;
    }

   

    public function logInSession()
    {
        $sessionData = Session::get('data');
        $session_log = new Session_Log();
        $session_log->user_id = $sessionData['userID'];
        $session_log->session_id = $sessionData['userSessionID'];
        $session_log->login_status = 1;
        $session_log->STATUS = 1;
        $session_log->ip_address = $sessionData['ip_address'];
        $session_log->save();
    }
    public function logOutSession()
    {
        $sessionData = Session::get('data');
        Session::flush();
        return DB::table('session_log')
            ->where('login_status', 1)
            ->where('user_id', $sessionData['userID'])
            ->update([
                'login_status' => 2
            ]);
       
        
    }   

    public function getfetchClients()
    {   
       
            $clients = Clients::whereIn('status', [0, 1])->pluck('name', 'id');
            return response()->json(['clients' => $clients]);
      
    }


    public function getchangeClient(Request $request)
    {
        $client_id = $request->input('client_id');
        $client_name = $request->input('client_name');

        // Retrieve the current session data.
        $data = $request->session()->get('data');
// echo "<pre>"; print_r($data);
        //return $data;

        // Update the client_id and clientName in the session data.
        $data['Client_id'] = $client_id;
        $data['clientName'] = $client_name;

        // Put the updated data back into the session.
        $request->session()->put('data', $data);

        // You can return a response if needed.
        return response()->json(['message' => 'Client changed successfully']);
    }


    public function getstats(Request $request){

        $sessionData = Session::get('data');

        $startDate = Carbon::now()->startOfDay(); // Start of the current day (00:00:00)
        $endDate = Carbon::now()->endOfDay();     // End of the current day (23:59:59)

       //print_r($sessionData);exit;
        if(session()->has('data'))
        {			    
            if(isset($sessionData) && $sessionData['userRole'] == 'admin' || $sessionData['userRole'] == 'supervisor'){

                $client_id = $sessionData['Client_id'] ?? 0;

                $totaluser = Users::whereIn('status',[0,1])
                ->where('role','=','user')
                ->where('client_id',$client_id)
                ->count();

                $Readyuser = Users::whereIn('status',[0,1])
                ->where('role','=','user')
                ->whereIn('login_status',[2])
                ->where('client_id',$client_id)
                ->count();

                $activeuser = Users::whereIn('status',[0,1])
                ->where('role','=','user')
                ->whereIn('login_status',[0,1])
                ->where('client_id',$client_id)
                ->count();

                $onbreak = Users::whereIn('status',[0,1])
                ->where('role','=','user')
                ->where('login_status',3)
                ->where('client_id',$client_id)
                ->count();

                // $openchat = Chats::whereIn('is_closed',[0,1,3])
                // ->whereNull('assigned_to')
                // ->where('client_id',$client_id)
                // ->whereBetween('created_at', [$startDate, $endDate])
                // ->count();
                //->whereRaw("DATE(chat.created_at) BETWEEN ? AND ?", [$startDate, $endDate])

                $totalchat = Chats::where('client_id',$client_id)
                ->whereIn('is_closed',[0,1,2])
                ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$startDate, $endDate])
                ->count();

                $closechat = Chats::where('client_id',$client_id)
                ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$startDate, $endDate])
                ->where('is_closed',2)
                ->count();

                $nonassign = Chats::where('client_id',$client_id)
                ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$startDate, $endDate])
                ->WhereNull('assigned_to')
                ->whereIn('is_closed',[0,1])
                ->count();

                // $assign = Chats::whereIn('is_closed', [0, 1])
                //     ->where(function ($query) use ($client_id, $startDate, $endDate) {
                //         $query->whereNotNull('assigned_to')
                //         ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$startDate, $endDate])
                //         ->orWhere('assigned_to', 0)
                //         ->where('client_id', $client_id);
                //     })
                //     ->count();

                $assign = Chats::whereIn('is_closed', [0, 1])
                ->where(function ($query) use ($client_id, $startDate, $endDate) {
                    $query->whereNotNull('assigned_to')
                        ->orWhere('assigned_to', '!=', 0);
                })
                ->where('client_id', $client_id)
                ->whereBetween(DB::raw("DATE(created_at)"), [$startDate, $endDate])
                ->count();

                $stats = [
                    'activeuser' => $activeuser,
                    'Readyuser' => $Readyuser,
                    'onbreak' => $onbreak,
                    'totalchat' => $totalchat,
                    'nonassign' => $nonassign,
                    'closechat' => $closechat,
                    'totaluser' => $totaluser,
                    'assign' => $assign,
                ];
        
                return response()->json($stats);
            }else{
                $client_id = $sessionData['Client_id'];

                $totaluser = Users::whereIn('status',[0,1])
                ->where('role','=','user')
                ->where('client_id',$client_id)
                ->count();

                $Readyuser = Users::whereIn('status',[0,1])
                ->where('role','=','user')
                ->whereIn('login_status',[2])
                ->where('client_id',$client_id)
                ->count();

                $activeuser = Users::whereIn('status',[0,1])
                ->where('role','=','user')
                ->whereIn('login_status',[0,1])
                ->where('client_id',$client_id)
                ->count();

                $onbreak = Users::whereIn('status',[0,1])
                ->where('role','=','user')
                ->where('login_status',3)
                ->where('client_id',$client_id)
                ->count();

                // $openchat = Chats::whereIn('is_closed',[0,1,3])
                // ->whereNull('assigned_to')
                // ->where('client_id',$client_id)
                // ->whereBetween('created_at', [$startDate, $endDate])
                // ->count();

                $totalchat = Chats::where('client_id',$client_id)
                ->whereIn('is_closed',[0,1,2])
                ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$startDate, $endDate])
                ->count();

                $closechat = Chats::where('client_id',$client_id)
                ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$startDate, $endDate])
                ->where('is_closed',2)
                ->count();

                //$activechat = 

                $nonassign = Chats::where('client_id',$client_id)
                ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$startDate, $endDate])
                ->WhereNull('assigned_to')
                ->whereIn('is_closed',[0,1])
                ->count();

                $assign = Chats::whereIn('is_closed', [0, 1])
                    ->where(function ($query) use ($client_id, $startDate, $endDate) {
                        $query->whereNotNull('assigned_to')
                            ->orWhere('assigned_to', '!=', 0);
                    })
                    ->where('client_id', $client_id)
                    ->whereBetween(DB::raw("DATE(created_at)"), [$startDate, $endDate])
                    ->count();

                // $assign = Chats::whereIn('is_closed', [0, 1])
                //     ->where(function ($query) use ($client_id, $startDate, $endDate) {
                //         $query->whereNotNull('assigned_to')
                //         ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$startDate, $endDate])
                //         ->orWhere('assigned_to', 0)
                //         ->where('client_id', $client_id);
                //     })
                //     ->count();

                    $stats = [
                        'activeuser' => $activeuser,
                        'Readyuser' => $Readyuser,
                        'onbreak' => $onbreak,
                        'totalchat' => $totalchat,
                        'nonassign' => $nonassign,
                        'closechat' => $closechat,
                        'totaluser' => $totaluser,
                        'assign' => $assign,
                    ];
        
                return response()->json($stats);
            }     	
        }
        else{
            return view('login');
        } 

    }


}
