<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Util;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Models\Chats;
use App\Models\Chat_log;
use App\Models\Api_log;
use App\Traits\CommonTraits;
use App\Traits\ApiTraits;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Carbon\Carbon;
// use App\Traits\PanasonicTraits;


class GupshupWebhookPanasonicController extends Controller
{
	use ApiTraits;
	use CommonTraits;
	// use PanasonicTraits;
	
	public $url = 'https://media.smsgupshup.com/GatewayAPI/rest';
	public $wp_number = '';
	public $hsm_userid = '';
	public $hsm_password = '';
	public $twoway_userid = '';
	public $twoway_password = '';
	public $auth_scheme	= 'plain';
	public $version	= '1.1';
	public $data_encoding = 'Unicode_text';
	public $globalTimestamp = '';
	public $whatsappcrud = array();
	
	public function __construct()
    {
		$this->url           	= Config::get('custom.gupshup.whatsapp.send-api.url');
		$this->wp_number		= Config::get('custom.gupshup.whatsapp.send-api.wp_number');
		$this->hsm_userid		= Config::get('custom.gupshup.whatsapp.send-api.hsm-userid');
		$this->hsm_password		= Config::get('custom.gupshup.whatsapp.send-api.hsm-password');
		$this->twoway_userid	= Config::get('custom.gupshup.whatsapp.send-api.twoway-userid');
		$this->twoway_password	= Config::get('custom.gupshup.whatsapp.send-api.twoway-password');
		$this->globalTimestamp	= round(microtime(true) * 1000);
		
		$this->whatsappcrud = Config::get('custom.gupshup.whatsapp');
	}
	
	public function getcrud($waNumber)
	{
		$this->url           	= $this->whatsappcrud[$waNumber]['url'];
		$this->wp_number		= $this->whatsappcrud[$waNumber]['wp_number'];
		$this->hsm_userid		= $this->whatsappcrud[$waNumber]['hsm-userid'];
		$this->hsm_password		= $this->whatsappcrud[$waNumber]['hsm-password'];
		$this->twoway_userid	= $this->whatsappcrud[$waNumber]['twoway-userid'];
		$this->twoway_password	= $this->whatsappcrud[$waNumber]['twoway-password'];
		
		$campaign_data = DB::table('campaign')
                ->select('id', 'name', 'client_id', 'auto_reply_id', 'allocation_type', 'call_window_from', 'call_window_to', 'working_days', 'holiday_start', 'holiday_end', 'holiday_name', 'wp_crud')
                ->whereIn('status', [0,1])
                ->where('wp_number', $waNumber)
                ->first();
				
		return $wp_crud	= isset($campaign_data->wp_crud) && !empty($campaign_data->wp_crud) ? $campaign_data->wp_crud : [];
	}
	//Request $request
    public function handleWebhook(Request $request)
    {		
		$uniqueID = round(microtime(true) * 1000);
		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '2048M');
		$log = Log::channel('wp_webhooks');
		$log->debug(__line__."\n\n ---- Start handleWebhook - for ". $uniqueID ." at : ". date("l jS \of F Y h:i:s A"));
		$data = $request->all();
		
		$newChat	= false;
        $eventType 	= 'message';
		$mobile 	= $data['mobile'];
		$type 		= $data['type'];
		$name 		= $data['name'];
		$waNumber 	= $data['waNumber'];
		$timestamp 	= isset($data['timestamp']) ? $data['timestamp'] : $this->globalTimestamp;
		$uniqueUuid = CommonTraits::uuid();
		$mediaUrl 	= $mediaCaption = $message_id = $mediaCaption = $text = $publicUrl = '';
		$templateID = 0;
		
		$this->getcrud($waNumber);
		$log->debug(__line__.'Webhook Row Data : ' . print_r($data, true));
		$log->debug(__line__.'wp_number : ' . $this->wp_number);
		
		// $basePath = url();
		// $log->debug(__line__.'basePath : ' . $basePath);
		// $currentTime = time();
		// Validate the incoming request, ensuring it comes from Gupshup
		if (!($mobile && $type && $timestamp && $waNumber)) {
			$log->debug(__line__.'Error : Invalid Data Received');			
            return response()->json(['error' => 'Invalid Data Received'], 400);
        }
		
		try {
			
			$campaign_data = DB::table('campaign')
                ->select('id', 'name', 'client_id', 'auto_reply_id', 'allocation_type', 'call_window_from', 'call_window_to', 'working_days', 'holiday_start', 'holiday_end', 'holiday_name', 'wp_crud')
                ->whereIn('status', [0,1])
                ->where('wp_number', $waNumber)
                ->first();
				
			 $log->debug(__LINE__.'Qeury campaign_data : ' . print_r($campaign_data, true));
			
			$client 			= isset($campaign_data->client_id) ? $campaign_data->client_id : '1001';				
			$campaign 			= isset($campaign_data->id) ? $campaign_data->id : '2001';
			$alloc_type			= isset($campaign_data->allocation_type) ? $campaign_data->allocation_type : '';
			$a_reply_id			= isset($campaign_data->auto_reply_id) ? $campaign_data->auto_reply_id : '';
			$call_window_from	= isset($campaign_data->call_window_from) ? $campaign_data->call_window_from : '';
			$call_window_to		= isset($campaign_data->call_window_to) ? $campaign_data->call_window_to : '';
			$working_days		= (isset($campaign_data->working_days) && !empty($campaign_data->working_days)) ? explode(',',$campaign_data->working_days) : [];
			$holiday_start		= isset($campaign_data->holiday_start) ? $campaign_data->holiday_start : '';
			$holiday_end		= isset($campaign_data->holiday_end) ? $campaign_data->holiday_end : '';
			$holiday_name		= isset($campaign_data->holiday_name) ? $campaign_data->holiday_name : '';
			$campaignName		= isset($campaign_data->name) ? $campaign_data->name : '';
			$wp_crud			= isset($campaign_data->wp_crud) && !empty($campaign_data->wp_crud) ? $campaign_data->wp_crud : [];
			$wp_number			= isset($waNumber) ? $waNumber : '';

			// Generate Message ID if not present
			$message_id 		= isset($data['messageId']) ? $data['messageId'] : CommonTraits::uuid();
			$reply_id 			= isset($data['replyId']) ? $data['replyId'] : "";
			$optinFlag 			= false;
			$newChat 			= false;
			$interactiveFlag 	= false;
			$interactiveData	= [];
			$chatDetails		= $this->getChatDetails($waNumber, $mobile);
			$chatID				= isset($chatDetails['id']) ? $chatDetails['id'] : '';
			$crudDetails 		= ['wp_number'=> $waNumber, 'wp_crud'=>$wp_crud];
			$log->debug(__LINE__.'Step1 - Chat : '.$chatID);
			if(empty($chatID))
			{				
				$chatID		= $this->addNewChat($waNumber, $mobile, $client, $campaign, $name);
				$newChat 	= true;
				$chatDetails= $this->getChatDetails($waNumber, $mobile);
				$log->debug(__LINE__.'Step2 : ');
			}
			$opID  = '';
			
			
			// Handle the event based on its type
			if ($eventType === 'message') {	
				if (preg_match('/image|document|voice|audio|video/', $type)) 
				{				
					$log->debug(__LINE__.'data type: '. print_r($data, true));
						$media  	= (is_array($data[$type])) ? $data[$type] : json_decode($data[$type], true);
						$mediaUrl 	= $media['url']. $media['signature'];
						$text		= isset($media['caption']) ? $media['caption'] : '';
						$originalFilename 	= pathinfo($mediaUrl, PATHINFO_BASENAME);
						$uniqueFilename 	= strtoupper(substr($type, 0, 3)). $message_id. ".". $this->mime2ext($media['mime_type']);
						$storagePath 		= "{$client}/{$campaign}/received/{$mobile}/{$type}";
						$destinationFilePath= "{$storagePath}/{$uniqueFilename}";
						try {
							if (!Storage::disk('public')->exists($storagePath)) {
								Storage::disk('public')->makeDirectory($storagePath);
								$log->debug(__LINE__.'Dir Created : ' . $storagePath);
							}else{
								$log->debug(__LINE__.'Dir exits :'. $storagePath);
							}
						} catch (\Exception $e) {
							$log->debug(__LINE__.'Error creating directory : ' . $e->getMessage());
						}
					
						// Download the file
						$response = Http::get($mediaUrl);					
						$log->debug(__LINE__.'Download file response  : ' . json_encode($response));

						// Check if the download was successful
						if ($response->successful()) {
							$publicPath = Storage::disk('public')->put($destinationFilePath, $response->body());
							$url 		= request()->root();
							$parsedUrl 	= parse_url($url);
							$baseUrl 	= $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
							$publicUrl = $baseUrl."/storage/app/public/{$storagePath}/{$uniqueFilename}";						
							$log->debug(__LINE__.'Media received and stored: ' . $publicUrl);
							
						} else {
							$log->debug(__LINE__.'Media failed to received and stored');
						}					
				}
				else if($type == 'text') {
					$text = $data['text'];		
				}
				else if($type == 'interactive') {
					$interactiveFlag = true;		
					$interactiveData = json_decode($data['interactive'], true);	
					$intID 		= $interactiveData[$interactiveData['type']]['id'];
					$expIntID 	= explode('-',$intID);
					$opID 		= (isset($expIntID[0]) && $expIntID[0] != 'mm') ? $expIntID[0] : 'mm';
					
					$log->debug(__LINE__.'interactive --- : '.$intID);
					$log->debug(__LINE__.'opID --- : '.$opID);
				}
			}
			
			// Insert new chat into chat_log table
			$chat_log_data = new Chat_log();
			$chat_log_data->chat_id = $chatID;
			$chat_log_data->timestamp = $timestamp;
			$chat_log_data->message_id = $message_id;
			$chat_log_data->in_out = 1;
			$chat_log_data->message_type = CommonTraits::MessageType($type);
			$chat_log_data->event = '';
			$chat_log_data->message = $text;
			$chat_log_data->reply_id = $reply_id;
			$chat_log_data->media_path = $publicUrl;
			$chat_log_data->is_delivered = 1;
			$chat_log_data->is_read = 1;
			$chat_log_data->is_deleted = 1;
			$chat_log_data->template_id = $templateID;
			$chat_log_data->save();	
			
			$log->debug(__LINE__.' newChat : ' . $newChat);		
		 	
		//End chat when type end
		if((($chatDetails['assigned_to'] == null || empty($chatDetails['assigned_to'])) && strtolower($text) == 'end' && $chatDetails['is_closed'] == '1') || $opID == 'end')
			{					
				$reqData['client_id']	= $client;
				$reqData['campaign_id'] = $campaign;
				$reqData['mobile'] 		= $mobile;
				$reqData['agent_id'] 	= 0;
				$reqData['chatID']		= $chatID;
				$reqData['message_type']= 'text'; 
				$reqData['event'] 		= '';
				$reqData['message'] 	= 'Thank you, LiveChat session has been ended';
				$reqData['reply_id'] 	= '';
				$reqData['mediaUrl'] 	= '';					
				// $autRepMsg = $this->sendMsg($reqData);
				$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
				$log->debug(__LINE__.'sendWPMsg Res : '. print_r($autRepMsg, true));
				$this->closedChat($client, $campaign, $chatID, $mobile);
				$log->debug(__LINE__.'End the chat ID : '.$client);				
			} 
			else if($chatDetails['assigning_flag'] == 1){			
			//Auto Reply Functionality		
				if($newChat === true || $opID == 'mm')
				{	
					$log->debug(__LINE__.' newChat : ' . $newChat);		
					$autRepMsg = [];		
					$templates = DB::table('templates')
						->select('id','caption', 'media_url', 'name', 'footer', 'list', 'msg_type', 'intractive_type')
						->where('id', $a_reply_id)
						->whereIn('status', [0,1])
						->first();	

					$log->debug(__LINE__.'a_reply_id  : ' . $a_reply_id);	
					$log->debug(__LINE__.'templates Data  : ' . print_r($templates, true));	
					
					if($templates)
					{
						$caption 	= $templates->caption;
						$media_url 	= $templates->media_url;
						$name 		= $templates->name;
						$footer 	= $templates->footer;
						$list 		= $templates->list;
						$msg_type 	= $templates->msg_type;
						$intractive_type = $templates->intractive_type;
						
						$reqData['client_id']	= $client;
						$reqData['campaign_id'] = $campaign;
						$reqData['mobile'] 		= $mobile;
						$reqData['agent_id'] 	= 0;
						$reqData['chatID']		= $chatID;
						$reqData['message_type']= (empty($media_url) ? 'TEXT' : 'IMAGE'); 
						$reqData['event'] 		= '';
						$reqData['message'] 	= $caption;
						$reqData['reply_id'] 	= '';
						$reqData['mediaUrl'] 	= $media_url;	
						$reqData['templateType']= $msg_type;	
						$reqData['action'] 		= $list;	
						$reqData['interactive_type']= $intractive_type;	
						$reqData['footer'] 		= $footer;
						$reqData['templateID']	= $a_reply_id;
						
						$log->debug(__line__.'reqData : ' . print_r($reqData, true));
						// $autRepMsg = $this->sendTemplate($reqData);
						$autRepMsg = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);
						$log->debug(__LINE__.'response autRepMsg : ' . print_r($autRepMsg, true));
													
					}
					
					$this->updateSetting($client, $campaign, $chatID, ['lsto' => '']);
				}				
				else if(!in_array($opID, ['mm', 'end', 'ttx']))
				{
					$settingArr = $this->getSetting($client, $campaign, $chatID);			
					if($interactiveFlag === true)
					{					
						if(!empty($interactiveData))
						{
							$optionID = $interactiveData[$interactiveData['type']]['id'];
							$option = $interactiveData[$interactiveData['type']]['title'];
							$log->debug(__LINE__.'Step10 new Chat option : '.$option);
							if(empty($settingArr['lsto']) || $settingArr['lsto'] == $option){
								$iOprionReply = $this->interactiveReply($crudDetails, $option, $client, $campaign, $mobile, $chatID, $optionID);	
							}
							else{
								//$text =	$option;		
								$this->interactiveReply($crudDetails, $settingArr['lsto'], $client, $campaign, $mobile, $chatID, $optionID, $option);							
							}	
							$log->debug(__LINE__.'Step10 new Chat option--- : ');
						}
						
					}
					
					$log->debug(__LINE__.'settingArr : '. print_r($settingArr, true));
					if(!empty($settingArr['lsto']) && !empty($text) && !in_array(strtolower($text), ['end']))
					{
						$this->interactiveReply($crudDetails, $settingArr['lsto'], $client, $campaign, $mobile, $chatID, '', $text);		
					}

					$log->debug(__LINE__.' text msg  : ' . $text);						
				}
				else{
					$this->updateSetting($client, $campaign, $chatID, ['lsto' => '']);
				}	
			}
			
			$log->debug(__LINE__.'Webhook received and processed');
			
			// Log API response
			// $apiLogID  = $this->logAPIRowData($waNumber, $mobile, $client, $mobile, $campaign, $chat_log_data->id, $data, 'chat_log', 'WEBHOOK');			
			// $log->debug(__line__.'Add new record in api_log for ID : '.$apiLogID);
			
         } catch (\Exception $e) {
            // Log any exceptions or errors
			$log->debug(__LINE__.'Error - An error occurred: ' . $e->getMessage());
           // return response()->json(['error' => 'An error occurred'], 500);
        } 
		$log->debug(__LINE__.'Webhook received and processed');
		
		$log->debug(__LINE__.'-----End Webhook-----------\n');
		
		//return response()->json(['status' => 200, 'msg'=>'Webhook received and processed']);
    }
	
	public function test()
	{
		$result = $this->getChatDetails('919987294336', '917738691223');
		
		echo "<pre>"; print_r($result['assigning_flag']);die;
	}
	
	public function interactiveReply($crudDetails, $option, $client, $campaign, $mobile, $chatID, $optionID, $searchInput = '')
	{
		$log = Log::channel('wp_webhooks');		
		$log->debug(__line__."Start interactiveReply ------------ ");
		$log->debug(__line__."option -- ".$option);
		$log->debug(__line__."optionID -- ".$optionID);
		$log->debug(__line__."searchInput -- ".$searchInput);
		$log->debug(__line__."crudDetails  -- ".print_r($crudDetails, true));
		
		
		$returnData = [];
		switch ($option) {
			case "Product Category":
				$searchInput = ($searchInput != 'Product Category') ? $searchInput : '';
				$returnData =  $this->productCatalog($crudDetails, $client, $campaign, $mobile, $chatID, $optionID,$searchInput);
				break;

			case "Product Catalogue":
				$searchInput = ($searchInput != 'Product Catalogue') ? $searchInput : '';
				$returnData =  $this->productCatalog($crudDetails, $client, $campaign, $mobile, $chatID, $optionID,$searchInput);
				break;

			case "Retailer":
				$searchInput = ($searchInput != 'Retailer') ? $searchInput : '';			
				$returnData =  $this->locateRetailer($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);
				break;

			case "Electrician":
				$searchInput = ($searchInput != 'Electrician') ? $searchInput : '';
				$returnData =  $this->locateElectrician($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);
				break;

			case "Talk to our experts":
				$searchInput = ($searchInput != 'Talk to our experts') ? $searchInput : '';
				$returnData =  $this->assignToExpert($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
				break;

			case "End session":
				$searchInput = ($searchInput != 'End session') ? $searchInput : '';
				$returnData =  $this->endChat($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput); 				
				break;

			case "Check Points":
				$searchInput = ($searchInput != 'Check Points') ? $searchInput : '';
				$returnData =  $this->getPointsSmartSaver($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);		
				break;

			case "Points/SKU":
				$searchInput = ($searchInput != 'Points/SKU') ? $searchInput : '';
				$returnData =  $this->SKU($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
				break;

			case "Program Information":
				$searchInput = ($searchInput != 'Program Information') ? $searchInput : '';
				$returnData =  $this->programInformation($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
				break;

			case "Promotion & Offers":
				$searchInput = ($searchInput != 'Promotion & Offers') ? $searchInput : '';
				$returnData =  $this->assignToExpert($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);			
				break;

			case "Training Videos":
				$searchInput = ($searchInput != 'Training Videos') ? $searchInput : '';
				$returnData =  $this->training($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
				break;

			case "Register Complaint":
				$searchInput = ($searchInput != 'Register Complaint') ? $searchInput : '';
				$returnData =  $this->helpLineNo($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
				break;

			case "Help Section":
				$searchInput = ($searchInput != 'Help Section') ? $searchInput : '';
				$returnData =  $this->helpSection($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
				break;

			case "Request a call back":
				$searchInput = ($searchInput != 'Request a call back') ? $searchInput : '';
				$returnData =  $this->helpLineNo($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
				break;


			default:
				return ['status' => 'failed', 'msg' => 'Invalid Response'];
				// Handle unknown message type or provide an error message
				break;
		}
		
		if(!empty($returnData) && isset($returnData['status']))
		{
			if($returnData['status'] != 'success')
			{
				$this->updateSetting($client, $campaign, $chatID, ['lsto' => $option]);
			}
		}
	}
	
	public function validateMobile($mobile) {
		$pattern = '/^\+91\d{10}$/';
		
		// Check if the mobile number matches the pattern
		return preg_match($pattern, $mobile);
	}
	public function getPointsSmartSaver($crudDetails, $client, $campaign, $mobile, $chatID, $isOption = false, $input = '')
	{
		$log = Log::channel('wp_webhooks');		
		$log->debug(__line__."Welcome getPointsSmartSaver Function ------------ ");
		
		$textMsg	= '';
		$reqData['client_id']	= $client;
		$reqData['campaign_id'] = $campaign;
		$reqData['mobile'] 		= $mobile;
		$reqData['agent_id'] 	= 0;
		$reqData['chatID']		= $chatID;
		$reqData['message_type']= 'text'; 
		$reqData['event'] 		= '';
		$reqData['reply_id'] 	= '';
		$reqData['mediaUrl'] 	= '';
		$autRepMsg = [];
		if(empty($input))
		{	
			$log->debug(__line__.' Step 1');
			$reqData['message'] = 'Please enter 10 digit mobile number';		
			$autRepMsg 	= $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
			$returnArr 	= ['status' => 'initiate', 'msg' => 'Initiated'];
		}
		else
		{
			$log->debug(__line__.' Step 2');
			$mobileNo = '+91'.$input; //$phone = urlencode('+919315806968');
			if($this->validateMobile($mobileNo))
			{
				$log->debug(__line__.' Step 3');
				$authURL 	= 'https://api-anchor.testing.thinkcap.in/auth';
				$PointsURL 	= 'https://api-anchor.testing.thinkcap.in/user/get-points';
				$key		= 'otherbzs2qp6itwjv8ty1k9u5mcfupgu9dtg';
				$headers = array(
					'api-version:3', 
					'version:2.8.0', 
					'app-name:Anchor', 
					'lang:en', 
					'tz:Asia/Kolkata', 
					'platform:other',
				);
					
				$postData = array(
					'key' => $key
				);
				$reqData['message'] = '🕐 Plesae wait, Processing your request...';		
				$autRepMsg 	= $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						
				$authRes = ApiTraits::curlHit($authURL, $postData, 'POST', $headers);
				// $log->debug(__line__.' headers : ' . print_r($headers, true));
				// $log->debug(__line__.' postData : ' . print_r($postData, true));
				// $log->debug(__line__.' authRes : ' . print_r($authRes, true));
				if($authRes['status'] != 'failed')
				{
					$log->debug(__line__.' Step 4');
					$response = json_decode($authRes['response'], true);
					if(isset($response['isSuccess']) && $response['isSuccess'] === true)
					{
						/* sleep(2);
						$reqData['message'] = 'I appreciate your patience, please wait..';		
						$autRepMsg 	= $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						sleep(2); */
						$headers[] = 'Authorization:Bearer '.$response['respData']['token'];		
						$res = ApiTraits::curlHit($PointsURL, ['phone' => $mobileNo], 'GET', $headers);
						//$log->debug(__line__.' headers : ' . print_r($headers, true));
						// $log->debug(__line__.' headers : ' . print_r($headers, true));
						// $log->debug(__line__.' authRes : ' . print_r($res, true));
						
						if($res['status'] != 'failed')
						{
							if(!empty($response['body']['full_name']))
							{
								sleep(2);
								$log->debug(__line__.' Step 5');
								$response = json_decode($res['response'], true);
								$reqData['message'] = 'See the details below.';		
								$autRepMsg 	= $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
								sleep(2);
								$reqData['message'] = 'Name : '.$response['body']['full_name'];		
								$autRepMsg 	= $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
								sleep(1);
								$reqData['message'] = 'Points : '.$response['body']['points'];
								$autRepMsg 	= $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
								$returnArr 	= ['status' => 'success', 'msg' => 'success'];
							}
							else{
								sleep(2);
								$reqData['message'] = 'No record found against given number! 😞';		
								$autRepMsg 	= $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
							}
						}
						else{
							sleep(2);
							$log->debug(__line__.' Step 6');
							$reqData['message'] = 'No data found!';		
							$autRepMsg 	= $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
							$returnArr 	= ['status' => 'success', 'msg' => 'success'];
						}
					}
					else{
						sleep(2);
						$log->debug(__line__.' Step 7');
						$reqData['message'] = 'No data found!';		
						$autRepMsg 	= $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						$returnArr 	= ['status' => 'initiate', 'msg' => 'Initiated'];
					}
				}
				else{
					sleep(2);
					$log->debug(__line__.' Step 8');
					$reqData['message'] = 'No data found!';		
					$autRepMsg 	= $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
					$returnArr 	= ['status' => 'initiate', 'msg' => 'Initiated'];
				}
			}
			else
			{	
				sleep(2);		
				$log->debug(__line__.' Step 9');
				$reqData['message'] = 'Please Provide 10 digit valid mobile number!';		
				$autRepMsg 	= $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
				$returnArr 	= ['status' => 'initiate', 'msg' => 'Initiated'];
			}
			
			sleep(2);
			$options =['mm'=>'Main menu', 'end'=>'End session'];	
			$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, 'Please select the option for proceed further. ', 'TEXT', $options);

		}
		
		$log->debug(__line__.'sendWPMsg Res : '.print_r($autRepMsg, true));
									
		return $returnArr;				
	}
	
	
	public function endChat($crudDetails, $client, $campaign, $mobile, $chatID, $isOption = false, $input = '')
	{
		$returnArr 	= ['status' => '', 'msg' => ''];
		$reqData['client_id']	= $client;
		$reqData['campaign_id'] = $campaign;
		$reqData['mobile'] 		= $mobile;
		$reqData['agent_id'] 	= 0;
		$reqData['chatID']		= $chatID;
		$reqData['message_type']= 'text'; 
		$reqData['event'] 		= '';
		$reqData['message'] 	= 'Thank you, LiveChat session has been ended';
		$reqData['reply_id'] 	= '';
		$reqData['mediaUrl'] 	= '';					
		// $autRepMsg = $this->sendMsg($reqData);
		$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
		
		$this->closedChat($client, $campaign, $chatID, $mobile);
		return $returnArr = ['status' => 'success', 'msg' => 'success'];
	}
	
		
	public function productCatalog($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = '')
	{
		$log = Log::channel('wp_webhooks');
		$log->debug(__line__."\n\n productCatalog ------------ \n");
		$log->debug(__line__."\n\n optionID With input ------------ ".$optionID);
		$log->debug(__line__."\n\n input ------------ ".$input);
		$returnArr 	= ['status' => '', 'msg' => ''];
		$textMsg 	= '';
		$contFlag = false;
		if(empty($input))
		{
			//send product catelog
			// $product 	= $query->get();
			$textMsg	= 'Enter Product Name';
			$reqData['client_id']	= $client;
			$reqData['campaign_id'] = $campaign;
			$reqData['mobile'] 		= $mobile;
			$reqData['agent_id'] 	= 0;
			$reqData['chatID']		= $chatID;
			$reqData['message_type']= 'text'; 
			$reqData['event'] 		= '';	
			$reqData['message'] 	= $textMsg;
			$reqData['reply_id'] 	= '';
			$reqData['mediaUrl'] 	= '';					
			// $this->sendMsg($reqData);
			
			$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
			$log->debug(__line__.'sendWPMsg Res : '.print_r($autRepMsg, true));
			$returnArr 	= ['status' => 'initiate', 'msg' => 'Initiated'];				
		}
		else{
			if(empty($optionID))
			{
				$textMsg = 'Please choose one of the option below.';
				$product = DB::connection('panasonic_connection')
					->table('product')
					->select('id', 'name', 'desc')
					->where(function($query) use ($input) {
						$query->where('name', 'like', '%' . $input . '%')
							  ->orWhere('desc', 'like', '%' . $input . '%');
					})
					->whereIn('status', [0,1])
					->limit(9)
					->get();
					
				$header = "Product List";
				$log->debug(__line__.'Product List count : '.$product->count());
				if($product->count() == 0){
					
					$product = DB::connection('panasonic_connection')
					->table('product_category')
					->select('id', 'name', 'desc')
					->where(function($query) use ($input) {
						$query->where('name', 'like', '%' . $input . '%')
							  ->orWhere('desc', 'like', '%' . $input . '%');
					})
					->whereIn('status', [0,1])
					->limit(9)
					->get();
					$log->debug(__line__.'Product Category count : '.$product->count());
					if($product->count() == 0){
					
						$product = DB::connection('panasonic_connection')
						->table('product_category')
						->select('id', 'name', 'desc')
						->whereIn('status', [0,1])
						->limit(9)
						->get();
						
						$textMsg = 'No match found, please choose product category.';
					}
					
					$header = "Product Category";
					$log->debug(__line__.'Product Category List  : '.print_r($product, true));
					if(!empty($product)) 
					{					
						$listArr=["button" => $header, "sections" => []];
						$rows 	=[];
						$id 	=0;
						foreach ($product as $k => $val) {
							
							$id = $val->id;
							if($k <= 7)
							{
								$rows[] = [
									"id" => $k.'-s1-0-'.$id,
									"title" => $val->name,
									"description" => $val->desc
								];
							}
						}
						if(!empty($rows) && $product->count() > 8)
						{
							$rows[] = [
								"id" => 'n-s1-0-'.$id,
								"title" => 'Scroll more',
							];
						}
						else{
							$rows[] = [
								"id" => 'mm-s0-0-0',
								"title" => 'Main menu',
							];
							$rows[] = [
								"id" => 'end-s0-0-0',
								"title" => 'End session',
							];
						}
						
						$listArr["sections"][] = ["rows" => $rows];					
						$list = json_encode($listArr, JSON_PRETTY_PRINT);
						
						$reqData['client_id']	= $client;
						$reqData['campaign_id'] = $campaign;
						$reqData['mobile'] 		= $mobile;
						$reqData['agent_id'] 	= 0;
						$reqData['chatID']		= $chatID;
						$reqData['message_type']= 'TEXT'; 
						$reqData['event'] 		= '';
						$reqData['message'] 	= $textMsg;
						$reqData['reply_id'] 	= '';
						$reqData['mediaUrl'] 	= '';	
						$reqData['templateType']= 'LIST';	
						$reqData['action'] 		= $list;	
						$reqData['interactive_type']= 'list';	
						$reqData['footer'] 		= '';
						$reqData['templateID'] 	= 0;
						// $this->sendTemplate($reqData);
						$msgRes = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);
						$returnArr = ['status' => 'progress', 'msg' => 'progress'];
					}
				}
				else{
					
					$listArr 	= ["button" => "Product List", "sections" => []];
					$rows 		= [];
					//$maxID = 
					$totalCnt 	= $product->count();
					$id 		= 0;
					foreach ($product as $k => $val) {
						$id = $val->id;
						if($k <= 7)
						{
							$rows[] = [
								"id" => $k.'-s2-'.$id.'-0',
								"title" => $val->name,
								"description" => ($val->desc != null ? $val->desc : '')
							];
						}
					}
					if(!empty($rows) && $totalCnt > 8)
					{
						$rows[] = [
							"id" => 'n-s2-'.$id.'-0',
							"title" => 'Scroll more'
						];
					}								
					else{
						$rows[] = [
							"id" => 'mm-s0-0-0',
							"title" => 'Main menu',
						];
						$rows[] = [
							"id" => 'end-s0-0-0',
							"title" => 'End session',
						];
					}
						
					$listArr["sections"][] = ["rows" => $rows];
					
					$list = json_encode($listArr, JSON_PRETTY_PRINT);
					$log->debug(__line__."\n\n list --- \n" . $list);
					$log->debug(__line__."\n\n product --- \n" . json_encode($product));
					$reqData['client_id']	= $client;
					$reqData['campaign_id'] = $campaign;
					$reqData['mobile'] 		= $mobile;
					$reqData['agent_id'] 	= 0;
					$reqData['chatID']		= $chatID;
					$reqData['message_type']= 'TEXT'; 
					$reqData['event'] 		= '';
					$reqData['message'] 	= 'Please choose product.';
					$reqData['reply_id'] 	= '';
					$reqData['mediaUrl'] 	= '';	
					$reqData['templateType']= 'LIST';	
					$reqData['action'] 		= $list;	
					$reqData['interactive_type']= 'list';	
					$reqData['footer'] 		= '';
					$reqData['templateID'] 	= 0;
					// $this->sendTemplate($reqData);
					$msgRes = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);	
					$returnArr = ['status' => 'progress', 'msg' => 'progress'];					
				}
			}
			else{
				//$k.'-s1-0-'.$id     'n-s1-0-'.$id
				$exploxdID 	= explode('-',$optionID);
				$index 		= (isset($exploxdID[0]) && $exploxdID[0] != 'n') ? $exploxdID[0] : 'n';
				$preID 		= isset($exploxdID[1]) ? $exploxdID[1] : '';
				$maxID 		= isset($exploxdID[2]) ? $exploxdID[2] : 0;
				$catID 		= isset($exploxdID[3]) ? $exploxdID[3] : 0;
				$menuKey 	= isset($exploxdID[4]) ? $exploxdID[4] : '';
				if($preID == 's2' && $index != 'n')
				{
					$product = DB::connection('panasonic_connection')
					->table('product')
					->select('name', 'desc', 'url', 'file_type')
					->where('name', 'like','%' . $input . '%')
					->whereIn('status', [0,1])
					->first();	
					$log->debug(__line__."\n\n product optionID------------". json_encode($product));				
					if($product){
						$log->debug(__line__."\n\n product isOption------------". json_encode($product));
						$reqData['client_id']	= $client;
						$reqData['campaign_id'] = $campaign;
						$reqData['mobile'] 		= $mobile;
						$reqData['agent_id'] 	= 0;
						$reqData['chatID']		= $chatID;
						$reqData['message_type']= $product->file_type; 
						$reqData['event'] 		= '';	
						$reqData['message'] 	= $product->name;
						$reqData['reply_id'] 	= '';
						$reqData['mediaUrl'] 	= $product->url;
						
						// $msgRes = $this->sendMsg($reqData);
						$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);						
						$returnArr = ['status' => 'success', 'msg' => 'success'];
						if($msgRes['status'] == 'success'){
							$contFlag = true;
						}
					}					
				}
				else
				{
					if($preID == 's1')
					{							
						if($index == 'n')
						{
							$product = DB::connection('panasonic_connection')
								->table('product_category')
								->select('id', 'name', 'desc')
								->where('id', '>=', $catID)
								->whereIn('status', [0,1])
								->limit(9)
								->get();
							
							if($product)
							{
								$count = $product->count();
								if($count == 0)
								{
									///////////////////////
								}
								else
								{
									$listArr	= ["button" => "Product category", "sections" => []];
									$rows 		= [];
									$id			= 0;
									foreach ($product as $k => $val) {
										$id = $val->id;
										if($k <= 7)
										{
											$rows[] = [
												"id" => $k.'-s1-0-'.$id,
												"title" => $val->name,
												"description" => ($val->desc != null ? $val->desc : '')
											];
										}
									}
									if(!empty($rows) && $count > 8)
									{
										$rows[] = [
											"id" => 'n-s1-0-'.$id,
											"title" => 'Scroll more'
										];
									}
									else{
										$rows[] = [
											"id" => 'mm-s0-0-0',
											"title" => 'Main menu',
										];
										$rows[] = [
											"id" => 'end-s0-0-0',
											"title" => 'End session',
										];
									}
									
									
									$listArr["sections"][] = ["rows" => $rows];
									
									$list = json_encode($listArr, JSON_PRETTY_PRINT);
									$log->debug(__line__."\n\n list --- \n" . $list);
									$log->debug(__line__."\n\n product --- \n" . json_encode($product));
									$reqData['client_id']	= $client;
									$reqData['campaign_id'] = $campaign;
									$reqData['mobile'] 		= $mobile;
									$reqData['agent_id'] 	= 0;
									$reqData['chatID']		= $chatID;
									$reqData['message_type']= 'TEXT'; 
									$reqData['event'] 		= '';
									$reqData['message'] 	= 'Please choose product category.';
									$reqData['reply_id'] 	= '';
									$reqData['mediaUrl'] 	= '';	
									$reqData['templateType']= 'LIST';	
									$reqData['action'] 		= $list;	
									$reqData['interactive_type']= 'list';	
									$reqData['footer'] 		= '';
									$reqData['templateID'] 	= 0;
									// $this->sendTemplate($reqData);
									$msgRes = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);									
									$returnArr = ['status' => 'progress', 'msg' => 'progress'];
								}
							}	
							
						}
						else
						{
							$product = DB::connection('panasonic_connection')
								->table('product')
								->select('id', 'category', 'name', 'desc', 'url', 'file_type')
								->where('category', $catID)
								->whereIn('status', [0,1])
								->limit(9)
								->get();
								
							$log->debug(__line__.'Product List : '.print_r($product, true));
							if($product)
							{
								$count = $product->count();
								if($count == 1)
								{									
									$reqData['client_id']	= $client;
									$reqData['campaign_id'] = $campaign;
									$reqData['mobile'] 		= $mobile;
									$reqData['agent_id'] 	= 0;
									$reqData['chatID']		= $chatID;
									$reqData['message_type']= $product[0]->file_type; 
									$reqData['event'] 		= '';	
									$reqData['message'] 	= $product[0]->name;
									$reqData['reply_id'] 	= '';
									$reqData['mediaUrl'] 	= $product[0]->url;					
									// $msgRes = $this->sendMsg($reqData);
									$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);									
									$returnArr = ['status' => 'success', 'msg' => 'success'];
									if($msgRes['status'] == 'success'){
										$contFlag = true;
									}	
								}
								else
								{
									$listArr	= ["button" => "Product List", "sections" => []];
									$rows 		= [];
									$id			= 0;
									foreach ($product as $k => $val) {
										$id = $val->id;
										if($k <= 7)
										{
											$rows[] = [
												"id" => $k.'-s2-'.$id.'-'.$catID,
												"title" => $val->name,
												"description" => ($val->desc != null ? $val->desc : '')
											];
										}
									}
									if(!empty($rows) && $count > 8)
									{
										$rows[] = [
											"id" => 'n-s2-'.$id.'-'.$catID,
											"title" => 'Scroll more'
										];
									}
									else{
										$rows[] = [
											"id" => 'mm-s0-0-0',
											"title" => 'Main menu',
										];
										$rows[] = [
											"id" => 'end-s0-0-0',
											"title" => 'End session',
										];
									}
									
									$listArr["sections"][] = ["rows" => $rows];
									
									$list = json_encode($listArr, JSON_PRETTY_PRINT);
									$log->debug(__line__."\n\n list --- \n" . $list);
									$log->debug(__line__."\n\n product --- \n" . json_encode($product));
									$reqData['client_id']	= $client;
									$reqData['campaign_id'] = $campaign;
									$reqData['mobile'] 		= $mobile;
									$reqData['agent_id'] 	= 0;
									$reqData['chatID']		= $chatID;
									$reqData['message_type']= 'TEXT'; 
									$reqData['event'] 		= '';
									$reqData['message'] 	= 'Please choose product.';
									$reqData['reply_id'] 	= '';
									$reqData['mediaUrl'] 	= '';	
									$reqData['templateType']= 'LIST';	
									$reqData['action'] 		= $list;	
									$reqData['interactive_type']= 'list';	
									$reqData['footer'] 		= '';
									$reqData['templateID'] 	= 0;
									// $this->sendTemplate($reqData);
									$msgRes = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);	
									$returnArr = ['status' => 'progress', 'msg' => 'progress'];
								}
							}
							else
							{
								
							}
						}
						
					}
					if($preID == 's2')
					{							
						if($index == 'n')
						{
							$product = DB::connection('panasonic_connection')
							->table('product')
							->select('id', 'category', 'name', 'desc')
							// ->where('name', 'like', '%' . $input . '%')
							->where('category', $catID)
							->where('id', '>=', $maxID)
							->whereIn('status', [0,1])
							->limit(9)
							->get();
														
							if (!empty($product)) 
							{
								$listArr 	= ["button" => "Product List", "sections" => []];
								$rows 		= [];
								//$maxID = 
								$totalCnt 	= $product->count();
								$id 		= 0;
								foreach ($product as $k => $val) {
									$id = $val->id;
									if($k <= 7)
									{
										$rows[] = [
											"id" => $k.'-s2-'.$id.'-'.$catID,
											"title" => $val->name,
											"description" => ($val->desc != null ? $val->desc : '')
										];
									}
								}
								if(!empty($rows) && $totalCnt > 8)
								{
									$rows[] = [
										"id" => 'n-s2-'.$id.'-'.$catID,
										"title" => 'Scroll more'
									];
								}								
								else{
									$rows[] = [
										"id" => 'mm-s0-0-0',
										"title" => 'Main menu',
									];
									$rows[] = [
										"id" => 'end-s0-0-0',
										"title" => 'End session',
									];
								}
									
								$listArr["sections"][] = ["rows" => $rows];
								
								$list = json_encode($listArr, JSON_PRETTY_PRINT);
								$log->debug(__line__."\n\n list --- \n" . $list);
								$log->debug(__line__."\n\n product --- \n" . json_encode($product));
								$reqData['client_id']	= $client;
								$reqData['campaign_id'] = $campaign;
								$reqData['mobile'] 		= $mobile;
								$reqData['agent_id'] 	= 0;
								$reqData['chatID']		= $chatID;
								$reqData['message_type']= 'TEXT'; 
								$reqData['event'] 		= '';
								$reqData['message'] 	= 'Please choose product.';
								$reqData['reply_id'] 	= '';
								$reqData['mediaUrl'] 	= '';	
								$reqData['templateType']= 'LIST';	
								$reqData['action'] 		= $list;	
								$reqData['interactive_type']= 'list';	
								$reqData['footer'] 		= '';
								$reqData['templateID'] 	= 0;
								// $this->sendTemplate($reqData);
								$msgRes = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);	
								$returnArr = ['status' => 'progress', 'msg' => 'progress'];
							}
						}
						else
						{
							$product = DB::connection('panasonic_connection')
							->table('product')
							->select('name', 'desc', 'url', 'file_type')
							->where('name', 'like', '%' . $input . '%')
							->whereIn('status', [0,1])
							->first();							
							$reqData['client_id']	= $client;
							$reqData['campaign_id'] = $campaign;
							$reqData['mobile'] 		= $mobile;
							$reqData['agent_id'] 	= 0;
							$reqData['chatID']		= $chatID;
							$reqData['message_type']= $product->file_type; 
							$reqData['event'] 		= '';	
							$reqData['message'] 	= $product->name;
							$reqData['reply_id'] 	= '';
							$reqData['mediaUrl'] 	= $product->url;					
							// $msgRes = $this->sendMsg($reqData);
							$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);							
							$returnArr = ['status' => 'success', 'msg' => 'success'];
							if($msgRes['status'] == 'success'){
								$contFlag = true;
							}
						}
					}
				}
			}
		}
		
		if($contFlag === true)
		{			
			sleep(7);
			$options =['mm'=>'Main menu', 'end'=>'End session'];
			$textMsg = 'Please choose below option for proceed further.';
			$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $textMsg, 'TEXT', $options);
		}
		return $returnArr;		
	}
	
	public function locateRetailer($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = '')
	{
		$log 		= Log::channel('wp_webhooks');
		$log->debug(__line__."\n\n LocateRetailer ------------ \n");
		$returnArr 	= ['status' => '', 'msg' => ''];		
		$textMsg 	= $plainText = '';
		$knowMoreFlag = false;
		$options	= [];
		
		if(empty($input))
		{			
			$plainText = 'Enter area pincode.';
			$returnArr = ['status' => 'initiate', 'msg' => 'Initiated'];
		}
		else{
			
			$knowMoreFlag = true;
			if(!$this->validatePIN($input) && empty($optionID))
			{						
				$log->debug(__line__."\nValid Input". $input);
				$textMsg 	= 'Enter valid pincode, or choose below option';
				$options	= ['mm'=>'Main menu','end'=>'End session']; //'ttx'=>'Talk to our experts', 
			}
			else
			{
				$maxID = 0;
				if(!empty($optionID))
				{
					$exploxdID 	= explode('-',$optionID);
					$index 		= (isset($exploxdID[0]) && $exploxdID[0] != 'n') ? $exploxdID[0] : 'n';
					$preID 		= isset($exploxdID[1]) ? $exploxdID[1] : '';
					$input 		= isset($exploxdID[2]) ? $exploxdID[2] : 0;
					$maxID 		= isset($exploxdID[3]) ? $exploxdID[3] : 0;
					$menuKey 	= isset($exploxdID[4]) ? $exploxdID[4] : '';
				}
				$log->debug(__line__."\n step2\n");		
				$offset 		= 0;
				$retailer = DB::connection('panasonic_connection')
					->table('retailer')
					->select('id', 'name', 'number', 'address', 'district', 'city', 'state', 'pincode')
					->where('pincode', $input)
					->whereIn('status', [0, 1])
					->where('id', '>=', $maxID)
					->limit(8)
					->get();
					
				$count = $retailer->count();
				
				if($count > 0){				
					$textMsg 	= '';
					$returnArr 	= ['status' => 'success', 'msg' => 'success'];
					$options	= ['mm'=>'Main menu', 'end'=>'End session'];
					$log->debug(__line__."\n step3\n". $retailer);
					foreach($retailer as $k => $values)
					{
						$maxID = $values->id;
						if($k <= 6){
							$log->debug(__line__."\n\n values ------------ \n". json_encode($values));
							$textMsg .= "\n\n\nSr No  : ".++$k. " \nName : ".$values->name. " \nNumber:".$values->number. " \nAddress:".$values->address. " \nDistrict:".$values->district. " \nPlace:".$values->city;
						}
					}
					if(!empty($textMsg) && $count > 7)
					{
						$options	= ['n-s1-'.$input.'-'.$maxID =>'Scroll more', 'mm'=>'Main menu', 'end'=>'End session'];
					}
					$log->debug(__line__."\n step4\n");
					if(empty($textMsg))
					{
						$log->debug(__line__."\n step5\n");
						$textMsg = "We are not serve at this location, Please enter other pincode, or choose below option.";
						$returnArr = ['status' => 'failed', 'msg' => 'We are not serve at this location, Please enter other pincode'];	
						$options =['mm'=>'Main menu', 'end'=>'End session'];//'ttx'=>'Talk to our experts', 
					}
				}
				else{
					$log->debug(__line__."\n step6\n");
					$textMsg = "We are not serve at this location, Please enter other pincode, or choose below option.";
					$returnArr = ['status' => 'failed', 'msg' => 'We are not serve at this location, Please enter other pincode'];	
					$options =['mm'=>'Main menu', 'end'=>'End session'];					
				}
			}
		}

		if(!empty($plainText))
		{
			$reqData['client_id']	= $client;
			$reqData['campaign_id'] = $campaign;
			$reqData['mobile'] 		= $mobile;
			$reqData['agent_id'] 	= 0;
			$reqData['chatID']		= $chatID;
			$reqData['message_type']= 'text'; 
			$reqData['event'] 		= '';	
			$reqData['message'] 	= $plainText;
			$reqData['reply_id'] 	= '';
			$reqData['mediaUrl'] 	= '';					
			// $this->sendMsg($reqData);
			$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);			
		}
		if($knowMoreFlag){	
			//$options =['mm'=>'Main menu', 'end'=>'End session'];
			$log->debug(__line__."\n\n diyReplyButton ------------ \n". $textMsg);		
			$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $textMsg, 'TEXT', $options);
		}
		return $returnArr;
	}
	
	public function locateElectrician($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = '')
	{
		$log 		= Log::channel('wp_webhooks');
		$log->debug(__line__."\n\n locateElectrician ------------ \n");
		$returnArr 		= ['status' => '', 'msg' => ''];
		$textMsg 		= $plainText = '';
		$knowMoreFlag 	= false;
		$options		= [];
		$log->debug(__line__."\n Step0". $input);	
		if(empty($input))
		{		
			$log->debug(__line__."\n Step1-1");	
			$plainText = 'Enter area pincode.';
			$returnArr = ['status' => 'initiate', 'msg' => 'Initiated'];
		}
		else
		{
			$log->debug(__line__."\n Step1");
			$knowMoreFlag 	= true;
			if(!$this->validatePIN($input) && empty($optionID))
			{
				$textMsg 		= 'Enter valid pincode.';
				$options 		= ['mm'=>'Main menu', 'end'=>'End session']; //'ttx'=>'Talk to our experts'
			}
			else
			{
				$log->debug(__line__."\n Step2");
				$maxID = 0;
				if(!empty($optionID))
				{
					$exploxdID 	= explode('-',$optionID);
					$index 		= (isset($exploxdID[0]) && $exploxdID[0] != 'n') ? $exploxdID[0] : 'n';
					$preID 		= isset($exploxdID[1]) ? $exploxdID[1] : '';
					$input 		= isset($exploxdID[2]) ? $exploxdID[2] : 0;
					$maxID 		= isset($exploxdID[3]) ? $exploxdID[3] : 0;
					$menuKey	= isset($exploxdID[4]) ? $exploxdID[4] : '';
				}
				
				$query = DB::connection('panasonic_connection')
					->table('electician')
					->select('id', 'name', 'number', 'address', 'district', 'city', 'state', 'pincode')
					->where('pincode', $input)
					->whereIn('status', [0,1])
					->where('id', '>=', $maxID)
					->limit(8)
					->get();
				$count = $query->count();
				$log->debug(__line__."\n Step3");
				if($count > 0){	
				$log->debug(__line__."\n Step4");
					$textMsg = '';
					$returnArr = ['status' => 'success', 'msg' => 'success'];
					$options = ['mm'=>'Main menu', 'end'=>'End session']; // 'ttx'=>'Talk to our experts',
					
					// $plainText	= '';
					foreach($query as $k => $values)
					{
						$maxID = $values->id;
						if($k <= 6){
							$textMsg .= "\n\n\nSr No  : ".++$k. " \nName : ".$values->name. " \nNumber:".$values->number. " \nAddress:".$values->address. " \nDistrict:".$values->district. " \nPlace:".$values->city;
						}
					}
					$log->debug(__line__."\n Step5");
					if(!empty($textMsg) && $count > 7)
					{
						$options	= ['n-s1-'.$input.'-'.$maxID =>'Scroll more', 'mm'=>'Main menu', 'end'=>'End session'];
					}
					
					if(empty($textMsg))
					{
						$textMsg = "We are not serve at this location, Please enter other pincode, or choose below option.";
						$returnArr = ['status' => 'failed', 'msg' => 'We are not serve at this location, Please enter other pincode'];	
						$options =['mm'=>'Main menu', 'end'=>'End session']; // 'ttx'=>'Talk to our experts',		
					}
					$log->debug(__line__."\n Step6");
				} else {
					$textMsg = 'No Record Found, please enter valid pincode, or choose below option';
					$returnArr = ['status' => 'failed', 'msg' => 'No Record Found, please enter valid pincode'];
					$options = ['mm'=>'Main menu', 'end'=>'End session']; //'ttx'=>'Talk to our experts',
				}
				
			}
		}		
		if(!empty($plainText))
		{
			$log->debug(__line__."\n Step7-1");
			$reqData['client_id']	= $client;
			$reqData['campaign_id'] = $campaign;
			$reqData['mobile'] 		= $mobile;
			$reqData['agent_id'] 	= 0;
			$reqData['chatID']		= $chatID;
			$reqData['message_type']= 'text'; 
			$reqData['event'] 		= '';	
			$reqData['message'] 	= $plainText;
			$reqData['reply_id'] 	= '';
			$reqData['mediaUrl'] 	= '';					
			// $this->sendMsg($reqData);			
			$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
		}
		$log->debug(__line__."\n Step7");
		if($knowMoreFlag){
		$log->debug(__line__."\n Step8");			
			$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $textMsg, 'TEXT', $options);
		}		
		return $returnArr;
	}
	
	public function assignToExpert($crudDetails, $client, $campaign, $mobile, $chatID, $isOption = false, $name = '')
	{
		
			
		if($this->isWorkingHours($campaign,$mobile, $chatID))
		{
			//$this->getConsent($mobile, 'OPT_IN', $name); // take consent
			ApiTraits::getConsent($client, $campaign, $mobile, $logSource = 'wp_webhooks', 'OPT_IN', $crudDetails, $name = '');
			
			Chats::where('id', $chatID)
			->where('client_id', $client)
			->where('campaign_id', $campaign)
			->where('is_closed', 1)
			->where('cust_unique_id', $mobile)
			->update(['assigning_flag' => 2]);					
		
			$reqData['client_id']	= $client;
			$reqData['campaign_id'] = $campaign;
			$reqData['mobile'] 		= $mobile;
			$reqData['agent_id'] 	= 0;
			$reqData['chatID']		= $chatID;
			$reqData['message_type']= 'text'; 
			$reqData['event'] 		= '';
			$reqData['message'] 	= 'Please wait while we connect to our next available executive';
			$reqData['reply_id'] 	= '';
			$reqData['mediaUrl'] 	= '';					
			// $autRepMsg = $this->sendMsg($reqData);			
			$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
			$returnArr = ['status' => 'success', 'msg' => 'success'];
		}
		else{
			$returnArr = ['status' => 'failed', 'msg' => 'failed'];
		}
		return $returnArr;
	}
	
	public function updateSetting($client, $campaign, $chatID, $updateArr = array())
	{
		$log 		= Log::channel('wp_webhooks');
		$log->debug(__line__."\n updateSetting ------------ \n");
		
		if(!empty($updateArr) && !empty($chatID))
		{
			$chatIDs = DB::table('chats')
                ->select('org_no', 'cust_unique_id', 'client_id', 'campaign_id')
                ->whereIn('status', [0,1])
                ->where('id', $chatID)
                ->where('is_closed', 1)
                ->where('assigning_flag', 1)
                ->first();
			$log->debug(__line__."chatIDs  : ". $chatID);
			if($chatIDs)
			{
				$conditions = ['chat_id' => $chatID, 'client' => $client, 'campaign' => $campaign];
				$status = DB::connection('panasonic_connection')
					->table('setting')
					->updateOrInsert($conditions, $updateArr);
					
				$log->debug(__line__."conditions  : ". print_r($conditions, true));
				$log->debug(__line__."updateArr  : ". print_r($updateArr, true));
				$log->debug(__line__."status  : ". $status);
			}	
			
		}
	}
	
	
	public function getSetting($client, $campaign, $chatID)
	{
		$setting = DB::connection('panasonic_connection')
			->table('setting')
			->select('ret_last_index', 'ele_last_index', 'lsto', 'prod_ofset')
			->where('client', $client)
			->where('campaign', $campaign)
			->where('chat_id', $chatID)
			->whereIn('status', [0, 1])
			->first();
		return (array) $setting;
	}
	// to call an api to send the msg call below function 
	public function sendNewMessage(Request $request)
	{
		$log = Log::channel('wp_send');
		$log->debug(__line__."\n\n--------------------Start sendNewMessage------------------------------------\n");
		
		$data = $request->all();
		$reqData['client_id']	= $request->input('client_id');
		$reqData['campaign_id'] = $request->input('campaign_id');
		$reqData['mobile'] 		= $request->input('mobile');
		$reqData['agent_id'] 	= $request->input('agent_id');
		$reqData['chatID']		= $request->input('chatID');
		$reqData['message_type']= $request->input('message_type'); 
		$reqData['event'] 		= $request->input('event');
		$reqData['message'] 	= $request->input('message');
		$reqData['reply_id'] 	= $request->input('reply_id');
		$reqData['mediaUrl'] 	= $request->input('mediaUrl');
		$waNumber 				= $request->input('wp_number');
		$crudDetails = $this->getcrud($waNumber);
		$log->debug(__line__.'Input reqData : ' . print_r($reqData, true));
		// $result = $this->sendMsg($reqData);			
		$result = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
		$log->debug(__line__.'result : ' . print_r($result, true));
		return json_encode($result);
	}
	
	// public function updatestatus($messageid, $eventTs, $eventType, $destAddr, $cause)
	public function updateDLR(Request $request)
	{
		$log = Log::channel('wp_dlr');
		$log->debug(__line__."\n\n--------------------Start Here for DLR------------------------------------\n");		
		$data = $request->all();
		$log->debug(__line__.'Received WhatsApp DLR Data : ' . $request);
		
		$response = (is_array($data['response'])) ? $data['response'] : json_decode($data['response'], true);
		
		if(!empty($response))
		{
			foreach($response as $k => $data)
			{				
				$conArr = [];
				if(strtoupper($data['cause']) == 'SENT' && strtoupper($data['eventType']) == 'SENT')
				{
					$conArr['is_sent'] = 2;
				}
				
				if(strtoupper($data['cause']) == 'SUCCESS' && strtoupper($data['eventType']) == 'DELIVERED')
				{
					$conArr['is_delivered'] = 2;
				}
				
				if(strtoupper($data['cause']) == 'READ' && strtoupper($data['eventType']) == 'READ')
				{
					$conArr['is_read'] = 2;
				}				
				
				if(!empty($conArr))
				{
					$log->debug(__line__.'Status for update : ' . json_encode($conArr));
					
					$externalID = explode('-', $data['externalId']);
					$messageID = $externalID[1];
					
					$query = DB::table('chat_log')
					->where('message_id', $messageID)
					->update($conArr);
					
					// $log->debug(__line__.'Update DLR Status : ' . $query);
				}
				else{
					$log->debug(__line__.'Empty Data for update');
				}
			}
		}
		else{
			$log->debug(__line__.'Empty Response');
		}
		
		$log->debug(__line__."\n\n--------------------End DLR------------------------------------\n");		
	}
/* 
	public function getConsent($phone, $type = 'OPT_IN', $name = ''){
		
		$log = Log::channel('wp_send');
		$log->debug(__line__."\n\n--------------------Start getOptIN Here------------------------------------\n");
		
		$returnArr = ['status'=> 'failed', 'status_code' => 400];
		$payload = array(
			"method" => $type,
			"format" => "json",
			"userid" => $this->hsm_userid, //2000200387'
			"password" => $this->hsm_password, //'tuC3vd#z',
			"auth_scheme" => $this->auth_scheme,//"plain",
			"v" => $this->version,//"1.1",
			"phone_number" => $phone,
			"channel" => "WHATSAPP"
		);
		
		$res 		= ApiTraits::curlHit($this->url, $payload, 'GET');
		$response 	= json_decode($res['response'], true);
		$log->debug(__line__.'URL : ' . $this->url);
		$log->debug(__line__.'Payload : ' . json_encode($payload));
		$log->debug(__line__.'Gupshup API response : ' . json_encode($response));
		
		$response_messages = $response['response']['status'];
		$optin = 1;
		$returnArr = array('status'=> $response['response']['status'], 'status_code' => 101, 'msg'=>$response['response']['details']);
		if($response['response']['status'] == 'success' && strtoupper($type) == 'OPT_IN')
		{
			$optin = 2;
			$returnArr = array('status'=> 'success', 'status_code' => 200);
		}
		
		
		$data = [
			'name' => $name,
			'mobile' => $phone,
			'is_optin' => $optin,
			'status' => 1,
		];

		$conditions = [
			'mobile' => $phone,
		];

		// Perform the update or insert
		DB::table('customer_master')->updateOrInsert($conditions, $data);
		
		$log->debug(__line__."\n\n--------------------End  getOptIN Here------------------------------------\n");
		
		return $returnArr;
	}

	 */
	public function getBulkData()
	{
		$custom_log = Log::channel('wp_send');
		$custom_log->debug(__line__."\n\n--------------------Start Here------------------------------------\n");
			
		 $Data = DB::table('bulkfile_log')
            ->leftJoin('clients', 'bulkfile_log.client_id', '=', 'clients.id')
            ->leftJoin('campaign', 'bulkfile_log.campaign_id', '=', 'campaign.id')
            ->select('bulkfile_log.id', 'clients.id as client_id', 'clients.name as clientname', 'bulkfile_log.campaign_id', 'bulkfile_log.filename', 'bulkfile_log.templete_id', 'campaign.wp_number')
            ->whereIn('bulkfile_log.status', [0,1])
            ->whereIn('clients.status', [0,1])
            ->where('bulkfile_log.is_processed', 1)
            // ->where('bulkfile_log.created_at', '>=', $startDate)
            ->orderBy('bulkfile_log.id', 'asc')
            ->first();
            // ->get();
			
		if(!empty($Data))
        {
            $waNumber     	= $Data->wp_number;
            $clientname     = $Data->clientname;
            $bulk_id        = $Data->id;
            $client_id      = $Data->client_id;
            $campaign_id    = $Data->campaign_id;
            $filename       = $Data->filename;
            $templete_id    = $Data->templete_id;
            $name    		= '';
			
			
			 $templateData = DB::table('templates')
                ->select('name', 'header', 'caption', 'media_url', 'footer', 'list', 'msg_type', 'intractive_type', 'is_hsm')
                ->whereIn('status', [0,1])
                ->where('id', $templete_id)
                ->first();
			
            $mobileData = DB::table('file_chunk_data')
                ->select('mobile', 'status')
                ->whereIn('status', [0,1])
                ->where('bulk_id', $bulk_id)
                ->orderBy('id', 'asc')
                ->take(25)
                ->get();
                //echo $id; dd($vehicleNumbers->toSql());
            $mobileArr = $mobileData->pluck('mobile')->toArray();
			// echo "<pre> Data : "; print_r($Data);//die;
			// echo "<pre> templateData : "; print_r($templateData);//die;
			
			foreach($mobileArr as $k => $mobile)
			{				
				$chat_data = DB::table('chats')
                ->select('chats.id')
                ->whereIn('chats.status', [0,1])
                ->where('chats.org_no', $waNumber)
                ->where('chats.cust_unique_id', $mobile) 
				->whereIn('chats.is_closed', [0,1])
                ->get()
                ->first();			
				$custom_log->debug(__line__.'Qeury chats : ' . json_encode($chat_data));
				if($chat_data)
				{
					$newChat = false;
					$chatID = $chat_data->id;
				}
				else{				
					$newChat 	= true;
					$chat_data = new Chats();
					$chat_data->unique_id = CommonTraits::uuid();
					$chat_data->client_id = $client_id; 
					$chat_data->campaign_id = $campaign_id;
					$chat_data->org_no = $waNumber;
					$chat_data->cust_unique_id = $mobile;
					$chat_data->customer_name = $name;
					$chat_data->assigning_flag = 1;
					$chat_data->is_closed = 1;
					$chat_data->status = 1;
					$chat_data->created_at = now();
					$chat_data->created_by = 0;
					$chat_data->save();
					// Get the inserted ID
					$chatID = $chat_data->id;
				}
				
				$postData = array();
				$postData['mobile'] = $mobile;
				$postData['client_id'] = $client_id;
				$postData['campaign_id'] = $campaign_id;
				$postData['agent_id'] = 0;
				$postData['chatID'] = $chatID;
				$postData['message_type'] = 'text';
				$postData['event'] = '';
				$postData['header'] = $templateData->header;
				$postData['message'] = $templateData->caption;
				$postData['reply_id'] = '';
				$postData['mediaUrl'] = $templateData->media_url;
				$postData['templateType'] = $templateData->msg_type;
				$postData['action'] = $templateData->list;
				$postData['interactive_type'] = $templateData->intractive_type;
				$postData['footer'] = $templateData->footer;
				$postData['is_hsm'] = $templateData->is_hsm;
				$postData['templateID'] = $templete_id;
				// echo "<pre> postData : "; print_r($postData);//die;
								
				// $result = $this->sendTemplate($postData);
				$result = $this->sendWPMsg($postData, true,'wp_webhooks', []);
				// echo "<pre> result : "; print_r($result);die;
				if(isset($result['status_code']) && $result['status_code'] == 200)
				{
					// update status for success
					$custom_log->debug(__LINE__." ---- success ");
				}
				else{
					 // update status for failed
					 $custom_log->debug(__LINE__." ---- failed ");
				}
			}
                 
            // $custom_log->debug(__LINE__." ---- Picked up non processed data  : ". json_encode($vehicleArr));
		}
		$custom_log->debug(__LINE__." ---- End ");
	}
	
	
	public function isChatExist($id)
	{		
		$custom_log = Log::channel('wp_send');
		$custom_log->debug(__line__."\n\n--------------------Start isChatExist------------------------------------\n");
		$data = DB::table('chats')
			->select('chats.id')
			->whereIn('chats.status', [0,1])
			->where('chats.id', $id)
			->whereIn('chats.is_closed', [0,1])
			->first();
			$custom_log->debug(__line__."\n client Data : ". json_encode($data));
		if($data)
		{
			return true;
		}
		else{
			return false;
		}
	}
	
	public function isClientExist($id)
	{		$custom_log = Log::channel('wp_send');
		$custom_log->debug(__line__."\n\n--------------------Start isClientExist------------------------------------\n");
		$data = DB::table('clients')
			->select('id')
			->whereIn('status', [0,1])
			->where('id', $id)
			->first();
			$custom_log->debug(__line__."\n client Data : ". json_encode($data));
		if($data)
		{
			return true;			
		}
		else{
			return false;
		}
	}
	
	public function isCampaingExist($id)
	{		
		$custom_log = Log::channel('wp_send');
		$custom_log->debug(__line__."\n\n--------------------Start isChatExist------------------------------------\n");
		$data = DB::table('campaign')
			->select('id')
			->whereIn('status', [0,1])
			->where('id', $id)
			->first();
			$custom_log->debug(__line__."\n client Data : ". json_encode($data));
		if($data)
		{
			return true;
		}
		else{
			return false;
		}
	}
	
	public function triggerEventMsg($triggerText, $msg)
	{
		echo "Event";
		switch (strtolower($triggerText)) {
			case 'end':
				// Code to be executed if $variable is 'value1'
				break;
			case 'start':
				// Code to be executed if $variable is 'value2'
				break;
			case 'exit':
				// Code to be executed if $variable is 'value3'
				break;
			// Add more cases as needed
			default:
				// Code to be executed if $variable doesn't match any case
		}
		
	}
	
	public function mime2ext($mime)
	{
		$mime_map = [
			'video/3gpp2'                                                               => '3g2',
			'video/3gp'                                                                 => '3gp',
			'video/3gpp'                                                                => '3gp',
			'application/x-compressed'                                                  => '7zip',
			'audio/x-acc'                                                               => 'aac',
			'audio/ac3'                                                                 => 'ac3',
			'application/postscript'                                                    => 'ai',
			'audio/x-aiff'                                                              => 'aif',
			'audio/aiff'                                                                => 'aif',
			'audio/x-au'                                                                => 'au',
			'video/x-msvideo'                                                           => 'avi',
			'video/msvideo'                                                             => 'avi',
			'video/avi'                                                                 => 'avi',
			'application/x-troff-msvideo'                                               => 'avi',
			'application/macbinary'                                                     => 'bin',
			'application/mac-binary'                                                    => 'bin',
			'application/x-binary'                                                      => 'bin',
			'application/x-macbinary'                                                   => 'bin',
			'image/bmp'                                                                 => 'bmp',
			'image/x-bmp'                                                               => 'bmp',
			'image/x-bitmap'                                                            => 'bmp',
			'image/x-xbitmap'                                                           => 'bmp',
			'image/x-win-bitmap'                                                        => 'bmp',
			'image/x-windows-bmp'                                                       => 'bmp',
			'image/ms-bmp'                                                              => 'bmp',
			'image/x-ms-bmp'                                                            => 'bmp',
			'application/bmp'                                                           => 'bmp',
			'application/x-bmp'                                                         => 'bmp',
			'application/x-win-bitmap'                                                  => 'bmp',
			'application/cdr'                                                           => 'cdr',
			'application/coreldraw'                                                     => 'cdr',
			'application/x-cdr'                                                         => 'cdr',
			'application/x-coreldraw'                                                   => 'cdr',
			'image/cdr'                                                                 => 'cdr',
			'image/x-cdr'                                                               => 'cdr',
			'zz-application/zz-winassoc-cdr'                                            => 'cdr',
			'application/mac-compactpro'                                                => 'cpt',
			'application/pkix-crl'                                                      => 'crl',
			'application/pkcs-crl'                                                      => 'crl',
			'application/x-x509-ca-cert'                                                => 'crt',
			'application/pkix-cert'                                                     => 'crt',
			'text/css'                                                                  => 'css',
			'text/x-comma-separated-values'                                             => 'csv',
			'text/comma-separated-values'                                               => 'csv',
			'application/vnd.msexcel'                                                   => 'csv',
			'application/x-director'                                                    => 'dcr',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
			'application/x-dvi'                                                         => 'dvi',
			'message/rfc822'                                                            => 'eml',
			'application/x-msdownload'                                                  => 'exe',
			'video/x-f4v'                                                               => 'f4v',
			'audio/x-flac'                                                              => 'flac',
			'video/x-flv'                                                               => 'flv',
			'image/gif'                                                                 => 'gif',
			'application/gpg-keys'                                                      => 'gpg',
			'application/x-gtar'                                                        => 'gtar',
			'application/x-gzip'                                                        => 'gzip',
			'application/mac-binhex40'                                                  => 'hqx',
			'application/mac-binhex'                                                    => 'hqx',
			'application/x-binhex40'                                                    => 'hqx',
			'application/x-mac-binhex40'                                                => 'hqx',
			'text/html'                                                                 => 'html',
			'image/x-icon'                                                              => 'ico',
			'image/x-ico'                                                               => 'ico',
			'image/vnd.microsoft.icon'                                                  => 'ico',
			'text/calendar'                                                             => 'ics',
			'application/java-archive'                                                  => 'jar',
			'application/x-java-application'                                            => 'jar',
			'application/x-jar'                                                         => 'jar',
			'image/jp2'                                                                 => 'jp2',
			'video/mj2'                                                                 => 'jp2',
			'image/jpx'                                                                 => 'jp2',
			'image/jpm'                                                                 => 'jp2',
			'image/jpeg'                                                                => 'jpeg',
			'image/pjpeg'                                                               => 'jpeg',
			'application/x-javascript'                                                  => 'js',
			'application/json'                                                          => 'json',
			'text/json'                                                                 => 'json',
			'application/vnd.google-earth.kml+xml'                                      => 'kml',
			'application/vnd.google-earth.kmz'                                          => 'kmz',
			'text/x-log'                                                                => 'log',
			'audio/x-m4a'                                                               => 'm4a',
			'audio/mp4'                                                                 => 'm4a',
			'application/vnd.mpegurl'                                                   => 'm4u',
			'audio/midi'                                                                => 'mid',
			'application/vnd.mif'                                                       => 'mif',
			'video/quicktime'                                                           => 'mov',
			'video/x-sgi-movie'                                                         => 'movie',
			'audio/mpeg'                                                                => 'mp3',
			'audio/mpg'                                                                 => 'mp3',
			'audio/mpeg3'                                                               => 'mp3',
			'audio/mp3'                                                                 => 'mp3',
			'video/mp4'                                                                 => 'mp4',
			'video/mpeg'                                                                => 'mpeg',
			'application/oda'                                                           => 'oda',
			'audio/ogg'                                                                 => 'ogg',
			'audio/ogg; codecs=opus'                                                    => 'ogg',
			'video/ogg'                                                                 => 'ogg',
			'application/ogg'                                                           => 'ogg',
			'font/otf'                                                                  => 'otf',
			'application/x-pkcs10'                                                      => 'p10',
			'application/pkcs10'                                                        => 'p10',
			'application/x-pkcs12'                                                      => 'p12',
			'application/x-pkcs7-signature'                                             => 'p7a',
			'application/pkcs7-mime'                                                    => 'p7c',
			'application/x-pkcs7-mime'                                                  => 'p7c',
			'application/x-pkcs7-certreqresp'                                           => 'p7r',
			'application/pkcs7-signature'                                               => 'p7s',
			'application/pdf'                                                           => 'pdf',
			'application/octet-stream'                                                  => 'pdf',
			'application/x-x509-user-cert'                                              => 'pem',
			'application/x-pem-file'                                                    => 'pem',
			'application/pgp'                                                           => 'pgp',
			'application/x-httpd-php'                                                   => 'php',
			'application/php'                                                           => 'php',
			'application/x-php'                                                         => 'php',
			'text/php'                                                                  => 'php',
			'text/x-php'                                                                => 'php',
			'application/x-httpd-php-source'                                            => 'php',
			'image/png'                                                                 => 'png',
			'image/x-png'                                                               => 'png',
			'application/powerpoint'                                                    => 'ppt',
			'application/vnd.ms-powerpoint'                                             => 'ppt',
			'application/vnd.ms-office'                                                 => 'ppt',
			'application/msword'                                                        => 'doc',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
			'application/x-photoshop'                                                   => 'psd',
			'image/vnd.adobe.photoshop'                                                 => 'psd',
			'audio/x-realaudio'                                                         => 'ra',
			'audio/x-pn-realaudio'                                                      => 'ram',
			'application/x-rar'                                                         => 'rar',
			'application/rar'                                                           => 'rar',
			'application/x-rar-compressed'                                              => 'rar',
			'audio/x-pn-realaudio-plugin'                                               => 'rpm',
			'application/x-pkcs7'                                                       => 'rsa',
			'text/rtf'                                                                  => 'rtf',
			'text/richtext'                                                             => 'rtx',
			'video/vnd.rn-realvideo'                                                    => 'rv',
			'application/x-stuffit'                                                     => 'sit',
			'application/smil'                                                          => 'smil',
			'text/srt'                                                                  => 'srt',
			'image/svg+xml'                                                             => 'svg',
			'application/x-shockwave-flash'                                             => 'swf',
			'application/x-tar'                                                         => 'tar',
			'application/x-gzip-compressed'                                             => 'tgz',
			'image/tiff'                                                                => 'tiff',
			'font/ttf'                                                                  => 'ttf',
			'text/plain'                                                                => 'txt',
			'text/x-vcard'                                                              => 'vcf',
			'application/videolan'                                                      => 'vlc',
			'text/vtt'                                                                  => 'vtt',
			'audio/x-wav'                                                               => 'wav',
			'audio/wave'                                                                => 'wav',
			'audio/wav'                                                                 => 'wav',
			'application/wbxml'                                                         => 'wbxml',
			'video/webm'                                                                => 'webm',
			'image/webp'                                                                => 'webp',
			'audio/x-ms-wma'                                                            => 'wma',
			'application/wmlc'                                                          => 'wmlc',
			'video/x-ms-wmv'                                                            => 'wmv',
			'video/x-ms-asf'                                                            => 'wmv',
			'font/woff'                                                                 => 'woff',
			'font/woff2'                                                                => 'woff2',
			'application/xhtml+xml'                                                     => 'xhtml',
			'application/excel'                                                         => 'xl',
			'application/msexcel'                                                       => 'xls',
			'application/x-msexcel'                                                     => 'xls',
			'application/x-ms-excel'                                                    => 'xls',
			'application/x-excel'                                                       => 'xls',
			'application/x-dos_ms_excel'                                                => 'xls',
			'application/xls'                                                           => 'xls',
			'application/x-xls'                                                         => 'xls',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
			'application/vnd.ms-excel'                                                  => 'xlsx',
			'application/xml'                                                           => 'xml',
			'text/xml'                                                                  => 'xml',
			'text/xsl'                                                                  => 'xsl',
			'application/xspf+xml'                                                      => 'xspf',
			'application/x-compress'                                                    => 'z',
			'application/x-zip'                                                         => 'zip',
			'application/zip'                                                           => 'zip',
			'application/x-zip-compressed'                                              => 'zip',
			'application/s-compressed'                                                  => 'zip',
			'multipart/x-zip'                                                           => 'zip',
			'text/x-scriptzsh'                                                          => 'zsh',
		];

		return isset($mime_map[$mime]) ? $mime_map[$mime] : false;
	}
	
	function validatePIN($pincode) {
		// Define a regular expression for a six-digit PIN code
		$pinPattern = '/^[1-9][0-9]{5}$/';

		// Use preg_match to check if the PIN matches the pattern
		if (preg_match($pinPattern, $pincode)) {
			return true; // PIN is valid
		} else {
			return false; // PIN is not valid
		}
	}


	public function closedChat($client, $campaign,  $chatID,  $mobile)
	{
		$result = Chats::where('id', $chatID)
		->update(['dispo' => 'Dispo_by_system', 'is_closed' => 2, 'closed_by' => 0, 'closed_at' => now()]);
		
		return $result ? true : false;
	}
	
	public function diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $textMsg, $msgType = 'TEXT', $buttonData, $mediaUrl = '')
	{
		$log 		= Log::channel('wp_webhooks');
		$log->debug(__line__."\n\n diyReplyButton ------------ \n");
		
		$listArr = ["buttons" => []];
		if (!empty($buttonData)) {
			foreach ($buttonData as $k => $button) {
				$key = $k.'-s0-0-0';
				if (strpos($k, '-') !== false) {
					$key = $k;
				}
				$listArr["buttons"][] = [
					"type" => "reply",
					"reply" => [
						"id" => $key,
						"title" => $button
					]
				];
			}
		}
		$list = json_encode($listArr, JSON_PRETTY_PRINT);	
		
		if(empty($textMsg))
		{
			$textMsg = 'Choose one of the option below.';
		}
		if(strtoupper($msgType) != 'TEXT' && empty($mediaUrl))
		{
			$log->debug(__line__."\n mediaUrl shoule not be empty if msg type is not text");	
			exit;
		}
		
		$reqData['client_id']	= $client;
		$reqData['campaign_id'] = $campaign;
		$reqData['mobile'] 		= $mobile;
		$reqData['agent_id'] 	= 0;
		$reqData['chatID']		= $chatID;
		$reqData['message_type']= $msgType; 
		$reqData['event'] 		= '';
		$reqData['message'] 	= $textMsg;
		$reqData['reply_id'] 	= '';
		$reqData['mediaUrl'] 	= $mediaUrl;	
		$reqData['templateType']= 'REPLY_BUTTON';	
		$reqData['action'] 		= $list;	
		$reqData['interactive_type']= 'dr_button';	
		$reqData['footer'] 		= '';
		$reqData['templateID'] 	= 0;
		// return $result = $this->sendTemplate($reqData);
		return $result = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);
		// $log->debug(__line__."\n\n diyReplyButton ------------ \n".json_encode($result));		
	}

	public function helpLineNo($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = ''){
		
		$reqData['client_id']	= $client;
		$reqData['campaign_id'] = $campaign;
		$reqData['mobile'] 		= $mobile;
		$reqData['agent_id'] 	= 0;
		$reqData['chatID']		= $chatID;
		$reqData['message_type']= 'text'; 
		$reqData['event'] 		= '';
		$reqData['message'] 	= 'Helpline No. +9136088606';
		$reqData['reply_id'] 	= '';
		$reqData['mediaUrl'] 	= '';					
		// $autRepMsg = $this->sendMsg($reqData);		
		$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
		sleep(3);
		$options =['mm'=>'Main menu', 'end'=>'End session'];	
		$textMsg = 'Please choose below option for proceed further.';
		$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $textMsg, 'TEXT', $options);
		
		return $returnArr 	= ['status' => 'success', 'msg' => 'success'];
	}

	public function helpSection($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = ''){
		
		$log = Log::channel('wp_webhooks');		
		$log->debug(__line__."\n\n helpSection --- input : ". $input);
		$contFlag = false;
		if(empty($input))
		{
			$product = ['How to Register in Smartsaver App?','How to update your personal details?','How to Redeem Points?','How to update your Bank Details?','How to Scan QR codes?'];
			$productTitle = ['FAQ-1 :','FAQ-2 :','FAQ-3 :','FAQ-4 :','FAQ-5 :'];
			$listArr=["button" => "Help Section", "sections" => []];
			$rows 	=[];
			$id 	=1;
			foreach ($product as $k => $val) {
				
				if($k <= 7)
				{
					$rows[] = [
						"id" => $k.'-s2-0-'.$id,
						"title" => $productTitle[$k],
						"description"=>$val
					];
				}
				$id++;
			}
			$log->debug(__line__."\n\n helpSection --- rows : ". json_encode($rows));
			if(!empty($rows) && count($product) > 8)
			{
				$rows[] = [
					"id" => 'n-s1-0-'.$id,
					"title" => 'Scroll more'
				];
			}
			else{
				$rows[] = [
					"id" => 'mm-s0-0-0',
					"title" => 'Main menu'
				];
				$rows[] = [
					"id" => 'end-s0-0-0',
					"title" => 'End session'
				];
			}
			$log->debug(__line__."\n\n rows : ". json_encode($rows));
			$listArr["sections"][] = ["rows" => $rows];					
			$list = json_encode($listArr, JSON_PRETTY_PRINT);
			
			$log->debug(__line__."\n\n helpSection --- list : ". $list);
			
			$reqData['client_id']	= $client;
			$reqData['campaign_id'] = $campaign;
			$reqData['mobile'] 		= $mobile;
			$reqData['agent_id'] 	= 0;
			$reqData['chatID']		= $chatID;
			$reqData['message_type']= 'TEXT'; 
			$reqData['event'] 		= '';
			$reqData['message'] 	= 'Select Questions.';
			$reqData['reply_id'] 	= '';
			$reqData['mediaUrl'] 	= '';	
			$reqData['templateType']= 'LIST';	
			$reqData['action'] 		= $list;	
			$reqData['interactive_type']= 'list';	
			$reqData['footer'] 		= '';
			$reqData['templateID'] 	= 0;
			// $this->sendTemplate($reqData);
			$result = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);	
			$returnArr = ['status' => 'progress', 'msg' => 'progress'];
		}
		else{
			$log->debug(__line__."\n\n else -- optionID : ". $optionID);
			if(!empty($optionID))
			{
				$parentURL = url('/catalogue_brochure/ElectricianFlow/help/');
				$SKUARR = array('1' => array('name' => 'Help 1- Register', 'url' => 'Help1.jpeg'), '2' => array('name' => 'Help 2- Update personal details', 'url' => '2.jpeg'), '3' => array('name' => 'Help 3- Redeem Points', 'url' => 'Help3.jpeg'), '4' => array('name' => 'Help 4- Update Bank Details', 'url' => 'Help4.jpeg'), '5' => array('name' => 'Help 5- How to Scan', 'url' => 'Help5.jpeg'));
				$exploxdID 	= explode('-',$optionID);
				$index 		= (isset($exploxdID[0]) && $exploxdID[0] != 'n') ? $exploxdID[0] : 'n';
				$preID 		= isset($exploxdID[1]) ? $exploxdID[1] : '';
				$maxID 		= isset($exploxdID[2]) ? $exploxdID[2] : 0;
				$catID 		= isset($exploxdID[3]) ? $exploxdID[3] : 1;
				$menuKey 	= isset($exploxdID[4]) ? $exploxdID[4] : '';
				
				$log->debug(__line__."\n\n else -- catID : ". $catID);			
				$mediaUrl 	= $parentURL.'/'.$SKUARR[$catID]['url']; 					
				// $msgRes = $this->sendMsg($reqData);	
				$log->debug(__line__."\n\n mediaUrl : ". $mediaUrl);
				$options =['mm'=>'Main menu', 'end'=>'End session'];
				$msgRes = $this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $SKUARR[$catID]['name'], 'IMAGE', $options, $mediaUrl);
				
				$returnArr = ['status' => 'success', 'msg' => 'success'];
				if($msgRes['status'] == 'success'){
					$contFlag = true;
				}
				$log->debug(__line__."\n\n else -- contFlag : ". $contFlag);
			}
		}
		return $returnArr;
	}
	
	public function programInformation($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = '')
	{
		$msg = 'Be a part of Anchor Smart Saver, an electrician loyalty program and earn rewards on purchasing Anchor Products.

			To avail the benefit, follow three simple steps.
			1) Register and share your details.
			2) Scan the QR code.
			3) Redeem the cash collected.

			Watch the video to get more information and register today!! 
			https://www.youtube.com/watch?v=arCtggX0MZg&t=16s
			';
		$reqData['client_id']	= $client;
		$reqData['campaign_id'] = $campaign;
		$reqData['mobile'] 		= $mobile;
		$reqData['agent_id'] 	= 0;
		$reqData['chatID']		= $chatID;
		$reqData['message_type']= 'text'; 
		$reqData['event'] 		= '';
		$reqData['message'] 	= $msg;
		$reqData['reply_id'] 	= '';
		$reqData['mediaUrl'] 	= '';					
		// $autRepMsg = $this->sendMsg($reqData);		
		$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
		sleep(3);
		$options =['mm'=>'Main menu', 'end'=>'End session'];	
		$textMsg = 'Please choose below option for proceed further.';		
		$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $textMsg, 'TEXT', $options);
		
		return $returnArr 	= ['status' => 'success', 'msg' => 'success'];
	}

	public function SKU($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = '')
	{
		$log = Log::channel('wp_webhooks');		
		$log->debug(__line__."\n\n SKU --- input : ". $input);
		$contFlag = false;
		if(empty($input))
		{
			$product = ['Switch (WD)','Switch Gear (SWG)','Wires (WCT)'];
			$listArr=["button" => "SKU List", "sections" => []];
			$rows 	=[];
			$id 	=1;
			foreach ($product as $k => $val) {
				
				if($k <= 7)
				{
					$rows[] = [
						"id" => $k.'-s2-0-'.$id,
						"title" => $val,
					];
				}
				$id++;
			}
			$log->debug(__line__."\n\n SKU --- rows : ". json_encode($rows));
			if(!empty($rows) && count($product) > 8)
			{
				$rows[] = [
					"id" => 'n-s1-0-'.$id,
					"title" => 'Scroll more'
				];
			}
			else{
				$rows[] = [
					"id" => 'mm-s0-0-0',
					"title" => 'Main menu'
				];
				$rows[] = [
					"id" => 'end-s0-0-0',
					"title" => 'End session'
				];
			}
			$log->debug(__line__."\n\n rows : ". json_encode($rows));
			$listArr["sections"][] = ["rows" => $rows];					
			$list = json_encode($listArr, JSON_PRETTY_PRINT);
			
			$log->debug(__line__."\n\n SKU --- list : ". $list);
			
			$reqData['client_id']	= $client;
			$reqData['campaign_id'] = $campaign;
			$reqData['mobile'] 		= $mobile;
			$reqData['agent_id'] 	= 0;
			$reqData['chatID']		= $chatID;
			$reqData['message_type']= 'TEXT'; 
			$reqData['event'] 		= '';
			$reqData['message'] 	= 'Please select SKU from the list.';
			$reqData['reply_id'] 	= '';
			$reqData['mediaUrl'] 	= '';	
			$reqData['templateType']= 'LIST';	
			$reqData['action'] 		= $list;	
			$reqData['interactive_type']= 'list';	
			$reqData['footer'] 		= '';
			$reqData['templateID'] 	= 0;
			// $this->sendTemplate($reqData);
			$result = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);	
			$returnArr = ['status' => 'progress', 'msg' => 'progress'];
		}
		else{
			$log->debug(__line__."\n\n else -- optionID : ". $optionID);
			if(!empty($optionID))
			{
				$parentURL = url('/catalogue_brochure/ElectricianFlow/SKU/');
				$SKUARR = array('1' => array('name' => 'WD rewards', 'url' => 'WDrewards.jpg'), '2' => array('name' => 'SWG rewards', 'url' => 'WCTrewards.jpg'), '3' => array('name' => 'WCT rewards', 'url' => 'SWGrewards.jpg'));
				$exploxdID 	= explode('-',$optionID);
				$index 		= (isset($exploxdID[0]) && $exploxdID[0] != 'n') ? $exploxdID[0] : 'n';
				$preID 		= isset($exploxdID[1]) ? $exploxdID[1] : '';
				$maxID 		= isset($exploxdID[2]) ? $exploxdID[2] : 0;
				$catID 		= isset($exploxdID[3]) ? $exploxdID[3] : 1;
				$menuKey 	= isset($exploxdID[4]) ? $exploxdID[4] : '';
				
				$log->debug(__line__."\n\n else -- catID : ". $catID);			
				$mediaUrl 	= $parentURL.'/'.$SKUARR[$catID]['url'];					
				// $msgRes = $this->sendMsg($reqData);	
				$log->debug(__line__."\n\n mediaUrl : ". $mediaUrl);
				$options =['mm'=>'Main menu', 'end'=>'End session'];
				$msgRes = $this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $SKUARR[$catID]['name'], 'IMAGE', $options, $mediaUrl);
				
				$returnArr = ['status' => 'success', 'msg' => 'success'];
				if($msgRes['status'] == 'success'){
					$contFlag = true;
				}
				$log->debug(__line__."\n\n else -- contFlag : ". $contFlag);
			}
		}
		return $returnArr;
	}
	
	public function training($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = ''){
		
		$reqData['client_id']	= $client;
		$reqData['campaign_id'] = $campaign;
		$reqData['mobile'] 		= $mobile;
		$reqData['agent_id'] 	= 0;
		$reqData['chatID']		= $chatID;
		$reqData['message_type']= 'text'; 
		$reqData['event'] 		= '';
		$reqData['message'] 	= 'Watch the video to get more information and register today!! 
									https://www.youtube.com/watch?v=arCtggX0MZg&t=16s
									';
		$reqData['reply_id'] 	= '';
		$reqData['mediaUrl'] 	= '';					
		// $autRepMsg = $this->sendMsg($reqData);		
		$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
		sleep(3);
		$options =['mm'=>'Main menu', 'end'=>'End session'];
		$textMsg = 'Please choose below option for proceed further.';	
		$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $textMsg, 'TEXT', $options);		
		return $returnArr 	= ['status' => 'success', 'msg' => 'success'];
	}
}
