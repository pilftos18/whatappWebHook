<?php

namespace App\Http\Controllers;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Chats;
use App\Models\Campaign;
use App\Models\Users;
use App\Models\Chat_log;
use App\Models\Break_log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Database\Query\Builder;
use App\Models\Rcdetails;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Buttons\Button;
use Yajra\DataTables\Buttons\DatatableButton;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Models\LoginStatus;

class DashboardController extends Controller
{
    
    public function getadminDashboardList(Request $request)
    {
        if ($request->ajax()) {
            $sessionData = session('data');
            if (isset($sessionData) && $sessionData['userRole'] == 'admin' || $sessionData['userRole'] == 'supervisor' || $sessionData['userRole'] == 'mis') {
                $client_id = $sessionData['Client_id'];
                $startDate = Carbon::now()->startOfDay(); // Start of the current day (00:00:00)
                $endDate = Carbon::now()->endOfDay(); 

                $data = Users::where('users.client_id', $client_id)
                ->leftJoin('queue_mapping','queue_mapping.user_id','=','users.id')
                ->leftJoin('queue','queue.id','=','queue_mapping.queue_id')
                ->leftJoin('campaign','campaign.id','=','queue.campaign_id')
                ->whereIn('users.status', [0, 1])
                ->where('users.role', 'user')
                ->where('queue_mapping.status','=',1)
                ->select(
                    'users.id',
                    'users.name',
                    'campaign.name as campaignname',
                    'users.login_status',
                    'users.created_at',
                    DB::raw('(SELECT COUNT(*) FROM chats WHERE chats.assigned_to = users.id AND DATE(chats.created_at) BETWEEN "'.$startDate.'" AND "'.$endDate.'") as chat_count')
                )
                ->get();

                    return DataTables::of($data)
                    ->addColumn('formatted_created_at', function ($user) {
                        return $user->created_at->format('Y-m-d H:i:s'); // Adjust the format according to your requirement
                    })
                    ->make(true);
        
            }
        }
        
        return abort(404);
        
    }

    public function getManagerDashboardList(Request $request){
        if ($request->ajax()) {
            $sessionData = session('data');
           
                    $client_id = $sessionData['Client_id'];
                
                    $client_id = $sessionData['Client_id'];
                    $startDate = Carbon::now()->startOfDay(); // Start of the current day (00:00:00)
                    $endDate = Carbon::now()->endOfDay(); 
                
                    $data = Users::select(
                        'users.id', 
                        'users.name', 
                        'users.username', 
                        'users.login_status', 
                        'users.created_at',
                        DB::raw('(SELECT COUNT(*) FROM chats WHERE chats.assigned_to = users.id AND DATE(chats.created_at) BETWEEN "'.$startDate.'" AND "'.$endDate.'") as chat_count')
                    )
                    ->where('client_id', $client_id)
                    ->whereIn('users.status', [0, 1])
                    ->where('users.role', '=', 'user')
                    ->get();

                    return DataTables::of($data)
                    ->addColumn('formatted_created_at', function ($user) {
                        return $user->created_at->format('Y-m-d H:i:s'); // Adjust the format according to your requirement
                    })
                    ->make(true);
        }
        
        return abort(404);
    }

    public function getSuperadminDashboardList(Request $request)
    {
        if ($request->ajax()) {
            $sessionData = session('data');
            if (isset($sessionData) && $sessionData['userRole'] == 'super_admin') {
                    $client_id = $sessionData['Client_id'];
                
                    // $data = Users::where('client_id',$client_id)
                    // ->leftjoin('chats','chat.assigned_to','=','users.id')
                    // ->whereIn('status',[0,1])
                    // ->where('role','=','user')
                    // ->select('id', 'name', 'username', 'login_status','created_at')
                    // ->get();

                    $startDate = Carbon::now()->startOfDay(); // Start of the current day (00:00:00)
                    $endDate = Carbon::now()->endOfDay(); 
                
                    $data = Users::select(
                        'users.id', 
                        'users.name', 
                        'users.username', 
                        'users.login_status', 
                        'users.created_at',
                        DB::raw('(SELECT COUNT(*) FROM chats WHERE chats.assigned_to = users.id AND DATE(chats.created_at) BETWEEN "'.$startDate.'" AND "'.$endDate.'") as chat_count')
                    )
                    ->where('client_id', $client_id)
                    ->whereIn('users.status', [0, 1])
                    ->where('users.role', '=', 'user')
                    ->get();


                    return DataTables::of($data)
                    ->addColumn('formatted_created_at', function ($user) {
                        return $user->created_at->format('Y-m-d H:i:s'); // Adjust the format according to your requirement
                    })
                    ->make(true);
        
            }
        }
        
        return abort(404);
        
    }

    public function getstatsmanager(Request $request){

}



	public function updateLoginStatus($user_id, $client_id, $profile = 'user')
	{
		if(!empty($user_id))
		{
			// Calculate the datetime
			$currentDatetime = Carbon::now();

			// Data to be inserted or updated
			$data = [
				'client_id' => $client_id,
				'user_id' => $user_id,
				'profile' => $profile, // Replace with the desired profile
				'updated_at' => $currentDatetime,
			];

			// Perform insert or update
			LoginStatus::updateOrInsert(
				['user_id' => $user_id, 'client_id' => $client_id],
				$data
			);
		}
		return 0;
	}
    public function fetch_dashboard_data(Request $request)
    {
		// Check if session data exists
        $sessionData = Session::get('data');

        if (!$sessionData) {
            // Session data does not exist, redirect to login page
            return redirect('/login');
        }
		
        $today = Carbon::now();
        $sessionData = Session::get('data');
        if(session()->has('data'))
        {
            $userID = $sessionData['userID'];
            $clientid = $sessionData['Client_id'];
            $userRole = $sessionData['userRole'];

            // update end time for every 5 seconds login and break where activity_statu is 1 code by mahesh start

                if($userRole=='user'){
                    $currentDateTime = now('Asia/Kolkata')->format('Y-m-d H:i:s');
                    $currentDate = Carbon::now('Asia/Kolkata')->toDateString(); // Get today's date
        
        
                    $lastBreakLog1 = Break_log::where('client_id', $clientid)
                    ->where('user_id', $userID)
                    ->where('break_id', '!=', 'L1')
                    ->whereDate('start_time', $currentDate) // Filter by today's date
                    ->where('activity_status', 1)
                    ->latest() // Get the latest record based on start_time
                    ->first();
        
                    // // If a record is found, update its end_time
                    if ($lastBreakLog1 !== null) {
                        $lastBreakLog1->end_time = $currentDateTime;
                        // $time_diff = strtotime($currentDateTime) - strtotime($lastBreakLog1->start_time);
                        // $hours = floor($time_diff / 3600);
                        // $minutes = floor(($time_diff % 3600) / 60);
                        // $seconds = $time_diff % 60;
                        // $lastBreakLog1->break_time = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                        $lastBreakLog1->save();
                    }
        
                    // // Check if there is an existing break log record that needs to be updated
                    // // Fetch all records for today where break_id is 'L1'
                    $breakLogsToUpdate = Break_log::where('client_id', $clientid)
                    ->where('user_id', $userID)
                    ->where('break_id', 'L1')
                    ->whereDate('start_time', $currentDate) // Filter by today's date
                    ->where('activity_status', 1)
                    ->latest('created_at')
                    ->first();
                    // //->get(); // Retrieve all matching records
        
                    // // Check if there is an existing break log record that needs to be updated
                    if ($breakLogsToUpdate !== null) {
                        $breakLogsToUpdate->end_time = $currentDateTime;
                        // $time_diff = strtotime($currentDateTime) - strtotime($breakLogsToUpdate->start_time);
                        // $hours = floor($time_diff / 3600);
                        // $minutes = floor(($time_diff % 3600) / 60);
                        // $seconds = $time_diff % 60;
                        // $breakLogsToUpdate->break_time = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                        $breakLogsToUpdate->save();
                        }
        
                
                }
            // update end time for every 5 seconds login and break where activity_statu is 1 code by mahesh end
			
			$this->updateLoginStatus($userID, $clientid, 'user'); // updated by jaiprakash Chauhan
			
            $campMsg='';
            $campaignID='';
            if(isset($sessionData['campaignID'])){
                $campaignID = $sessionData['campaignID'];
            }else{
                // $getAllCampaign = Campaign::where('client_id', $clientid)->get();
                $getAllCampaign = DB::table('queue_mapping')
                                ->join('campaign', function ($join) {
                                    $join->on('campaign.queue', 'LIKE', DB::raw("CONCAT('%', queue_mapping.queue_id, '%')"));
                                    $join->where('campaign.status', '=', 1);
                                })
                                ->where('queue_mapping.client_id', $clientid)
                                ->where('queue_mapping.user_id', $userID)
                                ->where('queue_mapping.status', 1)
                                ->select('campaign.*')
                                ->distinct() // Add distinct() method here
                                ->get();
                if($getAllCampaign){
                    if ($getAllCampaign->count() === 1) {
                        // Only one campaign found
                        $campaignID = $getAllCampaign[0]->id;
                        $campaignName = $getAllCampaign[0]->name;
                        // Set the campaign ID in session data
                        $sessionData['campaignID'] = $campaignID;
                        $sessionData['campaignName'] = $campaignName;
                        DB::table('users')
                        ->where('id', $userID)
                        ->update([
                            'campaign_id' => $campaignID, 
                            'campaign_name' => $campaignName 
                        ]);
                        // Put the updated data back into the session
                        $request->session()->put('data', $sessionData);
                        
                    }else{
                        $campMsg = 'Show Campaigns';
                    }
                }else{
                    $campMsg = "There is no campaign Assign to you. Please contact to Admin";
                }
            }

            





            $totalLoginTime=0;
            $pastLoginTime=0;
            $pastReadyTime=0;
            $pastBreakTime=0;
            $pastSoftTime=0;
            $CRDtotalLoginTime=0;
            $CBRtotalLoginTime=0;
            $CSFtotalLoginTime=0;
            $allChats=0;
            $activeChats=0;
            $oldPendingChats=0;
            $pendingChats=0;
            $closeChats=0;
            $totalTimeString='';
            $dashboardArray = [];
            if($campaignID){

                $allChats = Chats::where('assigned_to', $userID)->where('client_id', $clientid)->where('campaign_id', $campaignID)->whereDate('assigned_at', $today)->count();

                $oldPendingChats = Chats::where('assigned_to', $userID)
                ->where('client_id', $clientid)
                ->where('campaign_id', $campaignID)
                ->where('is_closed', 1)
                ->whereDate('assigned_at', '!=', $today)
                ->count();

                $activeChats = Chats::join('chat_log', 'chats.id', '=', 'chat_log.chat_id')
                ->where('chats.is_closed', 1)
                ->where('chat_log.is_read', 2)
                ->where('chats.assigned_to', $userID)
                ->where('chats.client_id', $clientid)
                ->where('chats.campaign_id', $campaignID)
                ->whereDate('chats.assigned_at', $today)
                ->distinct('chats.id') // Ensuring distinct records based on 'chats.id'
                ->count();

                // $pendingChats = Chats::join('chat_log', 'chats.id', '=', 'chat_log.chat_id')
                // ->where('chats.is_closed', 1)
                // ->where('chat_log.is_read', 1)
                // ->where('chats.assigned_to', $userID)
                // ->where('chats.client_id', $clientid)
                // ->where('chats.campaign_id', $campaignID)
                // ->whereDate('chats.created_at', $today)
                // ->distinct('chats.id') // Ensuring distinct records based on 'chats.id'
                // ->count();

                $pendingChats = Chats::join('chat_log', 'chats.id', '=', 'chat_log.chat_id')
                ->where('chats.is_closed', 1)
                ->where('chats.open_at',  NULL)
                ->where('chat_log.is_read', 1)
                ->where('chats.assigned_to', $userID)
                ->where('chats.client_id', $clientid)
                ->where('chats.campaign_id', $campaignID)
                ->whereDate('chats.assigned_at', $today)
                ->distinct('chats.id')
                ->count();



                // $activeChats = Chat_log::where('is_closed', 1)->where('assigned_to', $userID)->where('client_id', $clientid)->where('campaign_id', $campaignID)->whereDate('created_at', $today)->count();
                // $pendingChats = Chat_log::where('is_closed', 1)->where('assigned_to', $userID)->where('client_id', $clientid)->where('campaign_id', $campaignID)->whereDate('created_at', $today)->count();
                $closeChats = Chats::where('is_closed', 2)->where('assigned_to', $userID)->where('client_id', $clientid)->where('campaign_id', $campaignID)->whereDate('closed_at', $today)->count();
            }

                // $allChats = Chats::where('assigned_to', $userID)->where('client_id', $clientid)->count();
                // $pendingChats = Chats::where('is_closed', 1)->where('assigned_to', $userID)->where('client_id', $clientid)->count();
                // $closeChats = Chats::where('is_closed', 2)->where('assigned_to', $userID)->where('client_id', $clientid)->count();
            

            // Step 1 merge todays old  break time  For Login 
            $loginTimeArr = Break_log::select('break_time')->whereNotNull('break_time')->where('break_id', 'L1')->where('client_id', $clientid)->where('user_id', $userID)->whereDate('created_at', $today)->get();

            if ($loginTimeArr) {
            
                    // Loop through each time and convert it to seconds, then add to total time
                foreach ($loginTimeArr as $timeObject) {
                    if (isset($timeObject->break_time) && is_string($timeObject->break_time)) {
                           $existingTime = $timeObject->break_time;
            
                        // // Explode the time string to extract hours, minutes, and seconds
                        $timeParts = explode(':', $existingTime);

                        $totalSeconds = $timeParts[0] * 3600 + $timeParts[1] * 60 + $timeParts[2];
                        $pastLoginTime += $totalSeconds;
            
                       
                    }
                }
            }

            // Step 2 merge old + current (via start time ) break time  For Login
            $currentLoginTimeArr = Break_log::select('start_time')
            ->where('break_id', 'L1')
            ->where('client_id', $clientid)
            ->where('user_id', $userID)
            ->where('break_time', null)
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->first();

            if ($currentLoginTimeArr) {
            
                $formattedTime = $currentLoginTimeArr->start_time;
                $specificDateTime = Carbon::parse($formattedTime);
                $currentTime = Carbon::now();
                $existingTime = $specificDateTime->diff($currentTime)->format('%H:%I:%S');

                $timeParts = explode(':', $existingTime);

                $totalSeconds = $timeParts[0] * 3600 + $timeParts[1] * 60 + $timeParts[2];
                $totalLoginTime = $totalSeconds + $pastLoginTime;  

                // // Convert the total time back to HH:mm:ss format
                $hours = floor($totalLoginTime / 3600);
                $minutes = floor(($totalLoginTime % 3600) / 60);
                $seconds = $totalLoginTime % 60;
            
                $totalTimeString = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }


            // Step 1 merge todays old break time For Ready
            $readyTimeArr = Break_log::select('break_time')->whereNotNull('break_time')->where('break_id', 'R1')->where('client_id', $clientid)->where('user_id', $userID)->whereDate('created_at', $today)->get();

            if ($readyTimeArr) {

                // Loop through each time and convert it to seconds, then add to total time
                foreach ($readyTimeArr as $RDtimeObject) {
                    if (isset($RDtimeObject->break_time) && is_string($RDtimeObject->break_time)) {
                            $RDexistingTime = $RDtimeObject->break_time;
            
                        // // Explode the time string to extract hours, minutes, and seconds
                        $RDtimeParts = explode(':', $RDexistingTime);

                        $RDtotalSeconds = $RDtimeParts[0] * 3600 + $RDtimeParts[1] * 60 + $RDtimeParts[2];
                        $pastReadyTime += $RDtotalSeconds;
            
                    }
                }
            }


            // Step 2 merge old + current (via start time ) break time  For Ready
              $currentReadyTimeArr = Break_log::select('start_time')
            ->where('break_id', 'R1')
            ->where('client_id', $clientid)
            ->where('user_id', $userID)
            ->where('break_time', null)
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->first();

            if ($currentReadyTimeArr) {

                $CRDformattedTime = $currentReadyTimeArr->start_time;
                $CRDspecificDateTime = Carbon::parse($CRDformattedTime);
                $CRDcurrentTime = Carbon::now();
                $CRDexistingTime = $CRDspecificDateTime->diff($CRDcurrentTime)->format('%H:%I:%S');

                $CRDtimeParts = explode(':', $CRDexistingTime);

                $CRDtotalSeconds = $CRDtimeParts[0] * 3600 + $CRDtimeParts[1] * 60 + $CRDtimeParts[2];
                $CRDtotalLoginTime = $CRDtotalSeconds + $pastReadyTime; 

                // // Convert the total time back to HH:mm:ss format
                $CRDhours = floor($CRDtotalLoginTime / 3600);
                $CRDminutes = floor(($CRDtotalLoginTime % 3600) / 60);
                $CRDseconds = $CRDtotalLoginTime % 60;
            
                $CRDtotalTimeString = sprintf('%02d:%02d:%02d', $CRDhours, $CRDminutes, $CRDseconds);
            }else{
                // // Convert the total time back to HH:mm:ss format
                $CRDhours = floor($pastReadyTime / 3600);
                $CRDminutes = floor(($pastReadyTime % 3600) / 60);
                $CRDseconds = $pastReadyTime % 60;
            
                $CRDtotalTimeString = sprintf('%02d:%02d:%02d', $CRDhours, $CRDminutes, $CRDseconds);
            }

            // Step 1 merge todays old break time For Pause or Break
            $breakTimeArr = Break_log::select('break_time')->whereNotNull('break_time')->whereNotIn('break_id', ['L1', 'R1'])->where('client_id', $clientid)->where('user_id', $userID)->whereDate('created_at', $today)->get();

            if ($breakTimeArr) {
            
                // Loop through each time and convert it to seconds, then add to total time
                foreach ($breakTimeArr as $BRtimeObject) {
                    if (isset($BRtimeObject->break_time) && is_string($BRtimeObject->break_time)) {
                            $BRexistingTime = $BRtimeObject->break_time;
            
                        // // Explode the time string to extract hours, minutes, and seconds
                        $BRtimeParts = explode(':', $BRexistingTime);

                        $BRtotalSeconds = $BRtimeParts[0] * 3600 + $BRtimeParts[1] * 60 + $BRtimeParts[2];
                        $pastBreakTime += $BRtotalSeconds;
            
                    }
                }
            }

            // Step 2 merge old + current (via start time ) break time  For Pause or Break
            $currentBreakTimeArr = Break_log::select('start_time')
            ->whereNotIn('break_id', ['L1', 'R1'])
            ->where('client_id', $clientid)
            ->where('user_id', $userID)
            ->where('break_time', null)
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->first();

            if ($currentBreakTimeArr) {
            
                $CBRformattedTime = $currentBreakTimeArr->start_time;
                $CBRspecificDateTime = Carbon::parse($CBRformattedTime);
                $CBRcurrentTime = Carbon::now();
                $CBRexistingTime = $CBRspecificDateTime->diff($CBRcurrentTime)->format('%H:%I:%S');

                $CBRtimeParts = explode(':', $CBRexistingTime);

                $CBRtotalSeconds = $CBRtimeParts[0] * 3600 + $CBRtimeParts[1] * 60 + $CBRtimeParts[2];
                $CBRtotalLoginTime = $CBRtotalSeconds + $pastBreakTime; 

                 // Convert the total time back to HH:mm:ss format
                $CBRhours = floor($CBRtotalLoginTime / 3600);
                $CBRminutes = floor(($CBRtotalLoginTime % 3600) / 60);
                $CBRseconds = $CBRtotalLoginTime % 60;
            
                $CBRtotalTimeString = sprintf('%02d:%02d:%02d', $CBRhours, $CBRminutes, $CBRseconds);
            }else{
                 // Convert the total time back to HH:mm:ss format
                 $CBRhours = floor($pastBreakTime / 3600);
                 $CBRminutes = floor(($pastBreakTime % 3600) / 60);
                 $CBRseconds = $pastBreakTime % 60;
             
                 $CBRtotalTimeString = sprintf('%02d:%02d:%02d', $CBRhours, $CBRminutes, $CBRseconds);
            }

            //code for ideal time
            //Login
            $forLogin = explode(':', $totalTimeString);
            $LoginInSeconds = $forLogin[0] * 3600 + $forLogin[1] * 60 + $forLogin[2];

            //Ready
            $forReady = explode(':', $CRDtotalTimeString);
            $ReadyInSeconds = $forReady[0] * 3600 + $forReady[1] * 60 + $forReady[2];

            //Break
            $forBreak = explode(':', $CBRtotalTimeString);
            $BreakInSeconds = $forBreak[0] * 3600 + $forBreak[1] * 60 + $forBreak[2];

            $idealTimeInSeconds = $LoginInSeconds - ($ReadyInSeconds + $BreakInSeconds); 
            if($idealTimeInSeconds>0){   
                 $datahours = floor($idealTimeInSeconds / 3600);
                 $dataminutes = floor(($idealTimeInSeconds % 3600) / 60);
                 $dataseconds = $idealTimeInSeconds % 60;
                 $IDLtotalTimeString = sprintf('%02d:%02d:%02d', $datahours, $dataminutes, $dataseconds);
            }else{
                $IDLtotalTimeString = '00:00:00';
            }
            

            //SOft Break code
            // Step 1 merge todays old break time For Soft
            $softTimeArr = Break_log::select('break_time')->whereNotNull('break_time')->where('break_id', 'S1')->where('client_id', $clientid)->where('user_id', $userID)->whereDate('created_at', $today)->get();

            if ($softTimeArr) {

                // Loop through each time and convert it to seconds, then add to total time
                foreach ($softTimeArr as $SFtimeObject) {
                    if (isset($SFtimeObject->break_time) && is_string($SFtimeObject->break_time)) {
                            $SFexistingTime = $SFtimeObject->break_time;
            
                        // // Explode the time string to extract hours, minutes, and seconds
                        $SFtimeParts = explode(':', $SFexistingTime);

                        $SFtotalSeconds = $SFtimeParts[0] * 3600 + $SFtimeParts[1] * 60 + $SFtimeParts[2];
                        $pastSoftTime += $SFtotalSeconds;
            
                    }
                }
            }


            // Step 2 merge old + current (via start time ) break time  For Soft
              $currentSoftTimeArr = Break_log::select('start_time')
            ->where('break_id', 'S1')
            ->where('client_id', $clientid)
            ->where('user_id', $userID)
            ->where('break_time', null)
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->first();

            if ($currentSoftTimeArr) {

                $CSFformattedTime = $currentSoftTimeArr->start_time;
                $CSFspecificDateTime = Carbon::parse($CSFformattedTime);
                $CSFcurrentTime = Carbon::now();
                $CSFexistingTime = $CSFspecificDateTime->diff($CSFcurrentTime)->format('%H:%I:%S');

                $CSFtimeParts = explode(':', $CSFexistingTime);

                $CSFtotalSeconds = $CSFtimeParts[0] * 3600 + $CSFtimeParts[1] * 60 + $CSFtimeParts[2];
                $CSFtotalLoginTime = $CSFtotalSeconds + $pastSoftTime; 

                // // Convert the total time back to HH:mm:ss format
                $CSFhours = floor($CSFtotalLoginTime / 3600);
                $CSFminutes = floor(($CSFtotalLoginTime % 3600) / 60);
                $CSFseconds = $CSFtotalLoginTime % 60;
            
                $CSFtotalTimeString = sprintf('%02d:%02d:%02d', $CSFhours, $CSFminutes, $CSFseconds);
            }else{
                // // Convert the total time back to HH:mm:ss format
                $CSFhours = floor($pastSoftTime / 3600);
                $CSFminutes = floor(($pastSoftTime % 3600) / 60);
                $CSFseconds = $pastSoftTime % 60;
            
                $CSFtotalTimeString = sprintf('%02d:%02d:%02d', $CSFhours, $CSFminutes, $CSFseconds);
            }

            
           

            $dashboardArray['allChats'] = $allChats;
            $dashboardArray['activeChats'] = $activeChats;
            $dashboardArray['oldPendingChats'] = $oldPendingChats;
            $dashboardArray['pendingChats'] = $pendingChats;
            $dashboardArray['closeChats'] = $closeChats;
            $dashboardArray['loginTime'] = $totalTimeString;
            $dashboardArray['readyTime'] = $CRDtotalTimeString;
            $dashboardArray['breakTime'] = $CBRtotalTimeString;
            $dashboardArray['idealTime'] = $IDLtotalTimeString;
            $dashboardArray['softTime'] = $CSFtotalTimeString;
            $dashboardArray['campMsg'] = $campMsg;
            return response()->json($dashboardArray);
        }else{
            return view('login');
        } 

        
    }

    public function refresh_break_time(Request $request)
    {
        $today = Carbon::now()->toDateString();
        $sessionData = Session::get('data');
        // if(!$sessionData) {
        //     return redirect()->route('signout');
        // }
        if(session()->has('data'))
        {
            $dashboardArray = [];
            $userID = $sessionData['userID'];
            $clientid = $sessionData['Client_id'];
            $existingTime='';
            $break_name='';
            $currentLoginTimeArr = Break_log::select('start_time','break_name')
            ->where('user_id', $userID)
            ->where('client_id', $clientid)
            ->where('break_time', null)
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->first();

            if ($currentLoginTimeArr) {
                $break_name = $currentLoginTimeArr->break_name;
                $formattedTime = $currentLoginTimeArr->start_time;
                $specificDateTime = Carbon::parse($formattedTime);
                $currentTime = Carbon::now();
                $existingTime = $specificDateTime->diff($currentTime)->format('%H:%I:%S');

            }


           
           
            $dashboardArray['current_break'] = $existingTime;
            $dashboardArray['break_name'] = $break_name;
            return response()->json($dashboardArray);
        }else{
            return view('login');
        } 

        
    }

    public function check_agent_is_login()
	{
        $sessionData = Session::get('data');
		if($sessionData)
		{
            $checkLogin = 'login';
            $data = [
				'checkLogin' => $checkLogin,
				'agentid' => $sessionData['userID'],
				'agentrole' => $sessionData['userRole'],
				'clientid' => $sessionData['Client_id'],
			];
			
		}else{
            $checkLogin = '';
            $data = [
				'checkLogin' => $checkLogin,
			];
        }

        return response()->json($data);
		
	}


    public function setCampaignSession(Request $request)
    {
        // $today = Carbon::now();
        $today = Carbon::now()->toDateString();
        $sessionData = $request->session()->get('data');
         $campaigns = $request->input('selectedCampaign');
          $parts = explode('_', $campaigns);

        if (count($parts) >= 2) {
            $campaignID = $parts[0];  // First part before the underscore
            $campaignName = $parts[1];
        }
        $userID = $sessionData['userID'];
        $clientid = $sessionData['Client_id'];
        $dashboardArray = [];
        if ($campaignID) {
            // Update campaign ID in the session data
            $sessionData['campaignID'] = $campaignID;
            $sessionData['campaignName'] = $campaignName;

            DB::table('users')
            ->where('id', $userID)
            ->update([
                'campaign_id' => $campaignID, 
                'campaign_name' => $campaignName 
            ]);
            // Put the updated data back into the session
            $request->session()->put('data', $sessionData);
            
            if($campaignID){
                $allChats = Chats::where('assigned_to', $userID)->where('client_id', $clientid)->where('campaign_id', $campaignID)->whereDate('created_at', $today)->count();

                $oldPendingChats = Chats::where('assigned_to', $userID)->where('client_id', $clientid)->where('campaign_id', $campaignID)->where('chats.is_closed', 1)->count();
                // $activeChats = Chats::where('is_closed', 1)->where('assigned_to', $userID)->where('client_id', $clientid)->where////////('campaign_id', $campaignID)->where('is_read', 2)->whereDate('created_at', $today)->count();
               // $pendingChats = Chats::where('is_closed', 1)->where('assigned_to', $userID)->where('client_id', $clientid)->where('campaign_id', $campaignID)->where('is_read', 1)->whereDate('created_at', $today)->count();
               
                $activeChats = Chats::join('chat_log', 'chats.id', '=', 'chat_log.chat_id')
                ->where('chats.is_closed', 1)
                ->where('chat_log.is_read', 2)
                ->where('chats.assigned_to', $userID)
                ->where('chats.client_id', $clientid)
                ->where('chats.campaign_id', $campaignID)
                ->whereDate('chats.created_at', $today)
                ->distinct('chats.id') // Ensuring distinct records based on 'chats.id'
                ->count();

                $pendingChats = Chats::join('chat_log', 'chats.id', '=', 'chat_log.chat_id')
                ->where('chats.is_closed', 1)
                ->where('chat_log.is_read', 1)
                ->where('chats.assigned_to', $userID)
                ->where('chats.client_id', $clientid)
                ->where('chats.campaign_id', $campaignID)
                ->whereDate('chats.created_at', $today)
                ->distinct('chats.id') // Ensuring distinct records based on 'chats.id'
                ->count();

                
                $closeChats = Chats::where('is_closed', 2)->where('assigned_to', $userID)->where('client_id', $clientid)->where('campaign_id', $campaignID)->whereDate('created_at', $today)->count();
            }

            $dashboardArray['allChats'] = $allChats;
            $dashboardArray['activeChats'] = $activeChats;
            $dashboardArray['oldPendingChats'] = $oldPendingChats;
            $dashboardArray['pendingChats'] = $pendingChats;
            $dashboardArray['closeChats'] = $closeChats;
            $dashboardArray['campaignName'] = $campaignName;
            // return $dashboardArray;
            // Return the updated session data
            return response()->json($dashboardArray);
        }
        return response()->json(['error' => 'Invalid Campaign ID']);
    }


}
