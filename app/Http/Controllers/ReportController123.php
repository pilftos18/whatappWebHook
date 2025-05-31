<?php

namespace App\Http\Controllers;
use App\Models\Clients;
use App\Models\Users;
use App\Models\Campaign;
use App\Models\CallWindow;
use App\Models\MappedUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
// use Barryvdh\DomPDF\Facade as PDF;

class ReportController extends Controller
{
    public function getUserLoginList(){
        $sessionData = session('data');
        // print_r($sessionData);exit;
        $client_id = $sessionData['Client_id'];
        $users = Users::where('client_id', $client_id)
        ->whereIn('status', [0, 1])
        ->where('role','=','user')
        ->pluck('id','name');

        return response()->json($users);
    }

    public function getLoginReport(Request $request){

        DB::enableQueryLog();
        $custom_log = Log::channel('custom_log');
        $custom_log->debug(__LINE__."\n\n\n--------------------Start The login report ------------------------------------");
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $sqlDateFrom = date('Y-m-d', strtotime($dateFrom));
        $sqlDateTo = date('Y-m-d', strtotime($dateTo));
        $user = $request->input('user');

        $logMessage = __LINE__ . " : getting request data for loginreport --";
        $logMessage .= "\nClient ID: " . $client_id;
        $logMessage .= "\nDate From: " . $dateFrom;
        $logMessage .= "\nDate To: " . $dateTo;
        $logMessage .= "\nSQL Date From: " . $sqlDateFrom;
        $logMessage .= "\nSQL Date To: " . $sqlDateTo;
        $logMessage .= "\nUser: " . json_encode($user); 
        $custom_log->debug($logMessage);

        //echo "<pre>";print_r($campaign);die;  
            if($user[0] === "All"){
                //echo 2;
                $userslist = Users::where('client_id', $client_id)
                ->whereIn('status', [0, 1])
                ->where('role','=','user')->pluck('id')->toArray();

                $queryLog = DB::getQueryLog();
                    $lastQuery = end($queryLog)['query'];
                    $lastBindings = end($queryLog)['bindings'];

                    $custom_log->debug(__LINE__." : if all users is there then --");
                    $custom_log->debug("Query for user: " . $lastQuery);
                    $custom_log->debug("Bindings: " . json_encode($lastBindings));

                    $data = DB::table('break_log')
                    ->leftjoin('users','users.id','=','break_log.user_id')
                    ->leftjoin('clients','clients.id','=','break_log.client_id')
                    ->select('clients.name as clientname','users.name as Name','users.username as Username','break_log.break_name','break_log.start_time','break_log.end_time','break_log.break_time','break_log.activity_status')
                    ->whereRaw("DATE(break_log.start_time) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
                    ->where('break_log.client_id', $client_id)
                    ->whereIn('break_log.user_id', $userslist)
                    ->orderBy('break_log.user_id')
                    ->orderBy('break_log.start_time', 'desc')
                    ->get();
                    
                $queryLog = DB::getQueryLog();
                $lastQuery = end($queryLog)['query'];
                $lastBindings = end($queryLog)['bindings'];

                $custom_log->debug(__LINE__." : query for loginreport including user --");
                $custom_log->debug(" Query for login report: " . $lastQuery);
                $custom_log->debug("Bindings: " . json_encode($lastBindings));
            }else{

                $data = DB::table('break_log')
                    ->leftjoin('users','users.id','=','break_log.user_id')
                    ->leftjoin('clients','clients.id','=','break_log.client_id')
                    ->select('clients.name as clientname','users.name as Name','users.username as Username','break_log.break_name','break_log.start_time','break_log.end_time','break_log.break_time','break_log.activity_status')
                    ->whereRaw("DATE(break_log.start_time) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
                    ->where('break_log.client_id', $client_id)
                    ->whereIn('break_log.user_id', $user)
                    ->orderBy('break_log.user_id')
                    ->orderBy('break_log.start_time', 'desc')
                    ->get();

                $queryLog = DB::getQueryLog();
                $lastQuery = end($queryLog)['query'];
                $lastBindings = end($queryLog)['bindings'];

                $custom_log->debug(__LINE__." : query for loginreport including user --");
                $custom_log->debug("Query for login report:" . $lastQuery);
                $custom_log->debug("Bindings: " . json_encode($lastBindings));

            }

            $csvarray = [];

            $sessionData = $request->session()->get('data');
            $userRole = $sessionData['userRole'] ?? '';
    
            // if ($userRole == 'super_admin') {

                $custom_log->debug(__LINE__." : put content in csv if userrole is super_admin-----");
    
                $csvarray[] = ['Client','Name','Username','break_name','start_time', 'end_time', 'break_time','activity_status'];
    
                foreach ($data as $row) {
                    $csvarray[] = [
                        $row->clientname,
                        $row->Name,
                        $row->Username,
                        $row->break_name,
                        $row->start_time,
                        $row->end_time,
                        $row->break_time,
                        ($row->activity_status == 1) ? 'In progress' : 'completed',
                    ];
                }
    
            // }   
            $custom_log->debug(__LINE__." : generate csv -----");
    
            $timestamp = date('Y_m_d_H_i_s');
            $filename = 'LoginActivityReport_' . $timestamp . '.csv';
            $custom_log->debug(__LINE__." : file created -----");
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\""
            ];
            $tempFilePath = tempnam(sys_get_temp_dir(), 'LoginActivityReport');
            $tempFile = fopen($tempFilePath, 'w');
    
            foreach ($csvarray as $row) {
                fputcsv($tempFile, $row);
            }
            $custom_log->debug(__LINE__." : put csv content in file -----");
            fclose($tempFile);
            $url = request()->root();
            $parsedUrl = parse_url($url);
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
            $filePath = storage_path("app/public/uploads/loginreport/$filename");
            //$file_url = $baseUrl . "/public/storage/uploads/rcbulk/s" . $filename;
            $file_url = $baseUrl . "/storage/app/public/uploads/loginreport/" . $filename;
            $custom_log->debug(__LINE__." : file storage as -----");
    
            $custom_log->debug(__LINE__." : loginreport generate -----");
            rename($tempFilePath, $filePath);
            chmod($filePath, 0755);
            return response()->json(['download' => '1', 'file_url' => $file_url, 'file_name' => $filename], 200);
            

    }

    public function getSummaryReport(Request $request){

        DB::enableQueryLog();
        $custom_log = Log::channel('custom_log');
        $custom_log->debug(__LINE__."\n\n\n--------------------Start The summary report ------------------------------------");
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $sqlDateFrom = date('Y-m-d', strtotime($dateFrom));
        $sqlDateTo = date('Y-m-d', strtotime($dateTo));
        $user = $request->input('user');

        $logMessage = __LINE__ . " : getting request data for summary report  --";
        $logMessage .= "\nClient ID: " . $client_id;
        $logMessage .= "\nDate From: " . $dateFrom;
        $logMessage .= "\nDate To: " . $dateTo;
        $logMessage .= "\nSQL Date From: " . $sqlDateFrom;
        $logMessage .= "\nSQL Date To: " . $sqlDateTo;
        $logMessage .= "\nUser: " . json_encode($user); 
        $custom_log->debug($logMessage);

        //echo "<pre>";print_r($campaign);die;  
            if($user[0] === "All"){
                //echo 2;
                $userslist = Users::where('client_id', $client_id)
                ->whereIn('status', [0, 1])
                ->where('role','=','user')->pluck('id')->toArray();

                $queryLog = DB::getQueryLog();
                    $lastQuery = end($queryLog)['query'];
                    $lastBindings = end($queryLog)['bindings'];

                    $custom_log->debug(__LINE__." : if all users is there then --");
                    $custom_log->debug("Query for user: " . $lastQuery);
                    $custom_log->debug("Bindings: " . json_encode($lastBindings));

                $query_chat =  DB::table('chats')
                        ->select(
                            DB::raw('DATE(chats.created_at) AS Date'),
                            'users.id AS Agent_id',
                            'users.name AS Agent_name',
                            'campaign.id AS Campaign_id',
                            'campaign.name AS Campaign',
                            DB::raw('SUM(CASE WHEN chats.is_closed IN ("0","1","2") THEN 1 ELSE 0 END) as Total_Count'),
                            DB::raw('SUM(CASE WHEN chats.is_closed = "2" THEN 1 ELSE 0 END) AS Closed_Count')
                        )
                        ->leftJoin('users', 'users.id', '=', 'chats.assigned_to')
                        ->leftJoin('clients', 'chats.client_id', '=', 'clients.id')
                        ->leftJoin('campaign', 'chats.campaign_id', '=', 'campaign.id')
                        ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
                         ->where('chats.client_id', $client_id)
                        ->whereIn('chats.assigned_to', $userslist)
                        ->whereIn('campaign.status',[0,1])
                        ->whereIn('users.status',[0,1])
                        ->whereIn('chats.status',[0,1])
                        ->groupBy('Date', 'Agent_id',  'Campaign_id')
                        ->orderBy('Date', 'desc')
                        ->get();


                        $query_active_pending_chat = DB::table('chats')
                        ->join('chat_log', 'chats.id', '=', 'chat_log.chat_id')
                        ->join('users', 'users.id', '=', 'chats.assigned_to')
                        ->select(
                            'users.id AS Agent_id',
                            DB::raw('DATE(chats.created_at) AS date_chat'),
                            DB::raw('COUNT(DISTINCT CASE WHEN chat_log.in_out = 1 AND chat_log.is_read = 2  THEN chats.id END) AS active_chat'), //in_out = 1 receive  is_read = 2 chat dekh liya
                            DB::raw('COUNT(DISTINCT CASE WHEN chat_log.in_out = 1 AND chat_log.is_read = 1 THEN chats.id END) AS pending_chat'), //in_out = 1 receive  is_read = 1 chat  nahi dekha
                            )
                            ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
                            ->where('chats.is_closed', 1)
                            ->where('chats.client_id', $client_id)
                            ->whereIn('chats.assigned_to', $userslist)
                            ->whereIn('users.status',[0,1])
                            ->whereIn('chats.status',[0,1])
                            ->groupBy('date_chat', 'chats.assigned_to','chats.client_id')
                            ->get();

                           

                    $query_break_log =  DB::table('break_log')
                    ->select(
                        'break_log.user_id AS userid',
                        DB::raw('DATE(break_log.created_at) AS Date_log'),
                        DB::raw('MIN(break_log.created_at) AS Latest_Login_time'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Login" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Login_duration'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name NOT IN ("Login") THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Duration'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Login" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END) - SUM(CASE WHEN break_log.break_name NOT IN ("Login") THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Ideal_duration'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name NOT IN ("Ready","Soft","Login") THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Total_Break_Time'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Soft" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Soft_Break'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Tea" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Tea'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Bio" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Bio'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Ready" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Ready'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Lunch" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Lunch_Break'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Meeting" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Meeting'),
                    )
                    // ->leftJoin('users', 'users.id', '=', 'break_log.user_id')
                    // ->leftJoin('clients', 'users.client_id', '=', 'clients.id')
                    ->whereRaw("DATE(break_log.created_at) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
                    ->where('break_log.client_id', $client_id)
                    ->whereIn('break_log.user_id', $userslist)
                    // ->whereIn('users.status',[0,1])
                    ->groupBy('break_log.client_id', 'break_log.user_id', 'Date_log')
                    ->orderBy('Date_log', 'desc')
                    ->orderBy('Latest_Login_time', 'desc')
                    ->get();
                 //echo "<pre>";print_r($query_break_log);exit;


                $breaksArr = $chatsArr = [];


                foreach($query_active_pending_chat as $k => $chats){
                    $chatsArr[$chats->Agent_id][$chats->date_chat] = $chats;
                
                }

                foreach($query_break_log as $k => $breaks){
                    $breaksArr[$breaks->userid][$breaks->Date_log] = $breaks;
                    
                }

                $queryLog = DB::getQueryLog();
                $lastQuery = end($queryLog)['query'];
                $lastBindings = end($queryLog)['bindings'];

                $custom_log->debug(__LINE__." : query for summary report including user --");
                $custom_log->debug(" Query for summary report: " . $lastQuery);
                $custom_log->debug("Bindings: " . json_encode($lastBindings));

            }else{  


                $query_chat = DB::table('chats')
                ->leftJoin('users', 'users.id', '=', 'chats.assigned_to')
                ->leftJoin('clients', 'chats.client_id', '=', 'clients.id')
                ->leftJoin('campaign', 'chats.campaign_id', '=', 'campaign.id')
                ->select(
                    DB::raw('DATE(chats.created_at) AS Date'),
                    'users.id AS Agent_id',
                    'users.name AS Agent_name',
                    'campaign.id AS Campaign_id',
                    'campaign.name AS Campaign',
                    DB::raw('SUM(CASE WHEN chats.is_closed IN ("0","1","2") THEN 1 ELSE 0 END) as Total_Count'),
                    DB::raw('SUM(CASE WHEN chats.is_closed = "2" THEN 1 ELSE 0 END) AS Closed_Count'),
                )
                ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
                ->where('chats.client_id', $client_id)
                ->whereIn('chats.assigned_to', $user)
                ->whereIn('campaign.status',[0,1])
                ->whereIn('users.status',[0,1])
                ->whereIn('chats.status',[0,1])
                ->groupBy('Date', 'Agent_id',  'Campaign_id')
                ->orderBy('Date', 'desc')
                ->get();


                //echo "<pre>";print_r($query_chat);die;

                $query_active_pending_chat = DB::table('chats')
                    ->join('chat_log', 'chats.id', '=', 'chat_log.chat_id')
                    ->join('users', 'users.id', '=', 'chats.assigned_to')
                    ->select(
                        'users.id AS Agent_id',
                        DB::raw('DATE(chats.created_at) AS date_chat'),
                        DB::raw('COUNT(DISTINCT CASE WHEN chat_log.in_out = 1 AND chat_log.is_read = 2  THEN chats.id END) AS active_chat'), //in_out = 1 receive  is_read = 2 chat dekh liya
                        DB::raw('COUNT(DISTINCT CASE WHEN chat_log.in_out = 1 AND chat_log.is_read = 1 THEN chats.id END) AS pending_chat'), //in_out = 1 receive  is_read = 1 chat  nahi dekha
                    )
                    ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
                    ->where('chats.is_closed', 1)
                    ->where('chats.client_id', $client_id)
                    ->whereIn('chats.assigned_to', $user)
                    ->whereIn('users.status',[0,1])
                    ->whereIn('chats.status',[0,1])
                    ->groupBy('date_chat', 'chats.assigned_to','chats.client_id')
                    ->get();

                $query_break_log = DB::table('break_log')
                ->select(
                    'break_log.user_id AS userid',
                    DB::raw('DATE(break_log.created_at) AS Date_log'),
                    DB::raw('MIN(break_log.created_at) AS Latest_Login_time'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Login" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Login_duration'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name NOT IN ("Login") THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Duration'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Login" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END) - SUM(CASE WHEN break_log.break_name NOT IN ("Login") THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Ideal_duration'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name NOT IN ("Ready","Soft","Login") THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Total_Break_Time'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Soft" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Soft_Break'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Tea" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Tea'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Bio" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Bio'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Ready" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Ready'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Lunch" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Lunch_Break'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Meeting" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Meeting'),
                )
                // ->leftJoin('users', 'users.id', '=', 'break_log.user_id')
                // ->leftJoin('clients','clients.id' ,'=','break_log.client_id')
                ->whereRaw("DATE(break_log.created_at) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
                ->where('break_log.client_id', $client_id)
                ->whereIn('break_log.user_id', $user)
                //->whereIn('users.status',[0,1])
                ->groupBy('break_log.client_id', 'break_log.user_id','Date_log')
                ->orderBy('Date_log', 'desc')
                ->orderBy('Latest_Login_time', 'desc')
                ->get();

                $breaksArr = $chatsArr = [];


                foreach($query_active_pending_chat as $k => $chats){
                    $chatsArr[$chats->Agent_id][$chats->date_chat] = $chats;
                    //echo "<pre>_123";print_r($chats);
                }

                foreach($query_break_log as $k => $breaks){
                    $breaksArr[$breaks->userid][$breaks->Date_log] = $breaks;
                    
                }


            //echo "<pre>_456";print_r($breaksArr);die;
        
                $queryLog = DB::getQueryLog();
                $lastQuery = end($queryLog)['query'];
                $lastBindings = end($queryLog)['bindings'];

                $custom_log->debug(__LINE__." : query for summary report including user --");
                $custom_log->debug("Query for summary report:" . $lastQuery);
                $custom_log->debug("Bindings: " . json_encode($lastBindings));


            }
            //  echo "<pre>_123";print_r($chats);
            //  echo "<pre>_123";print_r($breaksArr);

            $csvarray = [];

            $sessionData = $request->session()->get('data');
            $userRole = $sessionData['userRole'] ?? '';
    
            // if ($userRole == 'super_admin') {

                $custom_log->debug(__LINE__." : put content in csv if userrole is super_admin-----");
    
                $csvarray[] = ['Date','Agent_name','Campaign','Total Chat','Closed chat','Active chat','Pending Chat','Soft Break','Total Break','Tea','Bio','Lunch Break','Meeting','login Duration','Ready Duration','Ideal duration'];
    
                foreach ($query_chat as $k => $row) {
                    $csvarray[] = [

                        // $idle = $breaks->Login_duration - $breaks->Duration,

                        $row->Date ?? '',
                        //$row->Agent_id ?? '',
                        $row->Agent_name ?? '',
                        $row->Campaign ?? '',
                        $row->Total_Count ?? '',
                        $row->Closed_Count ?? '',
                        $chatsArr[$row->Agent_id][$row->Date]->active_chat ?? '', // chatsArr[$row->Agent_id][$row->Date]
                        $chatsArr[$row->Agent_id][$row->Date]->pending_chat ?? '', //chatsArr[$row->Agent_id][$row->Date]
                        $breaksArr[$row->Agent_id][$row->Date]->Soft_Break ?? '', //breaksArr
                        $breaksArr[$row->Agent_id][$row->Date]->Total_Break_Time ?? '',
                        $breaksArr[$row->Agent_id][$row->Date]->Tea ?? '',
                        $breaksArr[$row->Agent_id][$row->Date]->Bio ?? '',
                        $breaksArr[$row->Agent_id][$row->Date]->Lunch_Break ?? '',
                        $breaksArr[$row->Agent_id][$row->Date]->Meeting ?? '',
                        $breaksArr[$row->Agent_id][$row->Date]->Login_duration ?? '',
                        $breaksArr[$row->Agent_id][$row->Date]->Ready ?? '',
                        $breaksArr[$row->Agent_id][$row->Date]->Ideal_duration ?? '',
                        //$breaksArr[$row->Agent_id][$row->Date]->Duration ?? '',
                    ];
                }
    
            // }   
            $custom_log->debug(__LINE__." : generate csv -----");
    
            $timestamp = date('Y_m_d_H_i_s');
            $filename = 'SummaryReport_' . $timestamp . '.csv';
            $custom_log->debug(__LINE__." : file created -----");
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\""
            ];
            $tempFilePath = tempnam(sys_get_temp_dir(), 'SummaryReport');
            $tempFile = fopen($tempFilePath, 'w');
    
            foreach ($csvarray as $row) {
                fputcsv($tempFile, $row);
            }
            $custom_log->debug(__LINE__." : put csv content in file -----");
            fclose($tempFile);
            $url = request()->root();
            $parsedUrl = parse_url($url);
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
            $filePath = storage_path("app/public/uploads/loginreport/$filename");
            //$file_url = $baseUrl . "/public/storage/uploads/rcbulk/s" . $filename;
            $file_url = $baseUrl . "/storage/app/public/uploads/loginreport/" . $filename;
            $custom_log->debug(__LINE__." : file storage as -----");
    
            $custom_log->debug(__LINE__." : SummaryReport_ generate -----");
            rename($tempFilePath, $filePath);
            chmod($filePath, 0755);
            return response()->json(['download' => '1', 'file_url' => $file_url, 'file_name' => $filename], 200);
            

    }

    public function getWhatsappCrmReport(Request $request)
    {   
        DB::enableQueryLog();
        $custom_log = Log::channel('custom_log');
        $custom_log->debug(__LINE__."\n\n\n--------------------Start The WhatsappCrm report ------------------------------------");
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $sqlDateFrom = date('Y-m-d', strtotime($dateFrom));
        $sqlDateTo = date('Y-m-d', strtotime($dateTo));
        $user = $request->input('user');

        $logMessage = __LINE__ . " : getting request data for WhatsappCrm report --";
        $logMessage .= "\nClient ID: " . $client_id;
        $logMessage .= "\nDate From: " . $dateFrom;
        $logMessage .= "\nDate To: " . $dateTo;
        $logMessage .= "\nSQL Date From: " . $sqlDateFrom;
        $logMessage .= "\nSQL Date To: " . $sqlDateTo;
        $logMessage .= "\nUser: " . json_encode($user); 
        $custom_log->debug($logMessage);

        if($user[0] === "All"){
            $userslist = Users::where('client_id', $client_id)
            ->whereIn('status', [0, 1])
            ->where('role','=','user')->pluck('id')->toArray();

            $queryLog = DB::getQueryLog();
            $lastQuery = end($queryLog)['query'];
            $lastBindings = end($queryLog)['bindings'];

            $custom_log->debug(__LINE__." : if all users is there then --");
            $custom_log->debug("Query for users: " . $lastQuery);
            $custom_log->debug("Bindings: " . json_encode($lastBindings));

            $data = DB::table('chats')
            ->leftjoin('users', 'users.id', '=', 'chats.assigned_to')
            ->leftjoin('clients', 'clients.id', '=', 'chats.client_id')
            ->leftjoin('campaign', 'campaign.id', '=', 'chats.campaign_id')
            ->leftJoin('users as closed_user', 'closed_user.id', '=', 'chats.closed_by')
            ->leftJoin('users as assigning_user', 'assigning_user.id', '=', 'chats.assigned_by')
            ->select(
                'clients.name as clientname',
                'campaign.name as campaign_name',
                'users.name as user_name',
                'users.role as role',
                'chats.id as chat_id',
                'chats.customer_name as cust_name',
                'chats.cust_unique_id',
                'chats.assigned_to as assignto',
                'chats.assigned_by as assignby',
                'chats.assigning_flag',
                'chats.assigned_to',
                'chats.assigned_at',
                'assigning_user.name as assigned_by',
                'chats.is_closed',
                'closed_user.name as closed_by',
                'chats.dispo',
                'chats.sub_dispo',
                'chats.remark',
                'chats.created_at'
            )
            ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
            ->whereIn('chats.is_closed',['1','2','3','4'])
            ->where('clients.id', $client_id)
            ->orderBy('chats.id','desc')
            ->get();
           

            $queryLog = DB::getQueryLog();
                $lastQuery = end($queryLog)['query'];
                $lastBindings = end($queryLog)['bindings'];

                $custom_log->debug(__LINE__." : query for WhatsappCrm report including user --");
                $custom_log->debug(" Query for WhatsappCrm report: " . $lastQuery);
                $custom_log->debug(" Bindings: " . json_encode($lastBindings));

        }else{

            $data = DB::table('chats')
            ->leftjoin('users', 'users.id', '=', 'chats.assigned_to')
            ->leftjoin('clients','clients.id', '=' ,'chats.client_id')
            ->leftjoin('campaign', 'campaign.id' , '=','chats.campaign_id')
            ->leftJoin('users as closed_user', 'closed_user.id', '=', 'chats.closed_by') 
            ->leftJoin('users as assigning_user', 'assigning_user.id', '=', 'chats.assigned_by')  
            ->select('clients.name as clientname', 'campaign.name as campaign_name','users.name as user_name', 'users.role as role','chats.id as chat_id','chats.customer_name as cust_name','chats.cust_unique_id','chats.assigned_to as assignto','chats.assigned_by as assignby','chats.assigning_flag','chats.assigned_to','chats.assigned_at','assigning_user.name as assigned_by','chats.is_closed','closed_user.name as closed_by','chats.dispo','chats.sub_dispo','chats.remark','chats.created_at')
            ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
            ->where('clients.id', $client_id)
            ->whereIn('chats.assigned_to', $user)
            ->orderBy('chats.id','desc')
            ->get();

            $queryLog = DB::getQueryLog();
                $lastQuery = end($queryLog)['query'];
                $lastBindings = end($queryLog)['bindings'];

                $custom_log->debug(__LINE__." : query for WhatsappCrm report including user --");
                $custom_log->debug("Query for WhatsappCrm report: " . $lastQuery);
                $custom_log->debug("Bindings: " . json_encode($lastBindings));

        }

        $csvarray = [];

            $sessionData = $request->session()->get('data');
            $userRole = $sessionData['userRole'] ?? '';
    
            // if ($userRole == 'super_admin') {

                $custom_log->debug(__LINE__." : put content in csv if userrole is super_admin-----");
    
                $csvarray[] = ['chat_id','Client','Campaign','assigned to','Customer name','Customer number', 'Assign status','Assigned by','Is closed','Closed by','Disposition','Sub_disposition','Remark','Assigned at','Created at'];
    
                foreach ($data as $row) {

                    $custom_log->debug("Original cust_unique_id: " .$row->cust_unique_id);

                    // Check if cust_unique_id starts with '91' and modify accordingly
                    $row->cust_unique_id = (strpos($row->cust_unique_id, '91') === 0) ? substr($row->cust_unique_id, 2) : $row->cust_unique_id;
                
                    // Log the modified cust_unique_id
                    $custom_log->debug("Modified cust_unique_id: " .$row->cust_unique_id);

                    // if ($row->assignby == '0' && $row->assignto != null) {
                    //     return 'System';
                    // }else if($row->assigned_by !== '0' || $row->assigned_by !== null && $row->assigned_to != null){
                    //     return $row->assigned_by;
                    // }

                    $csvarray[] = [
                        $row->chat_id,
                        $row->clientname,
                        $row->campaign_name,
                        $row->user_name,
                        $row->cust_name,
                        $row->cust_unique_id,
                        ($row->assignto == null || $row->assignto == '0' || empty($row->assignto)) ? 'No' : 'Yes',
                        ($row->assignby == '0' && $row->assignto != null) ? 'System' : $row->assigned_by,
                        ($row->is_closed == 1) ? 'No' : 'Yes',
                        $row->closed_by,
                        $row->dispo,
                        $row->sub_dispo,
                        $row->remark,
                        $row->assigned_at,
                        $row->created_at,
                        
                    ];
                }
    
            // }
            $custom_log->debug(__LINE__." : generate csv -----");
    
            $timestamp = date('Y_m_d_H_i_s');
            $custom_log->debug(__LINE__." : file created -----");
            $filename = 'WhatsappCRMReport_' . $timestamp . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\""
            ];
            $tempFilePath = tempnam(sys_get_temp_dir(), 'WhatsappCRMReport');
            $tempFile = fopen($tempFilePath, 'w');
    
            foreach ($csvarray as $row) {
                fputcsv($tempFile, $row);
            }
            $custom_log->debug(__LINE__." : put csv content in file -----");
    
            fclose($tempFile);
            $url = request()->root();
            $parsedUrl = parse_url($url);
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
            $filePath = storage_path("app/public/uploads/whatsappcrmreport/$filename");
            $file_url = $baseUrl . "/storage/app/public/uploads/whatsappcrmreport/" . $filename;
            $custom_log->debug(__LINE__." : File URL:----- " . $file_url);
            $custom_log->debug(__LINE__." : File Storage As: -----" . $filePath);
            $custom_log->debug(__LINE__." : WhatsappCRM Report Generated -----");
    
            rename($tempFilePath, $filePath);
            chmod($filePath, 0755);
    
            return response()->json(['download' => '1', 'file_url' => $file_url, 'file_name' => $filename], 200);



    }

    public function getChatsList(Request $request){
        if ($request->ajax()) {

            DB::enableQueryLog();
            $custom_log = Log::channel('custom_log');
            $custom_log->debug("\n\n\n--------------------Start getting ChatsList------------------------------------");
            $sessionData = session('data');
            $client_id = $sessionData['Client_id'];
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $sqlDateFrom = date('Y-m-d', strtotime($dateFrom));
            $sqlDateTo = date('Y-m-d', strtotime($dateTo));
            $user = $request->input('user');

            $logMessage = __LINE__ . " : getting request data for ChatsList --";
        $logMessage .= "\nClient ID: " . $client_id;
        $logMessage .= "\nDate From: " . $dateFrom;
        $logMessage .= "\nDate To: " . $dateTo;
        $logMessage .= "\nSQL Date From: " . $sqlDateFrom;
        $logMessage .= "\nSQL Date To: " . $sqlDateTo;
        $logMessage .= "\nUser: " . json_encode($user); 
        $custom_log->debug($logMessage);

            // if (isset($sessionData) && $sessionData['userRole'] == 'super_admin') { 
                
                if($user[0] === "All"){
                    $userslist = Users::where('client_id', $client_id)
                    ->whereIn('status', [0, 1])
                    ->where('role','=','user')->pluck('id')->toArray();

                    $queryLog = DB::getQueryLog();
                    $lastQuery = end($queryLog)['query'];
                    $lastBindings = end($queryLog)['bindings'];

                    $custom_log->debug(__LINE__." : if all users is there then --");
                    $custom_log->debug("Query for users: " . $lastQuery);
                    $custom_log->debug("Bindings: " . json_encode($lastBindings));

                    $data = DB::table('chats')
                    ->leftjoin('users', 'users.id', '=', 'chats.assigned_to')
                    ->leftjoin('clients', 'clients.id', '=', 'chats.client_id')
                    ->leftjoin('campaign', 'campaign.id', '=', 'chats.campaign_id')
                    ->leftJoin('users as closed_user', 'closed_user.id', '=', 'chats.closed_by')
                    ->leftJoin('users as assigning_user', 'assigning_user.id', '=', 'chats.assigned_by')
                    ->select(
                        'clients.name as clientname',
                        'campaign.name as campaign_name',
                        'users.name as user_name',
                        'users.role as role',
                        'chats.id as chat_id',
                        'chats.customer_name as cust_name',
                        'chats.cust_unique_id',
                        'chats.assigned_to as assignto',
                        'chats.assigned_by as assignby',
                        'chats.assigning_flag',
                        'chats.assigned_to',
                        'chats.assigned_at',
                        'assigning_user.name as assigned_by',
                        'chats.is_closed',
                        'closed_user.name as closed_by',
                        'chats.dispo',
                        'chats.sub_dispo',
                        'chats.remark',
                        'chats.created_at'
                    )
                    ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
                    ->whereIn('chats.is_closed',['1','2','3','4'])
                    ->where('clients.id', $client_id)
                    ->orderBy('chats.id','desc')
                    ->get();
        

                    $queryLog = DB::getQueryLog();
                    $lastQuery = end($queryLog)['query'];
                    $lastBindings = end($queryLog)['bindings'];

                    $custom_log->debug(__LINE__." : query for ChatsList including all user --");
                    $custom_log->debug("Query for ChatsList: " . $lastQuery);
                    $custom_log->debug("Bindings: " . json_encode($lastBindings));
                    
                }
                else{

                    $data = DB::table('chats')
                        ->leftjoin('users', 'users.id', '=', 'chats.assigned_to')
                        ->leftjoin('clients','clients.id', '=' ,'chats.client_id')
                        ->leftjoin('campaign', 'campaign.id' , '=','chats.campaign_id')
                        ->leftJoin('users as closed_user', 'closed_user.id', '=', 'chats.closed_by') 
                        ->leftJoin('users as assigning_user', 'assigning_user.id', '=', 'chats.assigned_by')  
                        ->select('clients.name as clientname', 'campaign.name as campaign_name','users.name as user_name', 'users.role as role','chats.id as chat_id','chats.customer_name as cust_name','chats.cust_unique_id','chats.assigned_to as assignto','chats.assigned_by as assignby','chats.assigning_flag','chats.assigned_to','chats.assigned_at','assigning_user.name as assigned_by','chats.is_closed','closed_user.name as closed_by','chats.dispo','chats.sub_dispo','chats.remark','chats.created_at')
                        ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
                        ->where('clients.id', $client_id)
                        ->whereIn('chats.assigned_to', $user)
                        ->orderBy('chats.id','desc')
                        ->get();

                    $queryLog = DB::getQueryLog();
                    $lastQuery = end($queryLog)['query'];
                    $lastBindings = end($queryLog)['bindings'];

                    $custom_log->debug(__LINE__." : query for ChatsList including particular selected user --");
                    $custom_log->debug("Query for ChatsList: " . $lastQuery);
                    $custom_log->debug("Bindings: " . json_encode($lastBindings));

                }
                $custom_log->debug(__LINE__." : data put in datatables -----");
                return DataTables::of($data)
                            ->make(true);
                } else {
                    return abort(404);
                }

            
            // }

    }

    public function getAgentActivityReport(Request $request){

        DB::enableQueryLog();
        $custom_log = Log::channel('custom_log');
        $custom_log->debug(__LINE__."\n\n\n--------------------Start The agent activity report ------------------------------------");
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $sqlDateFrom = date('Y-m-d', strtotime($dateFrom));
        $sqlDateTo = date('Y-m-d', strtotime($dateTo));
        $user = $request->input('user');

        $logMessage = __LINE__ . " : getting request data for agent activity report  --";
        $logMessage .= "\nClient ID: " . $client_id;
        $logMessage .= "\nDate From: " . $dateFrom;
        $logMessage .= "\nDate To: " . $dateTo;
        $logMessage .= "\nSQL Date From: " . $sqlDateFrom;
        $logMessage .= "\nSQL Date To: " . $sqlDateTo;
        $logMessage .= "\nUser: " . json_encode($user); 
        $custom_log->debug($logMessage);

        //echo "<pre>";print_r($campaign);die;  
            if($user[0] === "All"){
                //echo 2;
                $userslist = Users::where('client_id', $client_id)
                ->whereIn('status', [0, 1])
                ->where('role','=','user')->pluck('id')->toArray();

                $queryLog = DB::getQueryLog();
                    $lastQuery = end($queryLog)['query'];
                    $lastBindings = end($queryLog)['bindings'];

                    $custom_log->debug(__LINE__." : if all users is there then --");
                    $custom_log->debug("Query for user: " . $lastQuery);
                    $custom_log->debug("Bindings: " . json_encode($lastBindings));

                    $data = DB::table('break_log')
                    ->leftjoin('clients', 'break_log.client_id', '=', 'clients.id')
                    ->leftjoin('users', 'users.id', '=', 'break_log.user_id')
                    ->select(
                        'clients.name as clientname',
                        'users.id as userid',
                        'users.name as Name',
                        DB::raw('DATE(break_log.created_at) AS Date_log'),
                        DB::raw('MIN(break_log.created_at) as Latest_Login_time'),
                        DB::raw('MAX(break_log.updated_at) as Latest_Logout_time'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name NOT IN ("Ready","Soft","Login") THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Total_Break_Time'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Soft" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Soft_Break'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Login" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Login_duration'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name NOT IN ("Login") THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Duration'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Login" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END) - SUM(CASE WHEN break_log.break_name NOT IN ("Login") THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Ideal_duration'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Tea" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Tea'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Bio" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Bio'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Ready" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Ready'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Lunch" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Lunch_Break'),
                        DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Meeting" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Meeting')
                    )
                    ->whereRaw("DATE(break_log.created_at) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
                    ->where('break_log.client_id', $client_id)
                    ->whereIn('break_log.user_id', $userslist)
                    ->groupBy('users.id','clientname','break_log.client_id','Date_log') // Adjusted grouping
                    ->orderBy('Latest_Login_time', 'desc')
                    ->get();

                   
            }else{

                $data = DB::table('break_log')
                ->leftjoin('clients', 'break_log.client_id', '=', 'clients.id')
                    ->leftjoin('users', 'users.id', '=', 'break_log.user_id')
                ->select(
                    'clients.name as clientname',
                    'users.id as userid',
                    'users.name as Name',
                    DB::raw('DATE(break_log.created_at) AS Date_log'),
                    DB::raw('MIN(break_log.created_at) as Latest_Login_time'),
                    DB::raw('MAX(break_log.updated_at) as Latest_Logout_time'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name NOT IN ("Ready","Soft","Login") THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Total_Break_Time'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Soft" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Soft_Break'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Login" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Login_duration'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name NOT IN ("Login") THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Duration'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Login" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END) - SUM(CASE WHEN break_log.break_name NOT IN ("Login") THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) AS Ideal_duration'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Tea" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Tea'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Bio" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Bio'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Ready" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Ready'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Lunch" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Lunch_Break'),
                    DB::raw('SEC_TO_TIME(SUM(CASE WHEN break_log.break_name = "Meeting" THEN TIME_TO_SEC(break_log.break_time) ELSE 0 END)) as Meeting')
                )
                ->whereRaw("DATE(break_log.created_at) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo])
                ->where('break_log.client_id', $client_id)
                ->whereIn('break_log.user_id', $user)
                ->groupBy('users.id', 'clientname','break_log.client_id','Date_log') // Adjusted grouping
                ->orderBy('Latest_Login_time', 'desc')
                ->get();

                $queryLog = DB::getQueryLog();
                $lastQuery = end($queryLog)['query'];
                $lastBindings = end($queryLog)['bindings'];

                $custom_log->debug(__LINE__." : query for agent activity report including user --");
                $custom_log->debug("Query for agent activity report:" . $lastQuery);
                $custom_log->debug("Bindings: " . json_encode($lastBindings));

            }

            $csvarray = [];

            $sessionData = $request->session()->get('data');
            $userRole = $sessionData['userRole'] ?? '';
    
            // if ($userRole == 'super_admin') {

                $custom_log->debug(__LINE__." : put content in csv if userrole is super_admin-----");
    
                $csvarray[] = ['Client','Agent_name','Login_time','Soft_Break','Total_Break_Time','Tea','Bio','Lunch_Break','Meeting','Login_duration','Ready','Ideal_duration',];
    
                foreach ($data as $row) {
                    $csvarray[] = [
                        $row->clientname,
                        // $row->userid,
                        $row->Name,
                        $row->Latest_Login_time,
                        $row->Soft_Break,
                        $row->Total_Break_Time,
                        $row->Tea,
                        $row->Bio,
                        $row->Lunch_Break,
                        $row->Meeting,
                        $row->Login_duration,
                        $row->Ready,
                        $row->Ideal_duration,

                    ];
                }
    
            // }   
            $custom_log->debug(__LINE__." : generate csv -----");
    
            $timestamp = date('Y_m_d_H_i_s');
            $filename = 'AgentActivityReport_' . $timestamp . '.csv';
            $custom_log->debug(__LINE__." : file created -----");
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\""
            ];
            $tempFilePath = tempnam(sys_get_temp_dir(), 'agentActivityReport');
            $tempFile = fopen($tempFilePath, 'w');
    
            foreach ($csvarray as $row) {
                fputcsv($tempFile, $row);
            }
            $custom_log->debug(__LINE__." : put csv content in file -----");
            fclose($tempFile);
            $url = request()->root();
            $parsedUrl = parse_url($url);
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
            $filePath = storage_path("app/public/uploads/loginreport/$filename");
            //$file_url = $baseUrl . "/public/storage/uploads/rcbulk/s" . $filename;
            $file_url = $baseUrl . "/storage/app/public/uploads/loginreport/" . $filename;
            $custom_log->debug(__LINE__." : file storage as -----");
    
            $custom_log->debug(__LINE__." : agent activity report generate -----");
            rename($tempFilePath, $filePath);
            chmod($filePath, 0755);
            return response()->json(['download' => '1', 'file_url' => $file_url, 'file_name' => $filename], 200);
            

    }

}
