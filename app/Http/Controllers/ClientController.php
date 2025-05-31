<?php

namespace App\Http\Controllers;
use App\Models\Clients;
use App\Models\LicenseDetails;
use App\Models\ModuleSM;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;

class ClientController extends Controller
{   
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {       
        $sessionData = session('data');
         //print_r($sessionData);exit;
        $client = Clients::whereIn('status', [1,0,2])->whereIn('deleted_at', [1,0])->latest()->get();
        // print_r($client);exit;
        // return view ('client.index')->with('client', $client);

         // $client = Clients::whereIn('status', [1,0])->latest()->get();

        return view('client.index', compact('client'));
    }

    public function getClientList(Request $request)
    {

        if ($request->ajax()) {
            $data = Clients::whereIn('status', [1,0,2])->whereIn('deleted_at', [1,0])->latest()->get();
            //$data = Clients::all();
            //echo"<pre>"; print_r($data);die;
            return DataTables::of($data)
                ->addColumn('action', function($row){
                    $editUrl = route('client.edit', $row->id);
                    $deleteUrl = route('client.delete',$row->id);
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
        $clientlist = Clients::whereIn('status', [0,1,2])
        ->pluck('name');
        // $licenseDetails = LicenseDetails::where('client_id', $id)
        // ->orderBy('created_at', 'desc')
        // ->get();
        // return view ('client.create');
        return view ('client.create', compact('clientlist'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {       
        //ini_set('memory_limit', '-1');
        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n----create client -----");
        $sessionData = session('data');
        //print_r($sessionData);exit;
        $request->validate([
            'name' => 'required',
            'status' => 'required',
            'description' => 'required',
            'admin' => 'required|numeric',
            'manager' => 'required|numeric',
            'mis' => 'required|numeric',
            'agent' => 'required|numeric',
            'supervisor' => 'required|numeric',

        ], [
            'name.required' => 'The client name is required.',
            // 'mobileno.required' => 'The mobileno field is required.',
            // 'mobileno.numeric' => 'Please enter a numeric value for mobileno.',
            'status.required' => 'The status field is required.',
            'admin.required' => 'The admin field is required.',
            'admin.numeric' => 'Please enter a numeric value for admin.',
            'managar.required' => 'The managar field is required.',
            'managar.numeric' => 'Please enter a numeric value for managar.',
            'mis.required' => 'The mis field is required.',
            'mis.numeric' => 'Please enter a numeric value for mis.',
            'agent.required' => 'The agent field is required.',
            'agent.numeric' => 'Please enter a numeric value for agent.',
            'supervisor.required' => 'The supervisor field is required.',
            'supervisor.numeric' => 'Please enter a numeric value for supervisor.',
        ]);

        $custom_log->debug(__LINE__."\n\n\n---- validations are proper --------");
        //$data = $request->all();
        //echo "<pre>";print_r($data);exit;
        $client = new Clients();
        $client_id = $client->id;
        $client->name = $request->input('name');
        $client->email = $request->input('email');
        // $client->mobileno = $request->input('mobileno');
        $client->description = $request->input('description');
        $client->status = $request->input('status');
        $lic_admin = intval($request->input('admin'));
        $lic_supervisor = intval($request->input('supervisor'));
        $lic_mis = intval($request->input('mis'));
        $lic_manager = intval($request->input('manager'));
        $lic_agent = intval($request->input('agent'));
        $client->license_count = $lic_admin + $lic_supervisor + $lic_mis + $lic_manager + $lic_agent;
        $client->deleted_at = 1;
        $client->created_at = now();
        $ip = $request->ip();
        $client->created_by = 1;
        $custom_log->debug(__LINE__."\n\n\n----requested data are proper-----");
        $client->save();

        $custom_log->debug(__LINE__."\n\n\n----client create by --------".$ip);
        $client_id = $client->id;

        if(!empty($client->id))
        {
            $roleCounts = [
                'admin' => intval($request->input('admin')),
                'user' => intval($request->input('agent')),
                'manager' => intval($request->input('manager')),
                'mis' => intval($request->input('mis')),
                'supervisor' => intval($request->input('supervisor'))
            ];
            foreach ($roleCounts as $role => $count) {
                $licenseDetails = new LicenseDetails();
                $licenseDetails->client_id = $client_id; // Use your client ID here
                $licenseDetails->name = $role;
                $licenseDetails->lic_count = $count;
                $licenseDetails->deleted_at = 1 ;
                $licenseDetails->created_at = now();
                $ip = $request->ip();
                $licenseDetails->created_by = 1;
                $licenseDetails->save();
            }

            $custom_log->debug(__LINE__."\n\n\n----client and LicenseDetails created by ----".$ip);
            
        }

        return redirect()->route('client.index')->with('success', 'Client and License count created successfully.');

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
        $client = Clients::find($id);
        //$client = Clients::whereIn('status', [1,0])->whereIn('deleted_at', [1,0])->latest()->get();
        // echo "<pre>";print_r($client);exit;
        //$client = Clients::whereIn('status', [1,0])->latest()->get();
        //$licensecount = LicenseDetails::all();
        //$licensecount = LicenseDetails::whereIn('status', [1,0])->whereIn('deleted_at', [1,0])->latest()->get();
        // $client = Clients::all();
        // //$client = Clients::whereIn('status', [1,0])->whereIn('deleted_at', [1,0])->latest()->toArray();
        //$licensecount = LicenseDetails::whereIn('status', [1,0])->whereIn('deleted_at', [1,0])->latest()->get();
        $licenseDetails = LicenseDetails::where('client_id', $id)
        ->orderBy('created_at', 'desc')
        ->get();

        $clientlist = Clients::whereIn('status', [0,1,2])
        ->pluck('name');
        return view ('client.edit', compact('client','licenseDetails','clientlist'));
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
        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n-----update client-----".$id);
        $sessionData = session('data');
        //print_r($sessionData);exit;
        $request->validate([
            'name' => 'required',
            // 'email' => 'required|email',
            'status' => 'required',
            'description' => 'required',
            'admin' => 'required|numeric',
            'manager' => 'required|numeric',
            'mis' => 'required|numeric',
            'agent' => 'required|numeric',
            'supervisor' => 'required|numeric',
            // 'email' => [
            //     'required',
            //     'email',
            //     Rule::unique('clients')->ignore($id),
            // ], // Allow the current email to remain

        ], [
            'name.required' => 'The client name is required.',
            // 'email.required' => 'The email address is required.',
            // 'email.email' => 'Please enter a valid email address.',
            // 'mobileno.required' => 'The mobileno field is required.',
            // 'mobileno.numeric' => 'Please enter a numeric value for mobileno.',
            'status.required' => 'The status field is required.',
            'admin.required' => 'The admin field is required.',
            'admin.numeric' => 'Please enter a numeric value for admin.',
            'managar.required' => 'The managar field is required.',
            'managar.numeric' => 'Please enter a numeric value for managar.',
            'mis.required' => 'The mis field is required.',
            'mis.numeric' => 'Please enter a numeric value for mis.',
            'agent.required' => 'The agent field is required.',
            'agent.numeric' => 'Please enter a numeric value for agent.',
            'supervisor.required' => 'The supervisor field is required.',
            'supervisor.numeric' => 'Please enter a numeric value for supervisor.',
        ]);

        //$data = $request->all();
        $custom_log->debug(__LINE__."\n\n\n-----validation handle while on update client-----");

        $client = Clients::find($id);
        //dd($data);exit;
         if (!$client) {
        // Handle the case where the client with the given ID does not exist.
        return redirect()->back()->with('error', 'Client not found.');
        }
        // Update the client's information
        $client->name = $request->input('name');
        $client->email = $request->input('email');
        // $client->mobileno = $request->input('mobileno');
        $client->description = $request->input('description');
        $client->status = $request->input('status');
        $lic_admin = intval($request->input('admin'));
        $lic_supervisor = intval($request->input('supervisor'));
        $lic_mis = intval($request->input('mis'));
        $lic_manager = intval($request->input('manager'));
        $lic_agent = intval($request->input('agent'));
        $client->license_count = $lic_admin + $lic_supervisor + $lic_mis + $lic_manager + $lic_agent;
        $client->updated_at = now(); // Update the 'updated_at' timestamp
        $client->updated_by = 1; // Set the user who updated the client
        $custom_log->debug(__LINE__."\n\n\n----requested data are proper-----");
        $client->save();
        $ip = $request->ip();
        $custom_log->debug(__LINE__."\n\n\n-----update client by-----".$ip);

        $client_id = $id;   

        $roleCounts = [
            'admin' => intval($request->input('admin')),
            'user' => intval($request->input('agent')),
            'manager' => intval($request->input('manager')),
            'mis' => intval($request->input('mis')),
            'supervisor' => intval($request->input('supervisor'))
        ];
        
        foreach ($roleCounts as $role => $count) {
            // Check if a license detail entry already exists for this client and role
            $existingLicenseDetail = LicenseDetails::where('client_id', $client->id)
                ->where('name', $role)
                ->first();
        
            if ($existingLicenseDetail) {
                // Update the existing license detail
                $existingLicenseDetail->lic_count = $count;
                $existingLicenseDetail->updated_at = now();
                $existingLicenseDetail->updated_by = 1; // Set the user who updated the license detail
                $existingLicenseDetail->save();
            } else {
                // Create a new license detail entry
                $newLicenseDetail = new LicenseDetails();
                $newLicenseDetail->client_id = $client->id;
                $newLicenseDetail->name = $role;
                $newLicenseDetail->status = 1;
                $newLicenseDetail->lic_count = $count;
                $newLicenseDetail->deleted_at = 1;
                $newLicenseDetail->created_at = now();
                $newLicenseDetail->created_by = 1;
                $newLicenseDetail->save();
            }
        }

        $custom_log->debug(__LINE__."\n\n\n-----update client and license by-----".$ip);

        // if(!empty($client->id))
        // {
        //     $roleCounts = [
        //         'admin' => intval($request->input('admin')),
        //         'agent' => intval($request->input('agent')),
        //         'manager' => intval($request->input('manager')),
        //         'mis' => intval($request->input('mis')),
        //         'supervisor' => intval($request->input('supervisor'))
        //     ];
        //     foreach ($roleCounts as $role => $count) {
        //         $licenseDetails = new LicenseDetails();
        //         $licenseDetails->client_id = $client_id; // Use your client ID here
        //         $licenseDetails->name = $role;
        //         $licenseDetails->status = 1;
        //         $licenseDetails->lic_count = $count;
        //         $licenseDetails->deleted_at = 1 ;
        //         $licenseDetails->created_at = now();
        //         $ip = $request->ip();
        //         $licenseDetails->created_by = 1;
        //         $licenseDetails->save();
        //     }

        //     // $moduleSM = new ModuleSM();
        //     // $moduleSM->client_id = $client_id;
        //     // $moduleSM->name = 'whatsapp';
        //     // $moduleSM->status = 1;
        //     // $moduleSM->deleted_at = 1;
        //     // $moduleSM->created_at = now();
        //     // $ip = $request->ip();
        //     // $moduleSM->created_by = 1;
        //     // $moduleSM->save();
            
        // }

        return redirect()->route('client.index')->with('success', 'Client and License count updated successfully.');
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
        $custom_log->debug(__LINE__."\n\n\n-----delete client-----".$id);
        $client = Clients::find($id);
        $ip = $request->ip();

            if ($client) {
                // Update the client record
                $client->deleted_at = 2;
                $client->status = 2;
                $client->save();

                // Delete related license detailss
                LicenseDetails::where('client_id', $id)->update(['deleted_at' => 2, 'status' => 2]);
                $custom_log->debug(__LINE__."\n\n\n-----set client status and license details 2 by-----".$ip);

                // Delete related moduleSM records
                //ModuleSM::where('client_id', $id)->update(['deleted_at' => 2, 'status' => 2]);
                $custom_log->debug(__LINE__."\n\n\n-----delete client and license by-----".$ip);

                return redirect('client')->with('success', 'Client and related records deleted successfully.');
            } else {
                return redirect('client')->with('error', 'Client not found.');
            }
    }

    public function delete(Request $request,$id){
        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug(__LINE__."\n\n\n-----delete client-----".$id);
        $client = Clients::find($id);
        $ip = $request->ip();
            if ($client) {
                // Update the client record
                $client->deleted_at = 2;
                $client->status = 2;
                $client->save();

                // Delete related license details
                LicenseDetails::where('client_id', $id)->update(['deleted_at' => 2, 'status' => 2]);
                $custom_log->debug(__LINE__."\n\n\n-----set client status and license details 2 by-----".$ip);
                // Delete related moduleSM records
                //ModuleSM::where('client_id', $id)->update(['deleted_at' => 2, 'status' => 2]);
                $custom_log->debug(__LINE__."\n\n\n-----delete client and license by-----".$ip);

                return redirect('client')->with('success', 'Client and related records deleted successfully.');
            } else {
                return redirect('client')->with('error', 'Client not found.');
            }
    }
}
