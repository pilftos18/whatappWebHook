<?php

namespace App\Http\Controllers;
use App\Models\Clients;
use App\Models\Users;
use App\Models\Chat_log;
use App\Models\Campaign;
use App\Models\CallWindow;
use App\Models\MappedUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Chats;
use App\Models\Template;
use App\Models\Breaks;
use App\Models\Disposition;
use Illuminate\Support\Facades\Hash;
use App\Models\Session_Log;
use Carbon\Carbon;
use App\Models\Break_log;
use App\Http\Controllers\LoginController;

class ChatController extends Controller
{
    public function show($id)
    {
       $chat = Chat_log::where('chat_id',$id)
        ->select('chat_id','in_out','message','media_path','timestamp')
        ->get();

        return view('show_chat', ['chat' => $chat]);
    }

    public function getChatDetails(Request $request){

        DB::enableQueryLog();
        $custom_log = Log::channel('custom');
        $custom_log->debug(__LINE__."\n\n\n--------------------Start to get chat details ------------------------------------");
        
        $chatid = $request->input('chat_id');
        $logMessage = __LINE__ . " : getting request data for chat details --";
        $logMessage .= "\nChat_id: " . $chatid;
        $custom_log->debug($logMessage);
        $chat = Chat_log::where('chat_id', $chatid)
            ->select('in_out', 'message', 'media_path', 'timestamp')
            ->get();

            $queryLog = DB::getQueryLog();
            $lastQuery = end($queryLog)['query'];
            $lastBindings = end($queryLog)['bindings'];

            $custom_log->debug(__LINE__." : get chat for seleacted chat_id --");
            $custom_log->debug("Query for chat details: " . $lastQuery);
            $custom_log->debug("Bindings: " . json_encode($lastBindings));

        
        $csvarray = [];
        $custom_log->debug(__LINE__." : put content in csv-----");
    
        $csvarray[] = ['Sender/Receiver','Message','file Type','Time'];
     
        // Convert each row of the $chat data into an array suitable for CSV
        foreach ($chat as $row) {
            $fileExtension = pathinfo($row->media_path, PATHINFO_EXTENSION);
            $timestamp = (string) $row->timestamp;
            $firstTenDigits = substr($timestamp, 0, 10); 
            $formattedTimestamp = date('Y-m-d H:i:s', $firstTenDigits);
            $message = mb_convert_encoding($row->message, 'UTF-8', 'auto');

            if ($fileExtension) {
                $message .= 'File';
            }
            $csvarray[] = [
                ($row->in_out == 2) ? 'Sender' : 'Receiver',
                $message,
                $fileExtension,
                $formattedTimestamp,
            ];
        }

        $custom_log->debug(__LINE__." : generate csv -----");

        $tempFilePath = tempnam(sys_get_temp_dir(), 'ChatDetails');
        $tempFile = fopen($tempFilePath, 'w');
        $custom_log->debug(__LINE__." : file created -----");
        foreach ($csvarray as $row) {
            fputcsv($tempFile, $row);
        }
        $custom_log->debug(__LINE__." : put csv content in file -----");
        fclose($tempFile);
        $filename = 'chat_details' . time() . '.csv';
        $headers = array(
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Encoding' => 'UTF-8',
            'Content-Disposition' => 'attachment; filename='.$filename.'',
        );
        $url = request()->root();
        $parsedUrl = parse_url($url);
        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
        $filePath = storage_path("app/public/uploads/csvdownload/$filename");
        $fileUrl = $baseUrl . "/storage/app/public/uploads/csvdownload/" . $filename;
        $custom_log->debug(__LINE__." : File URL:----- " . $fileUrl);
        $custom_log->debug(__LINE__." : File Storage As: -----" . $filePath);
        $custom_log->debug(__LINE__." : chat details Generated -----");
        rename($tempFilePath, $filePath);
        chmod($filePath, 0755);
        return response()->json(['download' => '1', 'file_url' => $fileUrl, 'file_name' => $filename], 200);

    }

    public function chats(Request $request)
    {   
        $sessionData = Session::get('data');
       $chatid = $sessionData['userID'];
        // return $sessionData['userID'];
    //    print_r($sessionData);exit;
        if(session()->has('data'))
        {			
            $newchats='';
            // $sessionData[''];
            // $chats = Chats::where('status', 1)->where('is_closed', 1)->where('assigned_to', $sessionData['userID'])->where('client_id', $sessionData['Client_id'])->where('campaign_id', $sessionData['campaignID'])->get();
            $chats = Chats::select('chats.*', 'campaign.interaction_per_user')
            ->leftJoin('campaign', 'chats.campaign_id', '=', 'campaign.id')
            ->where('chats.assigned_to', $sessionData['userID'])
            ->where('chats.client_id', $sessionData['Client_id'])
            ->where('chats.campaign_id', $sessionData['campaignID'])
            ->where('chats.status', 1)
            ->where('campaign.status', 1)
            ->where('chats.is_closed', 1)
            ->get();

            $chatlogArray = [];
            $chatlogArray1 = [];
            $chatlogArray2 = [];
            foreach($chats as $chat){
                $chatid = $chat->id;
                $chatslogs = Chat_log::where('is_read', 1)->where('chat_id', $chatid)->count();
                $chatlogArray[$chatid] = $chatslogs;
            }

                $latestChatLog = Chat_log::where('chat_id', $chatid)
                ->orderBy('id', 'desc')
                ->select('chat_id','timestamp', 'message', 'media_path')
                ->first();

                if ($latestChatLog) {
                    $timestamp = $latestChatLog->timestamp;
                    $message = $latestChatLog->message;
                    $media_path = $latestChatLog->media_path;
            
                    $chatTime = date('H:i ', $timestamp);
                    if ($message != '') {
                        $msg = $message;
                    } elseif ($media_path != '') {
                        $path = asset("assets/agent_ui/img/img.png");
                        $msg = '<img src="'.$path.'" alt="image" height="15px" width="15px">'.' Attachment';
                    } else {
                        $msg = '';
                    }
            
                    // Create an array for each chat log data
                    $chatlogArray1 = [
                        'chat_id' => $chatid,
                        'chatTime' => $chatTime,
                        'msg' => $msg,
                    ];
            
                    // Push the chat log data into the main array
                    $chatlogArray2[] = $chatlogArray1;
                }
            

            $templates = Template::where('status', 1)->where('client_id', $sessionData['Client_id'])->get();
            $breaks = Breaks::where('status', 1)->where('client_id', $sessionData['Client_id'])->where('campaign_id', $sessionData['campaignID'])->get();
            
            return view('chat', compact('chats','chatlogArray','chatlogArray2','templates','breaks'));
        }
        else{
            return view('login');
        } 
		
    }

    public function sidebarchat(Request $request)
    {   
        $sessionData = Session::get('data');

       //print_r($sessionData);exit;
        if(session()->has('data'))
        {			
           
            if($request->input('idName')!=''){
               $idName = $request->input('idName');
               if($idName=='activeChat'){
                $chats = Chats::where('status', 1)->where('is_closed', 1)->where('assigned_to', $sessionData['userID'])->get();
               }elseif($idName=='closeChat'){
                $chats = Chats::where('status', 1)->where('is_closed', 2)->where('assigned_to', $sessionData['userID'])->get();
               }else{
                $chats = Chats::where('status', 1)->where('assigned_to', $sessionData['userID'])->get();
               }
               $data = [
                    'newchats' => $chats,
                ];
                return response()->json($data);
            }
            // dd(Request::exists('v'));
        //    return $chats;
        
            // return view('sidebarchat', compact('chats'));
        }
        else{
            return view('login');
        } 
		
    }


    public function getchats(Request $request)
    {   
        $sessionData = Session::get('data');
        // if(!$sessionData) {
        //     return redirect()->route('signout');
        // }
        $timestamp = now('Asia/Kolkata')->format('Y-m-d H:i:s');
        if (session()->has('data')) {			
            $id = $request->input('id');
            $client_id = $request->input('client_id');
            $campaign_id = $request->input('campaign_id');

            // code start added by mahesh 14-01-2024 
            DB::table('chats')
            ->where('id', $id)
            ->update([
                'open_at' => $timestamp
            ]);
            // code end added by mahesh 14-01-2024 
            
            $newchats = Chats::select('chats.*', 'clients.name as client_name', 'campaign.name as campaign_name')
                ->join('clients', 'chats.client_id', '=', 'clients.id')
                ->join('campaign', 'chats.campaign_id', '=', 'campaign.id')
                ->where('chats.status', 1)
                ->where('chats.id', $id)
                ->get();

           
            $newchatLogs = Chat_log::where('chat_id', $id)->get();

            $timestampArray = [];
           
            foreach ($newchatLogs as $chat) {
                $timestamp = (string) $chat->timestamp; // Convert timestamp to string if not already
                $firstTenDigits = substr($timestamp, 0, 10);
                $timestampArray[] = [
                    'timestamp' => date('Y-m-d H:i:s', $firstTenDigits), // Convert timestamp to date-time
                ];
            }

            $planid = Campaign::where('id', $campaign_id)->value('disposition_id');
            $maxLevel = Disposition::where('planid', $planid)->max('level');
            $dispositionData =  Disposition::select('planid','dispocode', 'disponame')
            ->where('client_id', $client_id)
            ->where('level', 1)
            ->where('planid', $planid)
            ->distinct()
            ->get();

            // $subDispositionData =  Disposition::select('sub_dispo_code', 'sub_dispo_name')
            // ->where('client_id', $client_id)
            // ->where('campaign_id', $campaign_id)
            // ->distinct()
            // ->get();
            
            $data = [
                'newchats' => $newchats,
                'newchatLogs' => $newchatLogs,
                'timestampArray' => $timestampArray,
                'dispositionData' => $dispositionData,
                'maxLevel' => $maxLevel,
                // 'subDispositionData' => $subDispositionData,
            ];

            Chat_log::where('chat_id', $id)->update([
                'is_read' => 2,

                // Add other columns and their new values as needed
            ]);
            return response()->json($data);
        } else {
            return view('login');
        } 
    }

    public function getchats_latest(Request $request)
    {   
        $sessionData = Session::get('data');
        // if(!$sessionData) {
        //     return redirect()->route('signout');
        // }
       
        if (session()->has('data')) {			
            $id = $request->input('id');
            $client_id = $request->input('client_id');
            $campaign_id = $request->input('campaign_id');
            
            $newchats = Chats::select('chats.*', 'clients.name as client_name', 'campaign.name as campaign_name')
                ->join('clients', 'chats.client_id', '=', 'clients.id')
                ->join('campaign', 'chats.campaign_id', '=', 'campaign.id')
                ->where('chats.status', 1)
                ->where('chats.id', $id)
                ->get();

           
            $newchatLogs = Chat_log::where('chat_id', $id)->get();
            $newchatLogsCount = Chat_log::where('chat_id', $id)->count();
            $timestampArray = [];
           
            foreach ($newchatLogs as $chat) {
                $timestamp = (string) $chat->timestamp; // Convert timestamp to string if not already
                $firstTenDigits = substr($timestamp, 0, 10);
                $timestampArray[] = [
                    'timestamp' => date('Y-m-d H:i:s', $firstTenDigits), // Convert timestamp to date-time
                ];
            }

            $planid = Campaign::where('id', $campaign_id)->value('disposition_id');
            $maxLevel = Disposition::where('planid', $planid)->max('level');
            $dispositionData =  Disposition::select('planid','dispocode', 'disponame')
            ->where('client_id', $client_id)
            ->where('level', 1)
            ->where('planid', $planid)
            ->distinct()
            ->get();

            // $subDispositionData =  Disposition::select('sub_dispo_code', 'sub_dispo_name')
            // ->where('client_id', $client_id)
            // ->where('campaign_id', $campaign_id)
            // ->distinct()
            // ->get();
            
            $data = [
                'newchats' => $newchats,
                'newchatLogs' => $newchatLogs,
                'timestampArray' => $timestampArray,
                'dispositionData' => $dispositionData,
                'maxLevel' => $maxLevel,
                'newchatLogsCount' => $newchatLogsCount,
                // 'subDispositionData' => $subDispositionData,
            ];

            // Chat_log::where('chat_id', $id)->update([
            //     'is_read' => 2,

            //     // Add other columns and their new values as needed
            // ]);
            return response()->json($data);
        } else {
            return view('login');
        } 
    }


    public function check_new_entries()
    {
        $sessionData = Session::get('data');
        $userid = $sessionData['userID'];
        $campaignID = $sessionData['campaignID'];
        $Client_id = $sessionData['Client_id'];
        $hasNewEntries = false;
        $latestChatId = Chat_log::where('campaign_id', $campaignID)
            ->where('client_id', $Client_id)
            ->where('assigned_to', $userid)
            ->orderBy('timestamp', 'desc')
            ->select('id')
            ->first();

        if ($latestChatId) {
            $latestChatId = $latestChatId->id;
            return response()->json([
                'hasNewEntries' => $hasNewEntries,
                'latestEntry' => $latestChatId
            ]);
            
        }
      
    }


    

    public function storechats(Request $request)
    {

        // return  $request;
        $sessionData = Session::get('data');
        $Client_id=$sessionData['Client_id'];
        $campaignID=$sessionData['campaignID'];
        $userMobile=$sessionData['userMobile'];
        $userID=$sessionData['userID'];

        $chatController = new ChatController();
        
        $campaignWpNumber = Campaign::where('id', $campaignID)->value('wp_number'); //fetch wp_number

        // Check if the user is authenticated
        if (session()->has('data')) {
            $id = $request->input('id');
            $msg = $request->input('msg');
            if($msg!=''){
                $message_type=1;
            }
            $client_name = $request->input('client_name');
            $campaign_name = $request->input('campaign_name');
            $mobile_number = $request->input('mobile_number');

            // Define allowed file extensions
            $allowed_ext = ["jpg", "jpeg", "png", "gif", "pdf", "doc", "docx", "xlsx", "xls", "csv", "mp3", "mp4", "m4a", "ogg"];
            
            $message_type = 1; // Default message type (assumed to be text)
            $message_type_name='text';
            // Get the current timestamp
            $timestamp = now('Asia/Kolkata')->timestamp;

            // Create the client directory if it doesn't exist
            $clientDirectoryPath = storage_path('app/public') . '/' . $client_name;
            if (!file_exists($clientDirectoryPath)) {
                mkdir($clientDirectoryPath, 0777, true);
            }

            // Create campaign, sender, and mobile number directories
            $campaignDirectoryPath = $clientDirectoryPath . '/' . $campaign_name;
            $senderDirectoryPath = $campaignDirectoryPath . '/send';
            $mobileDirectoryPath = $senderDirectoryPath . '/' . $mobile_number;

            if (!file_exists($campaignDirectoryPath)) {
                mkdir($campaignDirectoryPath, 0777, true);
            }
            if (!file_exists($senderDirectoryPath)) {
                mkdir($senderDirectoryPath, 0777, true);
            }
            if (!file_exists($mobileDirectoryPath)) {
                mkdir($mobileDirectoryPath, 0777, true);
            }
            // Create directories for images and documents
            $imageDirectoryPath = $mobileDirectoryPath . '/images';
            $documentDirectoryPath = $mobileDirectoryPath . '/documents';

            // Handle the file uploads
            if ($request->hasFile('upload')) {
                
                foreach ($request->file('upload') as $file) {
                //    return $fileCount = count($request->file('upload'));
                    $extension = $file->getClientOriginalExtension();
                    $filename = $file->getClientOriginalName(); 

                    if (in_array($extension, ["jpg", "jpeg", "png", "gif"])) {
                        $message_type = 2; // Image
                        $message_type_name='image';
                    } elseif (in_array($extension, ["mp4"])) {
                        $message_type = 3; // Video
                        $message_type_name='video';
                    } elseif (in_array($extension, ["pdf", "doc", "docx", "xlsx", "xls", "csv"])) {
                        $message_type = 4; // Document
                        $message_type_name='document';
                    } elseif (in_array($extension, ["mp3", "m4a", "ogg"])) {
                        $message_type = 5; // Audio
                        $message_type_name='audio';
                    }
                    // return $message_type;
                    // Check if the file has an allowed extension
                    if (in_array($extension, $allowed_ext)) {
                        // Determine the appropriate media folder
                        $mediaFolder = in_array($extension, ["jpg", "jpeg", "png", "gif"]) ? 'images' : 'documents';
                        
                        // Choose the correct directory path
                        $mediaDirectoryPath = $mediaFolder === 'images' ? $imageDirectoryPath : $documentDirectoryPath;

                        if (!file_exists($mediaDirectoryPath)) {
                            mkdir($mediaDirectoryPath, 0777, true);
                        }
                        // return $mediaDirectoryPath; 
                        // Store the uploaded file and get its path
                        //  $mediaPath = $file->store($mediaDirectoryPath);

                        // $uploadedFile = $request->file('upload'); // Assuming you're working with a file uploaded via a request
                        // $destinationPath = $mediaDirectoryPath; // The directory where you want to store the file
                       // Use the original filename or generate a unique one if needed

                        // Move the uploaded file to the destination directory
                        $file->move($mediaDirectoryPath, $filename);

                        // $destinationPath now contains the path where the file is stored
                        // $mediaPath = $mediaDirectoryPath . '/' . $filename;

                        $domain = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                        $domain .= '://' . $_SERVER['HTTP_HOST'];

                        // Assuming $filename contains the file name
                        $mainPath = $mediaDirectoryPath . '/' . $filename;

                        // Replace the local path with the dynamically obtained domain
                        $mediaPath = str_replace('/var/www/html', $domain, $mainPath);
                       
                        // $mainPath = $mediaDirectoryPath . '/' . $filename;
                        // $mediaPath = str_replace('/var/www/html', 'https://172.30.10.102', $mainPath);
                        // $mediaPath = asset('storage' . $mediaDirectoryPath . '/' . $filename);
                        // Create a Chatlog entry with the file path
                        // Chat_log::create([
                        //     'chat_id' => $id,
                        //     'message' => $msg,
                        //     'media_path' => $mediaPath,
                        //     'timestamp' => $timestamp,
                        //     'in_out' => 2, // Outgoing msg set 2
                        // ]);
                        
                            
                        $response = $chatController->sendAPIRequest($Client_id, $campaignID, $mobile_number, $userID, $id, $message_type_name, $msg, $mediaPath,$campaignWpNumber);

                        // Decode the JSON response
                        $responseData = json_decode($response, true);

                        // Check if decoding was successful
                        if ($responseData !== null) {
                            // Check the 'status' field in the response to determine success or failure
                            if (isset($responseData['status']) && $responseData['status'] === 'success') {
                                // Success handling
                                // Perform actions for a successful API response
                                // DB::insert(
                                //     'INSERT INTO chat_log (`chat_id`, `message`,`message_type`, `media_path`, `timestamp`, `in_out`,`is_sent`) VALUES (?, ?, ?, ?, ?, ?, ?)',
                                //     [$id, $msg, $message_type, $mediaPath, $timestamp, 2, 2]
                                // );
                                // echo "API request was successful!";
                                $resp = 'success';
                                // return  ['success', 'Files and message have been saved.'];
                            } else {
                                // Failure handling
                                // Perform actions for a failed API response
                                // echo "API request failed!";
                                $resp = 'Failed1';
                                // return  ['Failed', 'API request failed!'];
                            }
                        } else {
                            // Handle JSON decoding failure
                            // echo "JSON decoding failed!";
                            $resp = 'Failed2';
                            // return  ['Failed', 'JSON decoding failed!'];
                        }

                        

                    } else {
                        // Invalid file extension, handle accordingly (e.g., show an error message)
                        // return 'mahesh';
                        $resp = 'Failed3';
                        // return  ['Failed', 'Invalid file extension'];
                    }
                }
            } else {

                
                // If no files were uploaded, create a Chatlog entry without a media path
                $mediaPath ='';


                // return $Client_id. ', '.$campaignID.', '. $mobile_number.', '. $userID.', '. $id.', '. $message_type_name.', '. $msg.', '. $mediaPath;
                $response = $chatController->sendAPIRequest($Client_id, $campaignID, $mobile_number, $userID, $id, $message_type_name, $msg, $mediaPath,$campaignWpNumber);

                // Check if the response is not empty and contains valid JSON
                if (!empty($response)) {
                    $responseData = json_decode($response, true);

                    // Check if decoding was successful
                    if ($responseData !== null) {
                        // return $responseData['status'];
                        // Check the 'status' field in the response to determine success or failure
                        if (isset($responseData['status']) && $responseData['status'] === 'success') {
                            // Success handling
                            // Perform actions for a successful API response
                            // Chat_log::create([
                            //     'chat_id' => $id,
                            //     'message' => $msg,
                            //     'message_type' => $message_type,
                            //     'timestamp' => $timestamp,
                            //     'in_out' => 2, // Outgoing msg set 2
                            // ]);

                            // DB::table('chat_log')->insert([
                            //     'chat_id' => $id,
                            //     'message' => $msg,
                            //     'message_type' => $message_type,
                            //     'timestamp' => $timestamp,
                            //     'in_out' => 2, // Outgoing msg set 2
                            // ]);
                            $resp = 'success';
                            // return ['success', 'Files and message have been saved.'];
                        } else {
                            // Failure handling
                            // Perform actions for a failed API response
                            $resp = 'Failed1';
                            // return ['Failed', 'API request failed!'];
                        }
                    } else {
                        // Handle JSON decoding failure
                        $resp = 'Failed2';
                        // return ['Failed', 'JSON decoding failed!'];
                    }
                } else {
                    // Handle empty response
                    $resp = 'Failed3';
                    // return ['Failed', 'Empty response received!'];
                }


            }

            if($resp == 'success'){
                return ['success', 'Files and message have been saved.'];
            }elseif($resp == 'Failed1'){
                return ['Failed', 'API request failed!'];
            }elseif($resp == 'Failed2'){
                return ['Failed', 'JSON decoding failed!'];
            }else{
                return ['Failed', 'Empty response received!'];
            }

            // Success message after file handling
            
        } else {
            return view('login');
        }
    }

    public function sendAPIRequest($clientid, $campaignid, $mobile_number, $agentid, $chatid, $message_type, $msg, $mediaPath, $campaignWpNumber)
    {
       
        $apiUrl = 'https://edas-webapi.edas.tech/vaaniSM/send';
    
        // Constructing the request body as an associative array
        $body = array(
            'client_id' => $clientid,
            'campaign_id' => $campaignid,
            'mobile' => $mobile_number,
            'agent_id' => $agentid,
            'chatID' => $chatid,
            'message_type' => $message_type,
            'message' => $msg,
            'wp_number' => $campaignWpNumber,
            'mediaUrl' => $mediaPath
        );
    
        // Encode the body array as JSON
        $body_json = json_encode($body);
    
        // Initializing cURL session
        $curl = curl_init($apiUrl);
    
        // Set the necessary cURL options
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body_json);
    
        // Execute cURL request
        $response = curl_exec($curl);
    
        // Check for errors and handle the response
        if ($response === false) {
            $error = curl_error($curl);
            // Handle error
            return $error; // You might want to handle this error condition appropriately
        }
    
        // Close cURL session
        curl_close($curl);
    
        return $response;
    }
    


    public function msg_closedFun(Request $request)
    {
        $sessionData = Session::get('data');
        $id = $request->input('id');
        // Check if the user is authenticated
        if (session()->has('data')) {
           
            $client_id = $request->input('client_id');
            $campaign_id = $request->input('campaign_id');
             $dispo = $request->input('dispo');
            $sub_dispo = $request->input('sub_dispo');
            $remarks = $request->input('remarks');

            // Get the current timestamp
            $timestamp = now('Asia/Kolkata')->format('Y-m-d H:i:s');

            Chats::where('id', $id)->update([
                'is_closed' => 2,
                'closed_at' => $timestamp,
                'closed_by' => $sessionData['userID'],
                'dispo' => $dispo,
                'sub_dispo' => $sub_dispo,
                'remark' => $remarks,

                // Add other columns and their new values as needed
            ]);
            // Success message after file handling
            return redirect()->back()->with('success', 'Chat closed successfully');
        } else {
            return view('login');
        }
    }


    public function search_chats(Request $request)
    {   
       
        $sessionData = Session::get('data');
        // if (!$sessionData) {
        //     // Create an instance of the LoginController
        //     $loginController = app(LoginController::class);
            
        //     // Call the signout method
        //     return $loginController->signout($request);
        // }
       
        
        $searchcust = $request->input('searchcust');
        if($searchcust == 'ALL'){
            // $chats = Chats::where('status', 1)
            // ->where('is_closed', 1)
            // ->where('assigned_to', $sessionData['userID'])
            // ->get();
            $chats = Chats::select('chats.*', 'campaign.interaction_per_user')
            ->leftJoin('campaign', 'chats.campaign_id', '=', 'campaign.id')
            ->where('chats.assigned_to', $sessionData['userID'])
            ->where('chats.client_id', $sessionData['Client_id'])
            ->where('chats.campaign_id', $sessionData['campaignID'])
            ->where('chats.status', 1)
            ->where('campaign.status', 1)
            ->where('chats.is_closed', 1)
            ->get();
        }else{
            // $chats = Chats::where('status', 1)
            // ->where('customer_name', 'like', '%' . $searchcust . '%')
            // ->where('is_closed', 1)
            // ->where('assigned_to', $sessionData['userID'])
            // ->get();
            $chats = Chats::select('chats.*', 'campaign.interaction_per_user')
            ->leftJoin('campaign', 'chats.campaign_id', '=', 'campaign.id')
            ->where('chats.assigned_to', $sessionData['userID'])
            ->where('chats.client_id', $sessionData['Client_id'])
            ->where('chats.campaign_id', $sessionData['campaignID'])
            ->where('customer_name', 'like', '%' . $searchcust . '%')
            ->where('chats.status', 1)
            ->where('campaign.status', 1)
            ->where('chats.is_closed', 1)
            ->get();
        }
           
        $chatlogArray = [];
        $chatlogArray1 = [];
        $chatlogArray2 = [];
        foreach($chats as $chat){
            $chatid = $chat->id;
            $chatslogs = Chat_log::where('is_read', 1)->where('chat_id', $chatid)->count();
            $chatlogArray[$chatid] = $chatslogs;


            $latestChatLog = Chat_log::where('chat_id', $chatid)
                ->orderBy('id', 'desc')
                ->select('chat_id','timestamp', 'message', 'media_path', 'created_at')
                ->first();

                if ($latestChatLog) {
                    // $timestamp = $latestChatLog->timestamp;
                    $firstTenDigits = (string) $latestChatLog->timestamp; // code by mahesh
                    $timestamp = substr($firstTenDigits, 0, 10); // code by mahes
                    $message = $latestChatLog->message;
                    $created_at = $latestChatLog->created_at;
                    $media_path = $latestChatLog->media_path;
            
                    $chatTime = date('H:i ', $timestamp);
                    if ($message != '') {
                        $msg = $message;
                    } elseif ($media_path != '') {
                        $path = asset("assets/agent_ui/img/img.png");
                        $msg = '<img src="'.$path.'" alt="image" height="15px" width="15px">'.' Attachment';
                    } else {
                        $msg = '';
                    }
            
                    // Create an array for each chat log data
                    $chatlogArray1 = [
                        'chat_id' => $chatid,
                        'chatTime' => $chatTime,
                        'timestamp' => $timestamp,
                        'msg' => $msg,
                        'created_at' => $created_at,
                    ];
            
                    // Push the chat log data into the main array
                    $chatlogArray2[] = $chatlogArray1;
                }
        }

        $data = [
            'newchats' => $chats,
            'chatlogArray' => $chatlogArray,
            'chatlogArray2' => $chatlogArray2,
        ];


        // start code by mahesh 14 jan 2024
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
                        $lastBreakLog1->save();
                    }
        
                }
            // update end time for every 5 seconds login and break where activity_statu is 1 code by mahesh end
        // end code 14 jan 2024
        return response()->json($data);
        
    }


    public function get_sub_dispo(Request $request)
    {   
        $disponame = $request->input('disponame');
        $client_id = $request->input('client_id');
        $campaign_id = $request->input('campaign_id');
        $planid = $request->input('planid');
           
        $subDispositionData =  Disposition::select('dispocode', 'disponame')
            ->where('parent_id', $disponame)
            ->where('level', 2)
            ->where('planid', $planid)
            ->where('client_id', $client_id)
            ->distinct()
            ->get();

        
        $data = [
            'subDispositionData' => $subDispositionData,
        ];
        return response()->json($data);
        
    }

    public function get_sub_sub_dispo(Request $request)
    {   
        $subdisponame = $request->input('subdisponame');
        $client_id = $request->input('client_id');
        $campaign_id = $request->input('campaign_id');
        $planid = $request->input('planid');
           
        $subsubDispositionData =  Disposition::select('dispocode', 'disponame')
            ->where('parent_id', $subdisponame)
            ->where('level', 3)
            ->where('planid', $planid)
            ->where('client_id', $client_id)
            ->distinct()
            ->get();

        
        $data = [
            'subsubDispositionData' => $subsubDispositionData,
        ];
        return response()->json($data);
        
    }

    
    
}
