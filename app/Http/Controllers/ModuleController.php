<?php

namespace App\Http\Controllers;
use App\Models\Company;
use App\Models\Module;
use App\Models\Apimaster;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class ModuleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        $module = Module::all();
        return view ('module.index')->with('module', $module);
    }

    public function getModuleList(Request $request)
    {
        
       // echo"<pre>"; print_r($data);die;
        if ($request->ajax()) {
            $data = Module::whereIn('del_status', [1,0])->latest()->get();
            //echo"<pre>"; print_r($data);die;
            return DataTables::of($data)
                ->addColumn('action', function($row){
                    $editUrl = route('module.edit', $row->id);
                    $deleteUrl = route('module.delete',$row->id);
                    $btn = '<a href="'.$editUrl.'" class="btn btn-sm btn-info">Edit</a>';
                    $btn .= '<a href="'.$deleteUrl.'" class="btn btn-sm btn-info status-select" key-value = "'.$row->id.'">Delete</a>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return abort(404);
    }

    public function getApiData(Request $request)
    {
        $vendor = $request->input('vendor');
        $apiData = Apimaster::where('vender', $vendor)->where('status', 1)->pluck('api_name'); 
        // dd($apiData);
        // ->pluck
        return response()->json($apiData);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {       
        // return view('items.form');
        $companyname = Company::where('del_status', 1)->pluck('name', 'id');
        $vendorname = Apimaster::where('status', 1)->distinct()->pluck('vender');

        //return $vendorname;
        return view('module.create', compact('companyname','vendorname'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {   
        // return $request;
        $validatedData = $request->validate([
            'apiname' => 'required',
            'company' => 'required',
            // 'apiurl' => 'required|url',
            // 'apitesturl' => 'required|url',
            'vendorname' => 'required',
            'status' => 'required|in:1,2',
        ]);

        $companyName = $validatedData['company']['name'];
        $companyId = $validatedData['company']['id'];

        $apiMaster = DB::table('api_master')
         ->select('*')
         ->whereIn('status', [0,1])
         ->where('api_name', $validatedData['apiname'])
         ->where('vender', $validatedData['vendorname'])
         ->latest()
         ->first();

        $module = new Module();
        $module->apiname = $validatedData['apiname'];
        $module->company = $companyName;
        $module->view_filename = $apiMaster->view_filename;
        $module->api_alias = $apiMaster->api_alias;
        $module->vendorname = $validatedData['vendorname'];
        $module->status = $validatedData['status'];
        $module->client_id = $companyId;
        $ip = $request->ip();
        $module->created_by = $ip;
        $module->updated_by = $ip;

        $module->save();

        return redirect()->route('module.index')->with('success', 'module created successfully.');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Module  $module
     * @return \Illuminate\Http\Response
     */
    public function show(Module $module)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Module  $module
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {   
        $module = Module::findOrFail($id);
        $companyname = Company::where('del_status', 1)->pluck('name', 'id');
        $vendorname = Apimaster::where('status', 1)->distinct()->pluck('vender');
        return view ('module.create', compact('module','companyname','vendorname'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Module  $module
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Module $module)
    {
        $validatedData =  $request->validate([
            'apiname' => 'required',
            'company' => 'required',
            // 'apiurl' => 'required|url',
            // 'apitesturl' => 'required|url',
            'vendorname' => 'required',
            'status' => 'required|in:1,2',
        ]);
        $ip = $request->ip();

        $apiMaster = DB::table('api_master')
         ->select('*')
         ->whereIn('status', [0,1])
         ->where('api_name', $validatedData['apiname'])
         ->where('vender', $validatedData['vendorname'])
         ->latest()
         ->first();
        //echo "<pre>"; print_r($apiMaster);die;

        $request->merge([
            'company' => $request['company']['name'],
            'client_id' => $request['company']['id'],
            'view_filename' => $apiMaster->view_filename,
            'vender' => $apiMaster->api_alias,
            'created_by' => $ip,
            'updated_by' => $ip,
        ]);
        // echo "<pre>"; print_r($apiMaster);

        // echo "<pre>"; print_r($request->all());die;
        $module->update($request->all());

        return redirect()->route('module.index')->with('success', 'module updated successfully.');
    }

    public function delete($id){
        $module = Module::find($id);
            $module->delete();
            return Redirect()->back()->with('success','module deleted successfully.');
    }


    public function destroy($id)
    {   
        Module::destroy($id);
        return redirect('module')->with('success', 'module deleted successfully.');
    }
}
