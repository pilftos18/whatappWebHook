<?php

namespace App\Http\Controllers;
use App\Models\Campaign;
use App\Models\Breaks;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BreakController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        //$data = Breaks::where('client_id',$client_id)->whereIn('deleted_at', [1,0])->latest()->get();
        $data = Breaks::leftJoin('campaign', 'campaign.id', '=', 'breaks.campaign_id')
            ->where('breaks.client_id', $client_id)
            ->whereIn('breaks.status', [1,0])
            ->whereIn('breaks.deleted_at', [1,0])
            ->select('campaign.name as campaignname', DB::raw('GROUP_CONCAT(breaks.name) as names'), 'breaks.campaign_id')
            ->groupBy('breaks.campaign_id')
            ->get();
        //echo "<pre>";print_r($data);exit;
        return view ('break.index',compact('data'));
    }   

    public function getBreakList(Request $request)
    {
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        if ($request->ajax()) {

            // $data = Breaks::leftJoin('campaign', 'campaign.id', '=', 'breaks.campaign_id')
            // ->where('breaks.client_id',$client_id)
            // ->select('campaign.name as campaignname', 'breaks.*')
            // ->get();

            $data = Breaks::leftJoin('campaign', 'campaign.id', '=', 'breaks.campaign_id')
            ->where('breaks.client_id', $client_id)
            ->whereIn('breaks.status', [1,0])
            ->whereIn('breaks.deleted_at', [1,0])
            ->select('campaign.name as campaignname', DB::raw('GROUP_CONCAT(breaks.name) as names'), 'breaks.campaign_id')
            ->groupBy('breaks.campaign_id')
            ->get();

            //return($data);exit;
            //$data = Breaks::where('client_id',$client_id)->whereIn('deleted_at', [1,0])->latest()->get();
            return DataTables::of($data)
                ->addColumn('action', function($row){
                    $editUrl = route('break.edit', $row->campaign_id);
                    $deleteUrl = route('break.delete',$row->campaign_id);
                    $btn = '<a href="'.$editUrl.'" class="btn btn-sm btn-info">Edit</a>';
                    $btn .= '<a href="'.$deleteUrl.'" class="btn btn-sm btn-info status-select" key-value = "'.$row->campaign_id.'">Delete</a>';
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
        $campaignlist = Campaign::where('client_id',$client_id)->whereIn('deleted_at', [1,0])->pluck('name', 'id');
        $breakcampaign = Breaks::whereIn('status', [1,0])->groupBy('campaign_id')->pluck('campaign_id');

        return view ('break.create', compact('campaignlist','breakcampaign'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {   
        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n----create break -----");
        // $data = $request->all();
        // echo "<pre>";print_r($data);exit;
        $request->validate([
            'campaign' => 'required',
            'VaaniCampaignBreak.break.*' => 'required',
            'VaaniCampaignBreak.status.*' => 'required',
        ], [
            'campaign.required' => 'The campaign name is required.',
            'VaaniCampaignBreak.break.*.required' => 'The break is missing.',
            'VaaniCampaignBreak.status.*.required' => 'The status is required.',
        ]);

        $custom_log->debug(__LINE__."\n\n\n---- validations are proper --------");
        $campaignId = $request->input('campaign');
        $breaks = $request->input('VaaniCampaignBreak.break');
        $statuses = $request->input('VaaniCampaignBreak.status');
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $custom_log->debug(__LINE__."\n\n\n----requested data are proper-----");

        // Assuming you have a CampaignBreak model
        foreach ($breaks as $key => $breakName) {
            $status = $statuses[$key];

            $break = new Breaks();
            $break->client_id = $client_id;
            $break->campaign_id = $campaignId;
            $break->name = $breakName;
            $break->status = $status;
            $break->deleted_at = 1;
            $break->created_at = now();
            $ip = $request->ip();
            $break->created_by = 1;
            $break->save();
        }
        $ip = $request->ip();
        $custom_log->debug(__LINE__."\n\n\n----break create by --------".$ip);

    return redirect()->route('break.index')->with('success', 'Breaks created successfully');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $campaignlist = Campaign::where('client_id',$client_id)->whereIn('deleted_at', [1,0])->pluck('name', 'id');
        $breaks = Breaks::where('campaign_id',$id)->whereIn('deleted_at', [1,0])->latest()->get();
        $campaign_id = $id ;
        //echo"<pre>";print_r($breaks);exit;

        return view ('break.edit', compact('campaignlist','breaks','campaign_id'));
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
        // $data = $request->all();
        // echo "<pre>";print_r($data);exit;
        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n----update break -----");

        $request->validate([
            'VaaniCampaignBreak.break.*' => 'required',
            'VaaniCampaignBreak.status.*' => 'required',
        ], [
            'VaaniCampaignBreak.break.*.required' => 'The break is missing.',
            'VaaniCampaignBreak.status.*.required' => 'The status is required.',
        ]);
        $custom_log->debug(__LINE__."\n\n\n---- validations are proper --------");
        //$breaks = Breaks::where('campaign_id', $id)->get();
        //$breaks = Breaks::find($id);s
        //echo "<pre>";print_r($breaks);exit;

        $breaks = $request->input('VaaniCampaignBreak.break');
        $statuses = $request->input('VaaniCampaignBreak.status');
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $custom_log->debug(__LINE__."\n\n\n----requested data are proper-----");

        // Update the mapped users
        // $mappedbreaksData = [];
        // foreach ($selectedUsers as $userId) {
        //     $mappedbreaksData[] = [
        //         'campaign_id' => $id, // Use the updated campaign_id
        //         'client_id' => $client_id,
        //         'user_id' => $userId,
        //         'status' => 1,
        //         'created_at' => now(),
        //         'created_by' => 1,
        //     ];
        // }

        $mappedbreaksData = [];
        foreach ($breaks as $key => $breakName) {
            $status = $statuses[$key];
            $mappedbreaksData[] = [
                'campaign_id' => $id, 
                'client_id' => $client_id,
                'name' => $breakName,
                'status' => $status,
                'deleted_at' => 1,
                'created_at' => now(),
                'created_by' => 1,
                'updated_at' => now(),
                'updated_by' => 1,
            ];
            Breaks::where('campaign_id', $id)->delete();
            Breaks::insert($mappedbreaksData);
        }
        $ip = $request->ip();
        $custom_log->debug(__LINE__."\n\n\n---- delete the previous breaks and updated new break by --------".$ip);

        return redirect()->route('break.index')->with('success', 'Breaks updated successfully');
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
        $custom_log->debug(__LINE__."\n\n\n----delete break -----");
        $datas = Breaks::where('campaign_id',$id)->get();
        $ip = $request->ip();
        //echo "<pre>";print_r($data);exit;
        //$data = Breaks::find($id);
        if($datas){
            foreach ($datas as $data) {
                $data->deleted_at = 2;
                $data->status = 2;
                $data->save();
            }  
            $custom_log->debug(__LINE__."\n\n\n-----set break status 2 by-----".$ip);
            $custom_log->debug(__LINE__."\n\n\n-----delete break by-----".$ip);
            return redirect('break')->with('success', 'Breaks deleted successfully.');
        } else {
            return redirect('break')->with('error', 'Breaks not found.');
        }
    }

    public function delete(Request $request,$id)
    {   
        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n----delete break -----");
        $datas = Breaks::where('campaign_id',$id)->get();
        $ip = $request->ip();
        //echo "<pre>";print_r($datas);exit;
        //$data = Breaks::find($id);
        if($datas){
            foreach ($datas as $data) {
                $data->deleted_at = 2;
                $data->status = 2;
                $data->save();
            } 
            $custom_log->debug(__LINE__."\n\n\n-----set break status 2 by-----".$ip); 
            $custom_log->debug(__LINE__."\n\n\n-----delete break by-----".$ip);
            return redirect('break')->with('success', 'Breaks deleted successfully.');
        } else {
            return redirect('break')->with('error', 'Breaks not found.');
        }
    }
}
