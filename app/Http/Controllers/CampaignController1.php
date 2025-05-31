<?php

namespace App\Http\Controllers;
use App\Models\Clients;
use App\Models\Users;
use App\Models\Queue;
use App\Models\Campaign;
use App\Models\CallWindow;
use App\Models\MappedUsers;
use App\Models\DispositionPlan;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        $sessionData = session('data');
        // print_r($sessionData);exit;
        $client_id = $sessionData['Client_id'];
        $campaigns = Campaign::where('campaign.client_id',$client_id)
        ->leftjoin('templates', 'templates.id', '=', 'campaign.auto_reply_id')
        ->select('templates.name as templetename','campaign.*')
        ->whereIn('deleted_at', [1,0])
        ->latest()
        ->get();
        
        //Campaign::where('client_id',$client_id)->whereIn('deleted_at', [1,0])->latest()->get();

        // return view('client.index', compact('client'));
        return view ('campaign.index', compact('campaigns'));
    }

    public function getCampaignList(Request $request)
    {
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];

        if ($request->ajax()) {
            $data = Campaign::where('campaign.client_id',$client_id)
        ->leftjoin('templates', 'templates.id', '=', 'campaign.auto_reply_id')
        ->select('templates.name as templetename','campaign.*')
        ->whereIn('deleted_at', [1,0])
        ->latest()
        ->get();
            
            //Campaign::where('client_id',$client_id)->whereIn('deleted_at', [1,0])->latest()->get();
            //$data = Clients::all();
            //echo"<pre>"; print_r($data);die;
            return DataTables::of($data)
                ->addColumn('action', function($row){
                    $editUrl = route('campaign.edit', $row->id);
                    $deleteUrl = route('campaign.delete',$row->id);
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

        // $queue = Queue::where('client_id', $client_id)->whereIn('status', [0, 1])->pluck('queuename', 'id');
        $queue = Queue::where('client_id', $client_id)->whereNull('campaign_id')->whereIn('status', [0, 1])->pluck('queuename', 'id');
        // $users = Users::where('client_id', $client_id)
        // ->where('role', 'user')
        // ->whereIn('status', [0, 1])
        // ->pluck('queuename', 'id');
        $campaignlist = Campaign::where('client_id', $client_id)
        ->whereIn('status', [0,1,2])
        ->pluck('name');

        $workingdays = DB::table('workingdays')
        ->whereIn('status', [0, 1])
        ->pluck('dayname', 'daycode');

        $templetelist = DB::table('templates')
        ->where('client_id', $client_id)
        ->whereIn('status', [0, 1])
        ->pluck('name', 'id');

        $plandispo =  DispositionPlan::where('client_id',$client_id)->whereIn('status', [1,0])->pluck('planname','id');

        //return view ('campaign.create');
        return view ('campaign.create', compact('queue','campaignlist','templetelist','plandispo','workingdays'));
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
        $custom_log->debug(__LINE__."\n\n\n------Start The campaign creation------------");
        // $selectedUsers = $request->input('mappeduser');
        //dd($selectedUsers);exit;

        // $data = $request->all();
        // echo "<pre>";print_r($data);exit;

        $request->validate([
            'campaign' => 'required',
            'mobileno' => 'required|numeric',
            'distrubution' => 'required',
            'status' => 'required',
            'interperagent' => 'required',
            'sla' => 'required',
            'disposition' => 'required',
            'fromTime' => 'required',
            'toTime' => 'required',
            // 'Autoreply' => 'required',
        ], [
            'campaign.required' => 'The campaign name is required.',
            'distrubution.required' => 'The distrubution is required.',
             'mobileno.required' => 'The mobileno field is required.',
            'mobileno.numeric' => 'Please enter a numeric value for mobileno.',
            'interperagent.required' => 'The interaction per agent is required.',
            'sla.required' => 'The sla field is required.',
            'disposition.required' => 'The disposition field is required.',
            'fromTime.required' => 'The call_window_from field is required.',
            'toTime.required' => 'The call_window_to field is required.',
            // 'Autoreply.required' => 'Autoreply template is required',
        ]);
        $custom_log->debug(__LINE__."\n\n\n---- validations are proper --------");

        $from = trim($request->input('fromTime'));
        $to = trim($request->input('toTime'));
        $from = str_replace(' ', '', $from);
        $to = str_replace(' ', '', $to);
        $from = str_replace(':', '', $from);
        $to = str_replace(':', '', $to);


        $campaign = new Campaign();
        $campaign->client_id = $client_id;       
        $campaign->name = $request->input('campaign');
        $campaign->wp_number = '91'.$request->input('mobileno');
        $campaign->dist_type = $request->input('distrubution');
        if($campaign->dist_type == 'auto'){
            $campaign->dist_method = $request->input('dismethod');
        }else{
            $campaign->dist_method = '0';
        }
        $campaign->interaction_per_user = $request->input('interperagent');
        $campaign->sla = $request->input('sla');
        $campaign->call_window_from = $from;
        $campaign->call_window_to = $to;
        $campaign->disposition_id = $request->input('disposition');
        $campaign->auto_reply_id = $request->input('Autoreply');
        $selectedqueueIds = $request->input('mappedqueue');
        //echo "<pre>";print_r($selectedqueueIds);exit;

        // if($selectedqueueIds[0] == 'All'){
        //     $queue = Queue::where('client_id', $client_id)->whereIn('status', [0, 1])->pluck('id')->toArray();
            
        //     $queueString = implode(',', $queue);
        // }else{
            $queueString = implode(',', $selectedqueueIds);
        // }

        $campaign->queue = $queueString;
        //////////////////////////////////////////////
        $selectedworkingdays = $request->input('workingdays');

        if($selectedworkingdays[0] == 'All'){
            $day = DB::table('workingdays')
            ->whereIn('status', [0, 1])
            ->pluck('daycode')->toArray();
            
            $workString = implode(',', $day);
        }else{
            $workString = implode(',', $selectedworkingdays);
        }
        $campaign->working_days =  $workString;
        $holidayvalue = $request->input('holidayname');
        if($holidayvalue === ''){
            $campaign->holiday_name = $request->input('holidayname');
            $campaign->holiday_start  = null;
            $campaign->holiday_end  = null;
        }else{
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $sqlDateFrom = date('Y-m-d', strtotime($dateFrom));
            $sqlDateTo = date('Y-m-d', strtotime($dateTo));
            $campaign->holiday_name = $request->input('holidayname');
            $campaign->holiday_start  = $sqlDateFrom;
            $campaign->holiday_end  = $sqlDateTo;
        }
        $campaign->status = $request->input('status');
        $campaign->deleted_at = 1;
        $campaign->created_at = now();
        $ip = $request->ip();
        $campaign->created_by = 1;
        $custom_log->debug(__LINE__."\n\n\n----requested data are proper-----");
        $campaign->save();

        $custom_log->debug(__LINE__."\n\n\n----Campaign data inserted-----");

        $campaign_id = $campaign->id;

        if(!empty($campaign_id))
        {   

            $CallWindow = new CallWindow();
            $CallWindow->campaign_id = $campaign_id;
            $CallWindow->client_id = $client_id;
            $CallWindow->title = $from.'TO'.$to;
            $CallWindow->from = $from;
            $CallWindow->to = $to;
            $CallWindow->status = 1;
            $CallWindow->created_at = now();
            $ip = $request->ip();
            $CallWindow->created_by = 1;
            $custom_log->debug(__LINE__."\n\n\n----Call window data inserted-----");
            $CallWindow->save();

            $queueupdate = Queue::whereIn('id', $selectedqueueIds)->whereIn('status', [0, 1])->where('client_id',$client_id)->get();

            // // Assuming you have a $campaignId variable with the desired campaign ID
            foreach ($queueupdate as $campaignset) {
                $campaignset->update(['campaign_id' => $campaign_id]);
            }

            // $custom_log->debug(__LINE__."\n\n\n----campaign id set in queue-----");

            $custom_log->debug(__LINE__."\n\n\n----campaign id set in mapped queue-----");

            $custom_log->debug(__LINE__."\n\n\n----queue are mapped as per campaign-----");

            $custom_log->debug(__LINE__."\n\n\n----Campaign create by --------".$ip);
            return redirect()->route('campaign.index')->with('success', 'Campaign, Call window and user map successfully.');
        }
        else{

            return redirect()->route('campaign.index')->with('error', 'Campaign not found.');
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return view ('campaign.create');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {   
        $campaign = Campaign::find($id);
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];

        $queue = Queue::where(function ($query) use ($id, $client_id) {
            $query->where('client_id', $client_id)
                  ->where(function ($query) use ($id) {
                      $query->where('campaign_id', $id)
                            ->orWhereNull('campaign_id');
                  });
        })
        ->whereIn('status', [0, 1])
        ->pluck('queuename', 'id');

        $workingdays = DB::table('workingdays')
        ->whereIn('status', [0, 1])
        ->pluck('dayname', 'daycode');

        $templetelist = DB::table('templates')
        ->where('client_id', $client_id)
        ->whereIn('status', [0, 1])
        ->pluck('name', 'id');

        $plandispo =  DispositionPlan::where('client_id',$client_id)->whereIn('status', [1,0])->pluck('planname','id');


        return view ('campaign.edit', compact('campaign','queue','templetelist','plandispo','workingdays'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function update(Request $request, $id)
    // {   
    //     $campaign = Campaign::find($id);
    //     return view ('campaign.index');
    // }
    public function update(Request $request, $id)
{
    $sessionData = session('data');
    $client_id = $sessionData['Client_id'];
    $custom_log = Log::channel('adminactivity');
    $custom_log->debug("\n\n\n----\-----------------------/-----");
    $custom_log->debug(__LINE__."\n\n\n------campaign update------------");
    // $selectedqueueIds = $request->input('mappedqueue');

    // $data = $request->all();
    //     echo "<pre>";print_r($data);exit;

    // Validate the request data
    $request->validate([
        'campaign' => 'required',
        'distrubution' => 'required',
        'status' => 'required',
        'interperagent' => 'required',
        'sla' => 'required',
        'disposition' => 'required',
        'fromTime' => 'required',
        'toTime' => 'required',
        // 'Autoreply' => 'required',
        'mobileno' => 'required|numeric',
    ], [
        'campaign.required' => 'The campaign name is required.',
        'distrubution.required' => 'The distribution is required.',
        'mobileno.required' => 'The mobileno field is required.',
        'mobileno.numeric' => 'Please enter a numeric value for mobileno.',
        'interperagent.required' => 'The interaction per agent is required.',
        'sla.required' => 'The sla field is required.',
        'disposition.required' => 'The disposition field is required.',
        'fromTime.required' => 'The call_window_from field is required.',
        'toTime.required' => 'The call_window_to field is required.',
        // 'Autoreply.required' => 'Autoreply template is required.',
    ]);
    $custom_log->debug(__LINE__."\n\n\n---- validations are proper --------");

    $from = trim($request->input('fromTime'));
    $to = trim($request->input('toTime'));
    $from = str_replace(' ', '', $from);
    $to = str_replace(' ', '', $to);
    $from = str_replace(':', '', $from);
    $to = str_replace(':', '', $to);
    $ip = $request->ip();

    // Find the campaign to update
    $campaign = Campaign::where('id', $id)->first();

    if ($campaign) {
        $campaign->queue = null;
        $campaign->working_days = null;
        $campaign->name = $request->input('campaign');
        $campaign->wp_number = '91'.$request->input('mobileno');
        $campaign->dist_type = $request->input('distrubution');
        if ($campaign->dist_type == 'auto') {
            $campaign->dist_method = $request->input('dismethod');
        } else {
            $campaign->dist_method = '0';
        }
        $campaign->interaction_per_user = $request->input('interperagent');
        $campaign->sla = $request->input('sla');
        $campaign->call_window_from = $from;
        $campaign->call_window_to = $to;
        $campaign->disposition_id = $request->input('disposition');
        $campaign->auto_reply_id = $request->input('Autoreply');
        $selectedqueueIds = $request->input('mappedqueue');

        Queue::where('campaign_id', $id)->update(['campaign_id' => null]);
        
        // if($selectedqueueIds[0] == 'All'){
        //     $queue = Queue::where('client_id', $client_id)->whereIn('status', [0, 1])->pluck('id')->toArray();
            
        //     $queueString = implode(',', $queue);
        // }else{
            $queueString = implode(',', $selectedqueueIds);
        // }   
        $campaign->queue = $queueString;

        $selectedworkingdays = $request->input('workingdays');

        if($selectedworkingdays[0] == 'All'){
            $day = DB::table('workingdays')
            ->whereIn('status', [0, 1])
            ->pluck('daycode')->toArray();
            
            $workString = implode(',', $day);
        }else{
            $workString = implode(',', $selectedworkingdays);
        }
        $campaign->working_days =  $workString;

        $holidayvalue = $request->input('holidayname');
        if($holidayvalue === ''){
            $campaign->holiday_name = $request->input('holidayname');
            $campaign->holiday_start  = null;
            $campaign->holiday_end  = null;
        }else{
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $sqlDateFrom = date('Y-m-d', strtotime($dateFrom));
            $sqlDateTo = date('Y-m-d', strtotime($dateTo));
            $campaign->holiday_name = $request->input('holidayname');
            $campaign->holiday_start  = $sqlDateFrom;
            $campaign->holiday_end  = $sqlDateTo;
        }
        $campaign->status = $request->input('status');
        $custom_log->debug(__LINE__."\n\n\n----requested data are proper-----");
        $campaign->save();

        $custom_log->debug(__LINE__."\n\n\n----Campaign data updated-----");

        // Update the associated CallWindow if needed
        $callWindow = CallWindow::where('campaign_id', $id)->first();

        if ($callWindow) {
            $callWindow->title = $from.'TO'.$to;
            $callWindow->from = $from;
            $callWindow->to = $to;
            $custom_log->debug(__LINE__."\n\n\n----Call window data updated-----");
            $callWindow->save();
        }

        $queueupdate = Queue::whereIn('id', $selectedqueueIds)->whereIn('status', [0, 1])->where('client_id',$client_id)->get();

        foreach ($queueupdate as $campaignset) {
            $campaignset->update(['campaign_id' => $id]);
        }
  
        $custom_log->debug(__LINE__."\n\n\n----Users are mapped as per campaign-----");
            
        $custom_log->debug(__LINE__."\n\n\n----Campaign updated by --------".$ip);

        return redirect()->route('campaign.index')->with('success', 'Campaign, Call window, and user map updated successfully.');
    }
    else{

        return redirect()->route('campaign.index')->with('error', 'Campaign not found.');
    }

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
        $custom_log->debug(__LINE__."\n\n\n----delete campaign -----");
        $campaign = Campaign::find($id);
        $ip = $request->ip();


        if ($campaign) {
            // Update the campaign record
            $campaign->deleted_at = 2;
            $campaign->status = 2;
            $custom_log->debug(__LINE__."\n\n\n-----set campaign status 2 by-----".$ip);
            $client->save();

            // Delete related license details
            CallWindow::where('campaign_id', $id)->update(['status' => 2]);

            MappedUsers::where('campaign_id', $id)
                ->whereNull('campaign_id')
                ->whereIn('status', [0, 1])
                ->update(['campaign_id' => null]);

                Queue::where('campaign_id', $id)->update(['campaign_id' => null]);


            // Delete related moduleSM records
            // MappedUsers::where('campaign_id', $id)->update(['status' => 2]);
            $custom_log->debug(__LINE__."\n\n\n-----set CallWindow status 2 by-----".$ip);
            // $custom_log->debug(__LINE__."\n\n\n-----set MappedUsers status 2 by-----".$ip);
            $custom_log->debug(__LINE__."\n\n\n-----delete campaign by-----".$ip);

            return redirect('campaign')->with('success', 'Campaign and related records deleted successfully.');
        } else {
            return redirect('campaign')->with('error', 'Campaign not found.');
        }
    }

    public function delete(Request $request,$id)
    {
        $campaign = Campaign::find($id);
        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n----delete campaign -----");
        $ip = $request->ip();


        if ($campaign) {
            // Update the campaign record
            $campaign->deleted_at = 2;
            $campaign->status = 2;
            $custom_log->debug(__LINE__."\n\n\n-----set campaign status 2 by-----".$ip);
            $campaign->save();

            // Delete related license details
            CallWindow::where('campaign_id', $id)->update(['status' => 2]);

            MappedUsers::where('campaign_id', $id)
            ->whereNull('campaign_id')
            ->whereIn('status', [0, 1])
            ->update(['campaign_id' => null]);
            
            Queue::where('campaign_id', $id)->update(['campaign_id' => null]);

            // Delete related moduleSM records
            // MappedUsers::where('campaign_id', $id)->update(['status' => 2]);
            $custom_log->debug(__LINE__."\n\n\n-----set CallWindow status 2 by-----".$ip);
            // $custom_log->debug(__LINE__."\n\n\n-----set MappedUsers status 2 by-----".$ip);
            $custom_log->debug(__LINE__."\n\n\n-----delete campaign by-----".$ip);

            return redirect('campaign')->with('success', 'Campaign and related records deleted successfully.');
        } else {
            return redirect('campaign')->with('error', 'Campaign not found.');
        }
    }
}
