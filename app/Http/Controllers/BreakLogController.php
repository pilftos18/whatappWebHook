<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Chats;
use App\Models\Users;
use App\Models\Chat_log;
use App\Models\Break_log;
use App\Models\Disposition;
use Illuminate\Support\Facades\Hash;
use App\Models\Session_Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
class BreakLogController extends Controller
{
    public function store_break(Request $request)
    {   

        DB::enableQueryLog();
        $agent_log = Log::channel('agent_log');
        $agent_log->debug(__LINE__."\n\n\n-------------------- store_break Funtion Start  ------------------------------------");

        // return $request->breakId;
         $sessionData = Session::get('data');
         $currentDateTime = now('Asia/Kolkata')->format('Y-m-d H:i:s');
         $currentDate = Carbon::now('Asia/Kolkata')->toDateString();
        // Validate incoming data if needed
        // $validatedData = $request->validate([
        //     'breakId' => 'required',
        //     'breakName' => 'required',
        //     // Add validation rules for other fields if necessary
        // ]);

        // Check if there is an existing break log record that needs to be updated
        $lastBreakLog = Break_log::where('client_id', $sessionData['Client_id'])
        ->where('user_id', $sessionData['userID'])
        ->where('break_id', '!=', 'L1')
        ->whereDate('start_time', $currentDate) // Filter by today's date
        ->where('break_time', null)
        ->latest() // Get the latest record based on start_time
        ->first();
        $agent_log->debug(__LINE__.'\n fetch Last Break Data : ' . json_encode($lastBreakLog));

        // If a record is found, update its end_time
        if ($lastBreakLog !== null) {
            $lastBreakLog->end_time = $currentDateTime;
            $lastBreakLog->activity_status = 2;
            $lastBreakLog->break_time = strtotime($currentDateTime) - strtotime($lastBreakLog->start_time);
            $time_diff = strtotime($currentDateTime) - strtotime($lastBreakLog->start_time);
            $hours = floor($time_diff / 3600);
            $minutes = floor(($time_diff % 3600) / 60);
            $seconds = $time_diff % 60;
            $lastBreakLog->break_time = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
            $lastBreakLog->save();
        }

        Session::put('breakName', $request->breakName);
        // Create a new BreakLog instance and save to the database
        $breakLog = new Break_log(); // Create a new instance

        // Assign attributes
        $breakLog->client_id = $sessionData['Client_id'];
        $breakLog->break_id = $request->breakId;
        $breakLog->break_name = $request->breakName;
        $breakLog->user_id = $sessionData['userID'];
        $breakLog->start_time = $currentDateTime;

        // Save to the database
        $breakLog->save();

        $agent_log->debug(__LINE__.'\n Data inserted in break log : ' . $breakLog);

        if($request->breakId == 'R1'){
            $breakStatus = 2;
        }elseif($request->breakId == 'S1'){
            $breakStatus = 4;
        }else{
            $breakStatus = 3;
        }
        $userid = $sessionData['userID'];
        $user = Users::find($userid); // Retrieve the user by ID
        $agent_log->debug(__LINE__.'\n fetch User Data : ' . json_encode($user));
        if ($user) {
            $agent_log->debug(__LINE__.'\n login_status : ' . $breakStatus);
            $user->login_status = $breakStatus; // Update the login_status column
            $user->save(); // Save the changes to the database
        }

        $agent_log->debug(__LINE__."\nBreak log saved successfully");
        $agent_log->debug(__LINE__."\n\n\n-------------------- store_break Funtion END  ------------------------------------");
        return response()->json(['message' => 'Break log saved successfully']);

    }


   

}
