<?php

namespace App\Http\Controllers;
use App\Models\Clients;
use App\Models\Users;
use App\Models\Queue;
use App\Models\Campaign;
use App\Models\CallWindow;
use App\Models\MappedUsers;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class BulkFileUploadController extends Controller
{
    public function index()
    {       
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $campaigns = Campaign::where('client_id',$client_id)->whereIn('deleted_at', [1,0])->pluck('name','id');
        $templetelist = DB::table('templates')
        ->where('client_id', $client_id)
        ->whereIn('status', [0, 1])
        ->pluck('name', 'id');
        return view ('uploadfile.index',compact('campaigns','templetelist'));
    }

    public function BulkUploadList(Request $request)
    {   
        //fetching download url data from bulkfile_log
        $sessionData = session('data');
        //  echo "<pre>"; print_r($sessionData);die;
        if ($request->ajax()) {
 
                $data = DB::table('bulkfile_log')
                ->join('clients', 'clients.id', '=', 'bulkfile_log.client_id')
                ->join('campaign', 'campaign.id', '=', 'bulkfile_log.campaign_id')
                ->select('bulkfile_log.*', 'clients.name as client_name','campaign.name as campaignname')
                ->where('bulkfile_log.client_id',$sessionData['Client_id'])     
                ->where('bulkfile_log.created_at', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 15 DAY)'))
                ->latest()
                ->get(); 
            

            return DataTables::of($data)
                ->make(true);
        }
 
         return abort(404);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {       
        $sessionData = session('data');
        ini_set('memory_limit', '1024M');
        // echo "<pre>"; print_r($sessionData);die;
         $request->validate([
            'campaign' => 'required',
            'templete' =>'required',
             'file' => 'required|file|mimes:csv,txt|max:5048', // Adjust the max file size as per your requirements
         ]);
 
         if ($request->hasFile('file')) {
             $file = $request->file('file');
             $fileName = date('Y_m_d_His') . '_' . $file->getClientOriginalName();
             // Move the uploaded file to the storage directory
             $file->storeAs('csv', $fileName, 'public');
             $existFileName = 'csv/'.$fileName;
             $fileExists = Storage::disk('public')->exists($existFileName);
             if($fileExists)
             {
                 $filePath                       = Storage::disk('public')->path($existFileName);
                 $phoneNumbers                 = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                 if($phoneNumbers[0] != 'phone_no')
                 {                    
                     return response()->json(['status'=> 'failed', 'msg' => 'Invalid File Format, Please keep header name as {phone_no}.']);
                 }
                 $phoneNumbers                 = array_slice($phoneNumbers, 1);
                 // Extract the first column from each line
                 $phoneNumbers = array_map(function ($phoneNumbers) {
                     $columns = str_getcsv($phoneNumbers); // Parse CSV data from each line
                     return $columns[0]; // Return the first column (index 0) of each line
                 }, $phoneNumbers);
 
 
                 $count  = count($phoneNumbers);
                 if($count == 0)
                 {
                     return response()->json(['status'=> 'failed', 'msg' => 'Sorry, empty file not allowed.']);
 
                 }
                 else if($count > 10000)
                 {
                     return response()->json(['status'=> 'failed', 'msg' => 'You can not contain more then 10000 phones no in one file.']);
 
                 }
                 else
                 {
                     //echo "<pre>"; print_r($phoneNumbers);die;
                     // $filePath = Storage::disk('public')->path('csv/'.$fileName);
                     $Bulkfilelog =  new Bulkfilelog();
                     $Bulkfilelog->user_id = $sessionData['userID'];
                     $Bulkfilelog->client_id = $sessionData['Client_id'];
                     $Bulkfilelog->api_id = Config::get('custom.authbridge.rc.api_id');
                     $Bulkfilelog->vendor = Config::get('custom.authbridge.rc.vender');
                     $Bulkfilelog->filename = $fileName;
                     $Bulkfilelog->upload_url = $filePath;
                     $Bulkfilelog->api_name  = Config::get('custom.authbridge.rc.api_name');
                     $Bulkfilelog->count     = $count;
                     $Bulkfilelog->processed_count  = 0;
                     $Bulkfilelog->status  = 1;
                     $Bulkfilelog->is_processed  = 1;
                     $Bulkfilelog->request_type  = 'rc';
                     $Bulkfilelog->save();
                     
                     if($Bulkfilelog)
                     {
                         if(!empty($phoneNumbers))
                         {
                             foreach($phoneNumbers as $k => $input)
                             {
                                 
                                 
                                 DB::table('cron_bulk_dump')->insert([
                                     'bulk_id' => $Bulkfilelog->id,
                                     'input' => $this->sanitizeInputData($input, 'text'),
                                     'status' => 1,
                                     'created_at' => now()
                                 ]);
                             }
                         }
                         // $result = $this->processUploadedFiles($Bulkfilelog->id);
                         
                         $result['status'] = 'success';
                         if($result['status'] == 'success'){
                             return response()->json(['status'=> 'success', 'msg' => 'We have recieved your data please check the RC Bulk Upload List for the status in Report TAB.']);
                             // return redirect('rc.rc_bulk_upload')->with('success', 'We have recieved your data please check the RC Bulk Upload List for the status.');
                         }
                         else{
                         return response()->json(['status'=> 'failed', 'msg' => $result['msg']]);
                             // return redirect('rc.rc_bulk_upload')->with('failed', $result['msg']);
                         }
                             
                     }
                     else{
                         // redirect()->route('rc.rc_bulk_upload')->with('success', 'Sorry Unable to process the data!');
                         return response()->json(['status'=> 'failed', 'msg' => 'Sorry Unable to process the data.']);
                         // return redirect('rc.rc_bulk_upload')->with('failed', 'Sorry Unable to process the data');
                     }
                 }
             }
             else{
                 return response()->json(['status'=> 'failed', 'msg' => 'Sorry, Not able to find the data.']);
             }  
         }
         else{
             return response()->json(['status'=> 'failed', 'msg' => 'Sorry, Unable to find the file.']);
         }    

    }
}
