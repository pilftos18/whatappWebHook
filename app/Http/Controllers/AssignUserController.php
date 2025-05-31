<?php

namespace App\Http\Controllers;
use App\Models\Users;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Clients;
use App\Models\Campaign;
use App\Models\MappedUsers;
use App\Models\Chats;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Rules\PasswordPolicy;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Yajra\DataTables\Buttons\Button;
use Yajra\DataTables\Buttons\DatatableButton;
use App;
class AssignUserController extends Controller
{
    public function getCampaignList()
    {
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $campaigns = Campaign::where('client_id', $client_id)->whereIn('status', [0,1])->pluck('name', 'id'); // Assuming 'name' is the field for the client name and 'id' is the field for the client ID
        return response()->json($campaigns);

    }       

    public function getUserList(Request $request){
        
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $campaign = $request->input('campaign');

        $queue = Campaign::where('id',$campaign)->where('client_id',$client_id)->pluck('queue');
        $stringValue = $queue[0] ?? 0;
        $values = explode(',', trim($stringValue, '[]'));
        $queueArr = array_map('strval', $values);

        $users = Users::where('users.client_id','=',$client_id)
        ->leftjoin('queue_mapping', 'queue_mapping.user_id', '=', 'users.id')
        ->where('queue_mapping.queue_id','=', $queueArr)
        ->where('users.login_status','2')
        ->pluck('users.id','users.name');

        return response()->json($users);
    }

    public function getUserAssignList(Request $request){
        if ($request->ajax()) {
            DB::enableQueryLog();
            $custom_log = Log::channel('custom_log');
            $custom_log->debug("\n\n\n--------------------Start getting manual user assign list-------------");

            $sessionData = session('data');
            $client_id = $sessionData['Client_id'];
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $sqlDateFrom = date('Y-m-d', strtotime($dateFrom));
            $sqlDateTo = date('Y-m-d', strtotime($dateTo));
            $campaign = $request->input('campaign');
            $assign = $request->input('assigning');

            $logMessage = __LINE__ . " : getting request data for manual user assign list --";
            $logMessage .= "\nClient ID: " . $client_id;
            $logMessage .= "\nDate From: " . $dateFrom;
            $logMessage .= "\nDate To: " . $dateTo;
            $logMessage .= "\nSQL Date From: " . $sqlDateFrom;
            $logMessage .= "\nSQL Date To: " . $sqlDateTo;
            $logMessage .= "\ncampaign: " . json_encode($campaign); 
            $logMessage .= "\nassign: " . json_encode($assign); 
            $custom_log->debug($logMessage);
            //$all_value = $campaign[0];
    
            if (isset($sessionData) && $sessionData['userRole'] == 'super_admin' || $sessionData['userRole'] == 'manager') {
                $data = DB::table('chats')
                    ->leftjoin('users', 'users.id', '=', 'chats.assigned_to')
                    ->join('campaign', 'chats.campaign_id', '=', 'campaign.id')
                    ->leftJoin('users as assigning_user', 'assigning_user.id', '=', 'chats.assigned_by')
                    ->select(
                        'chats.id',
                        'chats.assigned_to as assignto',
                        DB::raw('users.name as assigned_to'),
                        'chats.customer_name',
                        'chats.cust_unique_id',
                        'chats.assigned_at',
                        'chats.assigned_by as assignby',
                        'assigning_user.name as assigned_by',
                        'chats.status',
                        'chats.created_at',
                        DB::raw('campaign.name as campaignname')
                    )
                    ->where('chats.client_id', $client_id)
                    ->where('chats.campaign_id', $campaign)
                    ->where('chats.is_closed' ,'!=','2')
                    ->where('chats.assigning_flag','=','2')
                    ->whereRaw("DATE(chats.created_at) BETWEEN ? AND ?", [$sqlDateFrom, $sqlDateTo]);
            
                    if ($assign == 'assign') {
                        $data->whereNotNull('chats.assigned_to');
                    } elseif ($assign == 'unassign') {
                        $data->whereNull('chats.assigned_to');
                    }

                $queryLog       =  DB::getQueryLog();
                $lastQueryEntry = end($queryLog);

                // $lastQuery = end($queryLog)['query'];
                // $lastBindings = end($queryLog)['bindings'];
                // $custom_log->debug(__LINE__." : query for manual user assign --");
                // $custom_log->debug("Query for manual user assign: " . $lastQuery);
                // $custom_log->debug("Bindings: " . json_encode($lastBindings));

                if ($lastQueryEntry !== false) {
                    $lastQuery = $lastQueryEntry['query'];
                    // print_r($lastQuery);
                    // die();
                    $lastBindings = $lastQueryEntry['bindings'];
                    $custom_log->debug(__LINE__ . " : query for manual user assign --");
                    $custom_log->debug("Query: " . $lastQuery);
                    $custom_log->debug("Bindings: " . json_encode($lastBindings));
                }

                $custom_log->debug(__LINE__." : data put in datatables -----");
                return DataTables::of($data)
                    ->make(true);
            } else {
                return abort(404);
            }
        }
    }

    public function setAssignUser(Request $request){
        
            $sessionData = session('data');
            DB::enableQueryLog();
            $custom_log = Log::channel('custom_log');
            $custom_log->debug("\n\n\n-------------------- manual user assign log -------------");
            $client_id = $sessionData['Client_id'];
            $assignerid = $sessionData['userID'];
            $userid = $request->input('userid');
            $selectedIds = $request->input('selectedIds');
            $logMessage = __LINE__ . " : getting request data for manual user assign log  --";
            $logMessage .= "\nClient ID: " . $client_id;
            $logMessage .= "\nassigner_id: " . $assignerid;
            $logMessage .= "\nuserid: " . $userid;
            $custom_log->debug($logMessage);
            $custom_log->debug(__LINE__." : start updating data in chats table  --");

            foreach ($selectedIds as  $value) {
                Chats::where('id', $value)->update(['assigned_to' => $userid ,'assigned_by' => $assignerid ,'assigned_at' => now()]);
                $log = __LINE__ . " : data inserted in chats table --";
                $log .= "\nchat_id: " . $value;
                $log .= "\nassign_to: " . $userid;
                $log .= "\nassigned_by: " .$assignerid;
                $log .= "\nassigned_at: " . now();
                $custom_log->debug($log);

                DB::table('manualassign_log')->insert([
                    'chat_id' => $value,
                    'assigned_to' => $userid,
                    'assigned_by' => $assignerid,
                    'assigned_at' => now(),
                    'created_at' => now(),
                ]);
                $logs = __LINE__ . " : data inserted in manualassign_log table --";
                $logs .= "\nchat_id: " . $value;
                $logs .= "\assigned_to: " . $userid;
                $logs .= "\nassigned_by: " .$assignerid;
                $logs .= "\nassigned_at: " . now();
                $custom_log->debug($logs);

            }

            $custom_log->debug(__LINE__." : data inserted in chats and manualassign_log table  --");

            return response()->json('user assign successfully');
    }


}
