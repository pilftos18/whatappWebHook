<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\DispositionPlan;
use App\Models\AllDispositions;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class DispositionController extends Controller
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
        $dispo = DispositionPlan::where('client_id',$client_id)->whereIn('is_deleted',[0,1])->whereIn('status', [1,0,2])->latest()->get();

        return view ('disposition.index',compact('dispo'));
    }

    public function getDispositionList(Request $request){

        $sessionData = session('data');
        // print_r($sessionData);exit;
        $client_id = $sessionData['Client_id'];

        if ($request->ajax()) {
            $data = DispositionPlan::where('client_id',$client_id)->whereIn('is_deleted',[0,1])->whereIn('status', [1,0,2])->latest()->get();
            return DataTables::of($data)
                ->addColumn('action', function($row){
                    $editUrl = route('disposition.edit', $row->id);
                    $deleteUrl = route('disposition.delete',$row->id);
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
        return view ('disposition.create');
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
        //  $data = $request;
        $data = $request->all();

        $request->validate([
            'VaaniDispositionPlan.name' => 'required|string|unique:dispositionplan,planname',
            'VaaniDispositions.disposition_name.*' => 'required|string',
            'VaaniDispositions.short_code.*' => 'required|string',
            'VaaniDispositions.type.*' => 'required|numeric',
        ], [
            'VaaniDispositionPlan.name.required' => 'The plan name is required.',
            'VaaniDispositions.disposition_name.required' => 'The disposition name is required.',
            'VaaniDispositions.short_code.required' => 'The short code is required.',
            'VaaniDispositions.type.required' => 'The type is required.',
        ]);

        // Save VaaniDispositionPlan
        $planModel = new DispositionPlan();
        $planModel->client_id = $client_id;
        $planModel->planname = $data['VaaniDispositionPlan']['name'];
        $planModel->status = 1;
        $planModel->save();

        $lastInsertedPlanId = $planModel->id;
        $vaaniDispositions = $data['VaaniDispositions'];
        // Loop through each VaaniDisposition data and insert into the database
        for ($i = 0; $i < count($vaaniDispositions['disposition_name']); $i++) {
            $newDisposition = new AllDispositions();
            $newDisposition->planid = $lastInsertedPlanId; // Set the plan ID
            $newDisposition->client_id = $client_id; // Set the client ID

            // Set other fields from VaaniDispositions array
            $newDisposition->disponame = $vaaniDispositions["disposition_name"][$i];
            $newDisposition->dispocode = $vaaniDispositions["short_code"][$i];
            $newDisposition->dispotype = $vaaniDispositions["type"][$i];
            $newDisposition->level = $vaaniDispositions["level"][$i];
            // Set parent_id as null if it's null in the array, otherwise set the value
            $newDisposition->parent_id = $vaaniDispositions["parent_id"][$i] ?? null;
            $newDisposition->status = 1;

            $newDisposition->save(); // Save the new disposition record
        }
    
        return redirect('disposition')->with('success', 'disposition Created successfully.');
    }

    // private function saveDispositions($data, $parentId = null, $planId,$client_id,$temp_id=null)
    // {   

    //     foreach ($data['disposition_name'] as $key => $disposition) {

    //         if( is_array( $disposition ) && $disposition[0] != '' ){
                
    //             $temp_arr = [
    //                 'disposition_name' => [
    //                     $disposition
    //                 ],
    //                 'short_code' => [
    //                     $data['short_code'][$key]
    //                 ],
    //                 'type' => [
    //                     $data['type'][$key]
    //                 ]
    //             ];
    //             $this->saveDispositions($temp_arr,$temp_id,$planId,$client_id,$temp_id=null);
    //         }

    //         $dispositionModel = AllDispositions::create([
    //             'client_id' => $client_id,
    //             'disponame' => $disposition[0],
    //             'dispocode' => $data['short_code'][$key][0],
    //             'dispotype' => $data['type'][$key][0],
    //             'parent_id' => $parentId,
    //             'level' => intval($key) + 1,
    //             'status' => '1',
    //             'planid' => $planId,
    //         ]);


    //         $temp_id = $dispositionModel->id;
    //         // return ;

    //         // if (is_array( $disposition[0])) {
    //         //     $this->saveDispositions(['disponame' =>  $disposition[0], 'dispocode' => $data['short_code'][$key][0], 'dispotype' => $data['type'][$key][0],'planid' => $planId,'client_id' => $client_id]);
    //         // }
    //     }
    // }

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
        $sessionData = session('data');
        // print_r($sessionData);exit;
        $planid = $id;
        $client_id = $sessionData['Client_id'];
        // $planname = DispositionPlan::where('client_id', $client_id)->where('id',$id)->select('planname','id');
        $plan = DispositionPlan::where('client_id', $client_id)->where('id', $id)->first();
        $planname = $plan ? $plan->planname : '';
        $planid = $plan ? $plan->id : '';
        $data = AllDispositions::leftJoin('dispositionplan', 'alldisposition.planid','=',  'dispositionplan.id')
        ->select(
            'alldisposition.planid',
            'alldisposition.disponame',
            'alldisposition.dispocode',
            'alldisposition.dispotype',
            'alldisposition.level',
            'dispositionplan.id as plan_id', // Renamed to avoid column name conflicts
            'dispositionplan.planname'
        )
    ->where('dispositionplan.client_id', $client_id)
    ->where('planid',$id)
    ->get();

            
    return view ('disposition.edit',compact('planid','planname','data','planid'));
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
        // return $request;

        $sessionData = session('data');

        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n------User update for--".$id);

        $data = $request->all();
         $request->validate([
            'VaaniDispositionPlan.name' =>  [
                'required',
                Rule::unique('dispositionplan', 'planname')->ignore($id),
            ],
            'VaaniDispositions.disposition_name.*' => 'required|string',
            'VaaniDispositions.short_code.*' => 'required|string',
            'VaaniDispositions.type.*' => 'required|numeric',
        ], [
            'VaaniDispositionPlan.name.required' => 'The plan name is required.',
            'VaaniDispositions.disposition_name.required' => 'The disposition name is required.',
            'VaaniDispositions.short_code.required' => 'The short code is required.',
            'VaaniDispositions.type.required' => 'The type is required.',
        ]);

        DispositionPlan::where('id', $id)->where('client_id', $sessionData['Client_id'])->delete();
        AllDispositions::where('planid', $id)->where('client_id', $sessionData['Client_id'])->delete();

        // Save VaaniDispositionPlan
        $planModel = new DispositionPlan();
        $planModel->client_id = $sessionData['Client_id'];
        $planModel->planname = $data['VaaniDispositionPlan']['name'];
        $planModel->status = 1;
        $planModel->save();

        $lastInsertedPlanId = $planModel->id;
        $vaaniDispositions = $data['VaaniDispositions'];
        // Loop through each VaaniDisposition data and insert into the database
        for ($i = 0; $i < count($vaaniDispositions['disposition_name']); $i++) {
            $newDisposition = new AllDispositions();
            $newDisposition->planid = $lastInsertedPlanId; // Set the plan ID
            $newDisposition->client_id = $sessionData['Client_id']; // Set the client ID

            // Set other fields from VaaniDispositions array
            $newDisposition->disponame = $vaaniDispositions["disposition_name"][$i];
            $newDisposition->dispocode = $vaaniDispositions["short_code"][$i];
            $newDisposition->dispotype = $vaaniDispositions["type"][$i];
            $newDisposition->level = $vaaniDispositions["level"][$i];
            // Set parent_id as null if it's null in the array, otherwise set the value
            $newDisposition->parent_id = $vaaniDispositions["parent_id"][$i] ?? null;
            $newDisposition->status = 1;

            $newDisposition->save(); // Save the new disposition record
        }

        // Retrieve the plan and update its name
        // $plan = DispositionPlan::findOrFail($id);
        // $plan->planname = $data['VaaniDispositionPlan']['name'];
        // $plan->client_id = $sessionData['Client_id'];
        // $plan->save();
        // $dispositionData = $data['VaaniDispositions'];
        // // Insert new records for each disposition data
        // foreach ($dispositionData['disposition_name'] as $index => $name) {
        //     AllDispositions::insert([
        //         'planid' => $id,
        //         'client_id' => $sessionData['Client_id'],
        //         'disponame' => $name,
        //         'dispocode' => $dispositionData['short_code'][$index],
        //         'dispotype' => $dispositionData['type'][$index],
        //         'level' => $dispositionData['level'][$index],
        //         'parent_id' => $dispositionData['parent_id'][$index],
        //         'status' => 1,
        //     ]);
        // }

        $custom_log->debug(__LINE__."\n\n\n----requested data are proper-----");
        $custom_log->debug(__LINE__."\n\n\n----Disposition data updated by -----".$sessionData['userID']);


        return redirect()->route('disposition.index')->with('success', 'Disposition updated successfully.');





        // $sessionData = session('data');
        // $client_id = $sessionData['Client_id'];
        // $ip = $request->ip();
        // DB::enableQueryLog();
        // $custom_log = Log::channel('adminactivity');
        // $custom_log->debug("\n\n\n----\-----------------------/-----");
        // $custom_log->debug(__LINE__."\n\n\n------User update for--".$id);
        // $users = Users::findOrFail($id);
        // //echo "<pre>"; print_r($client_id);die;
        // $request->validate([
        //     'client_id' => 'required',
        //     'name' => 'required',
        //     'email' => 'required|email',
        //     'role' => 'required',
        //     'mobile' => 'required',
        //     'status' => 'required',
        //     'email' => [
        //         'required',
        //         'email',
        //         Rule::unique('users')->ignore($id),
        //     ],
        //     'username' => [
        //         'required',
        //         'min:3',
        //         'max:255',
        //         Rule::unique('users')->ignore($id),
        //     ]
        // ]);
        // $custom_log->debug(__LINE__."\n\n\n---- validations are proper --------");

        // $users->client_id = $client_id;
        // $users->name = $request->input('name');
        // $users->role = $request->input('role');
        // $users->email = $request->input('email');
        // $users->mobile = $request->input('mobile');
        // $users->username = $request->input('username');
        // $users->status = $request->input('status');
        // $users->is_deleted = 1;
        // $users->manager_id = $request->input('manager');
        // $users->supervisor_id = $request->input('supervisor');
        // $custom_log->debug(__LINE__."\n\n\n----requested data are proper-----");
        // $users->save();
        // $custom_log->debug(__LINE__."\n\n\n----user data updated by -----".$ip);

        // return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        $dispo = DispositionPlan::find($id);
        // DB::enableQueryLog();
        // $custom_log = Log::channel('adminactivity');
        // $custom_log->debug("\n\n\n----\-----------------------/-----");
        // $custom_log->debug(__LINE__."\n\n\n----delete dispo -----");
        $ip = $request->ip();


        if ($dispo) {
            // Update the campaign record
            $dispo->status = 2;
            $dispo->is_deleted = 2;
            //$custom_log->debug(__LINE__."\n\n\n-----set dispo status 2 by-----".$ip);
            $dispo->save();

            // Delete related license details
            AllDispositions::where('planid', $id)->update(['status' => 2]);

            // Delete related moduleSM records
            // MappedUsers::where('campaign_id', $id)->update(['status' => 2]);
            // $custom_log->debug(__LINE__."\n\n\n-----set AllDispositions status 2 by-----".$ip);
            // // $custom_log->debug(__LINE__."\n\n\n-----set MappedUsers status 2 by-----".$ip);
            // $custom_log->debug(__LINE__."\n\n\n-----delete AllDispositions by-----".$ip);

            return redirect('disposition')->with('success', 'disposition and related records deleted successfully.');
        } else {
            return redirect('disposition')->with('error', 'disposition not found.');
        }
    }

    public function delete(Request $request,$id)
    {
        $dispo = DispositionPlan::find($id);
        // DB::enableQueryLog();
        // $custom_log = Log::channel('adminactivity');
        // $custom_log->debug("\n\n\n----\-----------------------/-----");
        // $custom_log->debug(__LINE__."\n\n\n----delete dispo -----");
        $ip = $request->ip();


        if ($dispo) {
            // Update the campaign record
            $dispo->status = 2;
            $dispo->is_deleted = 2;
            //$custom_log->debug(__LINE__."\n\n\n-----set dispo status 2 by-----".$ip);
            $dispo->save();

            // Delete related license details
            AllDispositions::where('planid', $id)->update(['status' => 2]);

            // Delete related moduleSM records
            // MappedUsers::where('campaign_id', $id)->update(['status' => 2]);
            // $custom_log->debug(__LINE__."\n\n\n-----set AllDispositions status 2 by-----".$ip);
            // // $custom_log->debug(__LINE__."\n\n\n-----set MappedUsers status 2 by-----".$ip);
            // $custom_log->debug(__LINE__."\n\n\n-----delete AllDispositions by-----".$ip);

            return redirect('disposition')->with('success', 'disposition and related records deleted successfully.');
        } else {
            return redirect('disposition')->with('error', 'disposition not found.');
        }
    }
}
