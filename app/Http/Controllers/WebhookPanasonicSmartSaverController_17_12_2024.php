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
use App\Models\Panasonic_Api_Log;
use DateTime;

class WebhookPanasonicSmartSaverController extends Controller
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
	public $hyperlocalCampaign = 19;
	public $whatsappcrud = array();
	public $auth_url = '';

	
	public function __construct()
    {
		$this->url           	= Config::get('custom.gupshup.whatsapp.send-api.url');
		$this->wp_number		= Config::get('custom.gupshup.whatsapp.send-api.wp_number');
		$this->hsm_userid		= Config::get('custom.gupshup.whatsapp.send-api.hsm-userid');
		$this->hsm_password		= Config::get('custom.gupshup.whatsapp.send-api.hsm-password');
		$this->twoway_userid	= Config::get('custom.gupshup.whatsapp.send-api.twoway-userid');
		// $this->twoway_password	= Config::get('custom.gupshup.whatsapp.send-api.twoway-password');
		$this->twoway_password	=  Config::get('custom.gupshup.whatsapp.send-api.twoway-password');
		$this->auth_url	= Config::get('custom.APIUrl.auth_url');
		$this->mobile_status_url = Config::get('custom.APIUrl.mobile_status');
		$this->end_user_profile = Config::get('custom.APIUrl.end_user_profile');
		$this->check_balance_points = Config::get('custom.APIUrl.check_balance_points');
		$this->club_benefits = Config::get('custom.APIUrl.club_benefits');
		
		$this->globalTimestamp	= round(microtime(true) * 1000);
		
		$this->whatsappcrud = Config::get('custom.gupshup.whatsapp');
		
	}
	
	public function getcrud($waNumber)
	{
		$log = Log::channel('wp_webhooks');
		$campaign_data = DB::table('campaign')
                ->select('id', 'name', 'client_id', 'auto_reply_id', 'allocation_type', 'call_window_from', 'call_window_to', 'working_days', 'holiday_start', 'holiday_end', 'holiday_name', 'wp_crud')				
                ->whereIn('status', [0,1])
                ->where('wp_number', $waNumber)
                ->first();
				
		if(!empty($campaign_data->wp_crud))
		{
			$wp_crudArr = json_decode($campaign_data->wp_crud, true);
			// $log->debug(__line__."\n Success  : campaign_data->WP_CRUD".$campaign_data->wp_crud);
			// $log->debug(__line__."\n Success  : hsm_userid".$wp_crudArr['twoway_password']);
			$this->wp_number		= $waNumber;
			$this->hsm_userid		= $wp_crudArr['hsm_userid'];
			$this->hsm_password		= $wp_crudArr['hsm_password'];
			$this->twoway_userid	= $wp_crudArr['twoway_userid'];
			$this->twoway_password	= $wp_crudArr['twoway_password'];
		}
		else{
			$log->debug(__line__."\n Error : WhatsApp Account Details should mapped with campaign");
		}
		// return $wp_crud	= isset($campaign_data->wp_crud) && !empty($campaign_data->wp_crud) ? $campaign_data->wp_crud : [];
		return $campaign_data	= (isset($campaign_data) && !empty($campaign_data)) ? $campaign_data : [];
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
		$log->debug(__line__."\n\n ---- Request Data : ". $request);
		$log->debug(__line__."\n\n ---- Start request data--- : ". print_r($data, true));
		if(empty($data))
		{
			$data = $request->json()->all();
			$log->debug(__line__."\n\n ---- Start request data : ". print_r($data,true));
		}
		
		
		
		/* $productLevel1 = array(
			'1'=> array('title' => 'Switches and Sockets', 'desc'=> ''),
			'2'=> array('title' => 'Switchgear & Protection', 'desc'=> ''),
			'3'=> array('title' => 'Wires and Cable', 'desc'=> ''),
			'4'=> array('title' => 'Led Lighting', 'desc'=> ''),
			'5'=> array('title' => 'Fan', 'desc'=> ''),
			'6'=> array('title' => 'Water Heater', 'desc'=> ''),
			'7'=> array('title' => 'Smart Homes', 'desc'=> '')			
		);
		$productLevel2 = array(
			'1'=> array(
					'1.1' => array('title' => 'Anchor', 'desc'=> ''),
					'1.2' => array('title' => 'Panasonic', 'desc'=> '')
				),
			'2'=> array(
					'2.1' => array('title' => 'Anchor Uno E series', 'desc'=> ''),
					'2.2' => array('title' => 'UNO', 'desc'=> ''),
					'2.3' => array('title' => 'Anchor UNO Plus', 'desc'=> ''),
					'2.4' => array('title' => 'Panasonic Switch Gear', 'desc'=> '')
				),
			'3'=> array( 'url' => 'https://marcom.lsin.panasonic.com/Uploads/Catalogue/Wire_&_Cable_202312261659586681900588413.pdf'),
			'4'=> array(
					'4.1' => array('title' => 'Anchor', 'desc'=> ''),
					'4.2' => array('title' => 'Panasonic', 'desc'=> '')
				),
			'5'=> array( 'url' => 'https://marcom.lsin.panasonic.com/Uploads/Catalogue/IAQ_Catalogue_202312261458059661432463675.pdf'),
			'6'=> array(
					'6.1' => array('title' => 'Anchor', 'desc'=> ''),
					'6.2' => array('title' => 'Panasonic', 'desc'=> '')
				),
			'7'=> array(
					'7.1' => array('title' => 'Thea IQ', 'desc'=> ''),
					'7.2' => array('title' => 'Vetaar', 'desc'=> ''),
					'7.3' => array('title' => 'Miraie', 'desc'=> '')
				)		
		); */
		
		$newChat	= false;
        $eventType 	= 'message';
		$mobile 	= $data['mobile'];
		$type 		= $data['type'];
		$name 		= $data['name'];
		$waNumber 	= $data['waNumber'];
		// $text 		= $data['text'];
		$timestamp 	= isset($data['timestamp']) ? $data['timestamp'] : $this->globalTimestamp;
		$uniqueUuid = CommonTraits::uuid();
		$mediaUrl 	= $mediaCaption = $message_id = $mediaCaption = $text = $publicUrl = '';
		$templateID = 0;
		$intID ='';
		
		// $log->debug(__line__.'Webhook Row Data : ' . print_r($data, true));
		// $log->debug(__line__.'wp_number : ' . $this->wp_number);
		
		if (!($mobile && $type && $timestamp && $waNumber)) {
			$log->debug(__line__.'Error : Invalid Data Received');			
            return response()->json(['error' => 'Invalid Data Received'], 400);
        }
		
		try {
			
			$campaign_data = $this->getcrud($waNumber);
				
			// $log->debug(__LINE__.'Qeury campaign_data : ' . print_r($campaign_data, true));
			
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
					$text = trim($data['text']);						
				}
				else if($type == 'interactive') {
					$interactiveFlag = true;		
					$interactiveData = json_decode($data['interactive'], true);	
					$intID 		= $interactiveData[$interactiveData['type']]['id'];
					$text 		= $interactiveData[$interactiveData['type']]['title'];
					$expIntID 	= explode('-',$intID);
					$opID 		= (isset($expIntID[0]) && $expIntID[0] != 'mm') ? $expIntID[0] : 'mm';
					
					$log->debug(__LINE__.'interactive --- : '.$intID);
					$log->debug(__LINE__.'opID --- : '.$opID);
					//$promptData = $this->deleteIfExists($chatID, ['REPLY_BUTTON']);
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
			
			$log->debug(__LINE__.' opID : ' . $opID);	

			$log->debug(__LINE__.'Chat Details : ' . print_r($chatDetails, true));			
			
			if($chatDetails['assigning_flag'] == 2 && ($chatDetails['assigning_to'] != null || $chatDetails['assigning_to'] != '')){
				exit;		
			}

			// $log->debug(__LINE__.'Webhook received and processed');
			//Default Param
			$reqData['client_id']	= $client;
			$reqData['campaign_id'] = $campaign;
			$reqData['mobile'] 		= $mobile;
			$reqData['agent_id'] 	= 0;
			$reqData['chatID']		= $chatID;
			$reqData['message_type']= 'text'; 
			$reqData['event'] 		= '';
			$reqData['message'] 	= 'NA';
			$reqData['reply_id'] 	= '';
			$reqData['mediaUrl'] 	= '';
			
			$textCopy = $text;
			
			//Flow_leve & language management option
			$log->debug(__LINE__.'ertertretret : ' );	
			$flowMData = $this->AddChatToFlowManager($mobile);	
			$log->debug(__LINE__.'flowMData : ' . print_r($flowMData, true));				
			if($flowMData->flow_level == 0)
			{
				$flow_level = 0;
				$text = '';
				$this->updateFlowLevel($flow_level, 0, $mobile);
				$flowMData = $this->getFlowManager($mobile);
			}
			else{
				if($opID == 'mm')
				{
					$flow_level = 1;
					$text = $flowMData->language;
					$this->updateFlowLevel($flow_level, 0, $mobile);
					$flowMData = $this->getFlowManager($mobile);
				}
				if($opID == 'end')
				{
					$flow_level = 2;
					$text = '2.9';
					$this->updateFlowLevel($flow_level, 0, $mobile);
					$flowMData = $this->getFlowManager($mobile);
				}
				
				if(in_array($opID, ['2.1', '2.2', '2.3','2.4','2.5','2.6', '2.7', '2.8', '2.9', '2.10', '2.11', '2.4.1', '2.4.2', '2.4.3', '2.5.1', '2.5.2', '2.5.3','2.6.1','2.6.1.1','2.6.1.2','2.6.1.3','2.6.1.4', '2.6.2','2.6.2.1','2.6.2.2','2.6.2.3','2.6.2.4','2.8.1', '2.8.2', '2.8.3', '2.11.1', '2.11.2', '2.11.3']))
				{
					$flow_level = 2;
					$text = $opID;
					$this->updateFlowLevel($flow_level, $opID, $mobile);
					$flowMData = $this->getFlowManager($mobile);
				}
				
				

				if(in_array($opID, ['1.1', '1.2', '1.3', '1.4', '1.5', '1.6', '1.7']))
				{
					$flow_level = 1;
					$text = $opID;
					$this->updateFlowLevel($flow_level, $opID, $mobile);
					$flowMData = $this->getFlowManager($mobile);
				}
				
				if(in_array($flowMData->flow_sub_level, ['2.11.1', '2.11.2', '2.11.3', '2.11.4']))
				{
					$flow_level = 2;
					$text = $flowMData->flow_sub_level;
					$this->updateFlowLevel($flow_level, $flowMData->flow_sub_level, $mobile);
					$flowMData = $this->getFlowManager($mobile);
				}
			}
			$flow_level = $flowMData->flow_level;
			$flow_sub_level = $flowMData->flow_sub_level;
			$language = $flowMData->language;
			$next_level = ($flow_level + 1);
			$pre_level = ($flow_level > 0) ? ($flow_level - 1) : $flow_level;
			
			$log->debug(__LINE__.'Flow Level : ' . $flow_level);
			$log->debug(__LINE__.'Flow Sub Level : ' . $flow_sub_level);

			if($flow_sub_level == '2.6.2.4')
			{
				$flow_level = 2;
				$text = '2.6.2.4';
				// $this->updateFlowLevel($flow_level, $opID, $mobile);
				// $flowMData = $this->getFlowManager($mobile);
			}
			
			// main flow
			if($flow_level == 0)
			{							
				// $reqData['message'] 	= "Hello,\nWelcome to Anchore by Panasonic Influencers Program. *Please select your preferred language*.\n 1. English\n 2. Hindi\n 3. Marathi\n 4. Tamil\n 5. Kanada\n 6. Malayalam\n 7. Gujarati\n\n*Type a number between 1-7 to make your selection*.";
				// $autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
				
				
				$listArr=["button" => 'Language Selection', "sections" => []];
				$listArr["sections"][] = ["rows" => array(array(
							"id" => '1.1',
							"title" => 'English'
						), array(
							"id" => '1.2',
							"title" => 'Hindi'
						), array(
							"id" => '1.3',
							"title" => 'Marathi'
						), array(
							"id" => '1.4',
							"title" => 'Tamil'
						), array(
							"id" => '1.5',
							"title" => 'Kanada'
						), array(
							"id" => '1.6',
							"title" => 'Malayalam'
						), array(
							"id" => '1.7',
							"title" => 'Gujarati'
						))];					
				$list = json_encode($listArr, JSON_PRETTY_PRINT);
				
				$reqData['message'] 	= "Hello *".$name."*,\n\nWelcome to *Anchore by Panasonic* Influencers Program. \n\nPlease select your preferred language.";
				$reqData['templateType']= 'LIST';	
				$reqData['action'] 		= $list;
				$reqData['templateID'] 	= 0;	
				$reqData['interactive_type']= 'list';
				$log->debug(__LINE__.'reqData for case - 2.8.1 : ' . print_r($reqData, true));
				$msgRes = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);
				$this->updateFlowLevel($next_level, 0, $mobile);
			}		
			else if($flow_level == 1)
			{
				$log->debug(__LINE__.'Text Details : ' . $text);
				$log->debug(__LINE__.'auth_url : ' . $this->auth_url);
				
				
				// exit;

				if(in_array($text, array('1.1','1.2','1.3','1.4','1.5','1.6','1.7'))) // after language selection
				{										
					$isRegistered = false;
					// // API Integration to check the registration
					$token = $this->getToken($this->auth_url);
					$log->debug(__LINE__.'token : ' . $token);
					$isRegistered = $this->getUserisregistered($token, $mobile, $this->mobile_status_url);//store this response in a table for future purpose pending
					$log->debug(__LINE__.'isRegistered : ' . print_r($isRegistered,true));
					
					// // $log->debug(__LINE__.'data for non register user against mobile no '.$mobile.' : ' . print_r($result, true));
					// if($this->getNonCustDetails($mobile))
					// {
					// 	$isRegistered = true;
					// }
					
					if($isRegistered)
					{
						if(!empty($opID) && $opID != 'mm'){
							$reqData['message']= "Hello *".$name."*, How can I assist you today?";
							$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);	
							sleep(1);
						}
						// $reqData['message']= "Please choose an option below.\n 1. My Profile\n 2. Check Point Balance\n 3. Scheme Points\n 4. Learn About the Program\n 5. Report an Issue\n 6. Training & Support\n 7. Product Brochures\n 8. Program Update\n 9. End Chat\n\n*Type a number between 1-9 to make your selection*.";
						// $autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						// $this->updateFlowLevel($next_level, 0, $mobile, $text);
						
						$listArr=["button" => 'Main Menu', "sections" => []];
						$listArr["sections"][] = ["rows" => array(array(
									"id" => '2.1',
									"title" => 'My Profile'
								), array(
									"id" => '2.2',
									"title" => 'Check Point Balance'
								), array(
									"id" => '2.3',
									"title" => 'Club Status'
								), array(
									"id" => '2.4',
									"title" => 'Learn About the Program'
								), array(
									"id" => '2.5',
									"title" => 'Report an Issue'
								), array(
									"id" => '2.6',
									"title" => 'Training & Support'
								), array(
									"id" => '2.7',
									"title" => 'Product Brochures'
								), array(
									"id" => '2.8',
									"title" => 'Program Update'
								), array(
									"id" => '2.9',
									"title" => 'End'
								))];					
						$list = json_encode($listArr, JSON_PRETTY_PRINT);
						
						$reqData['message'] 	= "*Please choose an option below*. \n"; //.$list msg length is limit of 1024
						$reqData['templateType']= 'LIST';	
						$reqData['action'] 		= $list;
						$reqData['templateID'] 	= 0;	
						$reqData['interactive_type']= 'list';
						$log->debug(__LINE__.'reqData for case - 2.8.1 : ' . print_r($reqData, true));
						$msgRes = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);
						$this->updateFlowLevel($next_level, 0, $mobile, $text);
						
					}
					else{
						//$options =['2.4'=>'About Program', '2.6'=>'Training & Support', '2.7'=>'Product Brochures', '2.11'=>'Register Now'];	
						//$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'It looks like you are new here. You can explore the options below or register to access full features.', 'TEXT', $options);
						
						$listArr=["button" => 'Main Menu', "sections" => []];
						$listArr["sections"][] = ["rows" => array(array(
									"id" => '2.4',
									"title" => 'About Program'
								), array(
									"id" => '2.6',
									"title" => 'Training & Support'
								), array(
									"id" => '2.7',
									"title" => 'Product Brochures'
								), array(
									"id" => '2.11',
									"title" => 'Register Now'
								))];					
						$list = json_encode($listArr, JSON_PRETTY_PRINT);
						
						$reqData['message'] 	= "It looks like you are *NEW* here. You can explore the options below or register to access full features. \n"; //.$list msg length is limit of 1024
						$reqData['templateType']= 'LIST';	
						$reqData['action'] 		= $list;
						$reqData['templateID'] 	= 0;	
						$reqData['interactive_type']= 'list';
						$log->debug(__LINE__.'non reg : ' . print_r($reqData, true));
						$msgRes = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);
						$this->updateFlowLevel($next_level, 0, $mobile, $text);
						
					}
					
				}
				else{
					$reqData['message']= 'Invalid attempt, Please choose the right option';
					$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
					sleep(2);
					$options =['mm'=>'Main menu', 'end'=>'End session'];	
					$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
					
				}
			}
			else if($flow_level == 2)
			{
				$log->debug(__LINE__.'Text hfgjhij : ' . $text);
				switch($text)
				{					
					case '2.1':
						//pooja 10-18-2024
						$token = $this->getToken($this->auth_url);
						$log->debug(__LINE__.'token : ' . $token);
						$profile_details = $this->getProfileDetails($this->end_user_profile, $mobile , $token);
						$log->debug(__LINE__.'Profile_details : ' . print_r($profile_details,true));
						$reqData['message']= "*Profile Details as follow*.\n\n Mobile - ". $profile_details['mobile']." \n Name - ". $profile_details['name'] ."\n Club - ".$profile_details['club']." \n Profile Status - ".$profile_details['profile_status']."";
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						$this->updateFlowLevel($next_level, 0, $mobile);
						sleep(2);
						$options =['mm'=>'Main menu', 'end'=>'End session'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						break;
					case '2.2':
						//pooja 10-18-2024
						$token = $this->getToken($this->auth_url);
						$log->debug(__LINE__.'token : ' . $token);
						$check_balance_points = $this->checkBalancePoints($this->check_balance_points, $mobile , $token);
						$log->debug(__LINE__.'check_balance_points : ' . print_r($check_balance_points, true));

						$reqData['message']= "*Your Balance Details as follow*.\n\n Your Available Points are - ".$check_balance_points['total_earnings']." \n Your Redeemed Points are - ".$check_balance_points['total_redeemed_points']."\n Your Balance is - ".$check_balance_points['total_balance']."\n Your Current FY Earning is - ".$check_balance_points['curr_fy_earning']."\n Your Current FY Redeemed points - ".$check_balance_points['curr_fy_redeemed_points']."\n Your Current FY Balance - ".$check_balance_points['curr_fy_balance']."";
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						$this->updateFlowLevel($next_level, 0, $mobile);
						sleep(2);
						$options =['mm'=>'Main menu', 'end'=>'End session'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						break;
					case '2.3':
						$token = $this->getToken($this->auth_url);
						$log->debug(__LINE__.'token : ' . $token);
						$check_balance_points = $this->club_benefits($this->club_benefits, $mobile, $token);
						$log->debug(__LINE__.'club benefits : ' . print_r($check_balance_points, true));
						$current_club_benefits = '';
						foreach($check_balance_points['curr_club_benefits'] as $current_benefits){
							$current_club_benefits .= '•'.$current_benefits."\n";
						}

						$next_club_benefits = '';
						foreach($check_balance_points['next_club_benefits'] as $next_benefits){
							$next_club_benefits .= '•'.$next_benefits."\n";
						}

						// $reqData['message']= "Dear Electrician,\n\nYou have earned *10 points* in Smart Saver Program and have not achived any Slab in *Super Hero Scheme*. Earn *1500* points before *30th Sep 2024* and win *Slab 1-1000* Bonus Points in Smart Saver Program Super Heros Scheme. Scheme valid from *1st Apr 2024 till 30th Sep 2024*";
						$reqData['message']= "*Club Benefits for you are as follows:-* \n\nYour Total Earrings - ".$check_balance_points['total_earnings']."\nYour Current FY Earrings - ".$check_balance_points['curr_fy_earning']."\nYour Current Club - ".$check_balance_points['curr_club']."\nYour Next Club - ".$check_balance_points['next_club']."\n\n*Current Club Benefits* - \n".$current_club_benefits."\n*Next Club Benefits* -\n".$next_club_benefits."";
							$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
							$this->updateFlowLevel($next_level, 0, $mobile);
						sleep(2);
						$options =['mm'=>'Main menu', 'end'=>'End session'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						break;
					case '2.4':
						$reqData['message_type']= "DOCUMENT"; 
						// $reqData['message']= "*For Video Link*: www.videolink.com\n\n*For PDF Link*: www.PDFlink.com\n\n*For Program Overview*:\nOur Influencer Program is designed to reward you for promoting our products. You earn points to every successful recommendation that leads to a sale. These points can be redeemed for various rewards. Explore the other options in the menu to learn more!";
						$reqData['message']= "*For Video Link*: https://youtu.be/VFzW2IXYJL0?si=pVTtxc6TBdflxHN3 \n\nFor Program Overview:\nOur Influencer Program is designed to reward you for promoting our products. You earn points to every successful recommendation that leads to a sale. These points can be redeemed for various rewards. Explore the other options in the menu to learn more!";
						$reqData['mediaUrl'] 	= 'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/About Smart Saver Program.pdf';

						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						$this->updateFlowLevel($next_level, 0, $mobile);
						sleep(3);
						$options =['mm'=>'Main menu', 'end'=>'End session'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						break;						
					// case '2.4.1':
					// 	$reqData['message']= '*For Video Link*: www.videolink.com';
					// 		$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
					// 		$this->updateFlowLevel($next_level, 0, $mobile);
					// 	sleep(2);
					// 	$options =['mm'=>'Main menu', 'end'=>'End session'];	
					// 	$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
					// 	break;						
					// case '2.4.2':
					// 	$reqData['message']= '*For PDF Link*: www.PDFlink.com';
					// 		$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
					// 		$this->updateFlowLevel($next_level, 0, $mobile);
					// 	sleep(2);
					// 	$options =['mm'=>'Main menu', 'end'=>'End session'];	
					// 	$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
					// 	break;						
					// case '2.4.3':
					// 	$reqData['message']= "*For Program Overview*:\n\nOur Influencer Program is designed to reward you for promoting our products. You earn points to every successful recommendation that leads to a sale. These points can be redeemed for various rewards. Explore the other options in the menu to learn more!";
					// 		$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
					// 		$this->updateFlowLevel($next_level, 0, $mobile);
					// 	sleep(2);
					// 	$options =['mm'=>'Main menu', 'end'=>'End session'];	
					// 	$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
					// 	break;
					case '2.5':
						$options =['2.5.1'=>'Damaged QR Code', '2.5.2'=>'Duplicate Product', '2.5.3'=>'No QR Code'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'Report any issues you are facing.', 'TEXT', $options);
						break;
					case '2.5.1':
						$reqData['message']= "Please take a picture of the *QR code* and send it here. Our customer care team will contact you shortly";
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						$this->updateFlowLevel($next_level, 0, $mobile);
						break;
					case '2.5.2':
						$reqData['message']= "Please take a picture of the *QR code* and send it here. Our customer care team will contact you shortly";
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						$this->updateFlowLevel($next_level, 0, $mobile);
						break;
					case '2.5.3':
						$reqData['message']= "Please take a picture of the *QR code* and send it here. Our customer care team will contact you shortly";
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						$this->updateFlowLevel($next_level, 0, $mobile);
						break;
					case '2.6':
						$options =['2.6.1'=>'Training Videos', '2.6.2'=>'Customer Care'];		
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'Please access training materials & support.', 'TEXT', $options);
						break;
					case '2.6.1':
						$listArr=["button" => 'Training Videos', "sections" => []];
						$listArr["sections"][] = ["rows" => array(array(
									"id" => '2.6.1.1',
									"title" => 'How to Scan QR Code'
								), array(
									"id" => '2.6.1.2',
									"title" => 'How to redeem Points'
								), array(
									"id" => '2.6.1.3',
									"title" => 'How to Update KYC'
								), array(
									"id" => '2.6.1.4',
									"title" => 'How to Upload Documents'
								))];					
						$list = json_encode($listArr, JSON_PRETTY_PRINT);
						
						$reqData['message'] 	= 'Choose from the following options.'; //.$list msg limit 1024
						$reqData['templateType']= 'LIST';	
						$reqData['action'] 		= $list;
						$reqData['templateID'] 	= 0;	
						$reqData['interactive_type']= 'list';
						$log->debug(__LINE__.'reqData for case - 2.8.1 : ' . print_r($reqData, true));
						$msgRes = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);
						break;						
					
					
					case '2.6.1.1':
						// $reqData['message_type']= "DOCUMENT"; 
						$reqData['message'] 	= "*How to Scan QR Code*\nhttps://www.youtube.com/watch?v=VFzW2IXYJL0";
						// $reqData['mediaUrl'] 	= 'https://edas-webapi.edas.tech/vaaniSMDev/catalogue_brochure/samplevedio.mp4';
						// $reqData['mediaUrl'] 	= "https://www.youtube.com/watch?v=VFzW2IXYJL0";
						
						
						$log->debug(__LINE__.'reqData for case - 2.8.1 : ' . print_r(json_encode($reqData)));
						// $msgRes = $this->sendMsg($reqData);
						$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						sleep(5);
						$options =['mm'=>'Main menu', 'end'=>'End session'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						break;						
					case '2.6.1.2':
						// $reqData['message_type']= "DOCUMENT"; 
						$reqData['message'] 	= "*How to redeem Points*\nhttps://www.youtube.com/watch?v=VFzW2IXYJL0";
						// $reqData['mediaUrl'] 	= 'https://edas-webapi.edas.tech/vaaniSMDev/catalogue_brochure/samplevedio.mp4';
						$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						sleep(5);
						$options =['mm'=>'Main menu', 'end'=>'End session'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						break;						
					case '2.6.1.3':
						// $reqData['message_type']= "DOCUMENT"; 
						$reqData['message'] 	= "*How to Update KYC*\nhttps://www.youtube.com/watch?v=VFzW2IXYJL0";
						// $reqData['mediaUrl'] 	= 'https://edas-webapi.edas.tech/vaaniSMDev/catalogue_brochure/samplevedio.mp4';
						$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						sleep(5);
						$options =['mm'=>'Main menu', 'end'=>'End session'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						break;						
					case '2.6.1.4':
						// $reqData['message_type']= 'DOCUMENT'; 
						$reqData['message'] 	= "*How to Upload Documents*\nhttps://www.youtube.com/watch?v=VFzW2IXYJL0";
						// $reqData['mediaUrl'] 	= 'https://edas-webapi.edas.tech/vaaniSMDev/catalogue_brochure/samplevedio.mp4';
						$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						sleep(5);
						$options =['mm'=>'Main menu', 'end'=>'End session'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						break;						
					case '2.6.2':
						$listArr=["button" => 'List Details', "sections" => []];
						$listArr["sections"][] = ["rows" => array(array(
									"id" => '2.6.2.1',
									"title" => 'Customer care'
								), array(
									"id" => '2.6.2.2',
									"title" => 'Chat with agent'
								), array(
									"id" => '2.6.2.3',
									"title" => 'Callback request'
								), array(
									"id" => 'mm',
									"title" => 'Main menu'
								), array(
									"id" => 'end',
									"title" => 'End session'
								))];					
						$list = json_encode($listArr, JSON_PRETTY_PRINT);
						
						$reqData['message'] 	= 'Choose from the following options.';
						$reqData['templateType']= 'LIST';	
						$reqData['action'] 		= $list;
						$reqData['templateID'] 	= 0;	
						$reqData['interactive_type']= 'list';
						$log->debug(__LINE__.'reqData for case - 2.6.2 : ' . print_r($reqData, true));
						$msgRes = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);
						
						$log->debug(__LINE__.'msgRes for case - 2.6.2 : ' . print_r($msgRes, true));
						break;
					case '2.6.2.1':
						$reqData['message_type']= "TEXT"; 
						$reqData['message'] 	= "You can contact our support center at below given details. 👇
•	Customer Service Number: 📞 02241304130
•	Electrician Program Support Number: 📞 9136088606
•	Retailer Program Support Number: 📞 8657749002
•	Sales Enquiry: 📞 02261406140

Thank you for contacting Anchor by Panasonic 😊.";
						$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						sleep(5);
						$options =['mm'=>'Main menu', 'end'=>'End session'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						break;
					case '2.6.2.2':
						$log->debug(__LINE__.'assign to expert: ');
						$this->assignToExpert($crudDetails, $client, $campaign, $mobile, $chatID, $opID);		
						break;
					case '2.6.2.3':
						$reqData['message']= "Please mention your preferred date and time(in 24 hours format like 12 Nov 2024 - 10:30) to connect with our Human Resource Recruiter:";
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						$this->updateFlowLevel(2, '2.6.2.4', $mobile);
						break;
					case '2.6.2.4':
						$log->debug(__LINE__.'textcopy: =>'.$textCopy);	

						$result = $this->validateDateTime($textCopy);	
						$log->debug(__LINE__.'validateDateTime: =>'.print_r($result,true));	

						if($result == "failed"){
							$reqData['message']= 'Please enter a valid datetime';
							$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						}else{
							$conv_datetime = $this->convertToMySQLDateTime($textCopy);
							$log->debug(__LINE__.'converted datetime: =>'.print_r($conv_datetime,true));

							$this->setCallback($conv_datetime, $mobile);
							// $this->updateUserDetails($mobile, ['callback_datetime' => $conv_datetime]);
							$reqData['message']= "Thank you, One of our Agents will call you back!";
							$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);	
							sleep(5);
							$options =['mm'=>'Main menu', 'end'=>'End session'];	
							$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						}
						break;
					case '2.7':
						$this->productCatalog($crudDetails, $client, $campaign, $mobile, $chatID);
					
						/* $msgStr = "Please choose an option below.\n ";
						$product = $this->getProductCat();
						foreach($product as $k => $val) {
							$msgStr .= ($k+1).". ".$val->name."\n ";
						}
						$msgStr .= "\n*Type a number between 1-".$product->count()." to make your selection*.";
						$log->debug(__LINE__.'productCat for case  - 7 : ' . print_r($product, true));
						$reqData['message']= $msgStr;
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails); */
						// $this->updateFlowLevel($next_level, 0, $chatID, $text);
						 $this->updateFlowLevel(5, 0, $mobile);
						break;
					case '2.8':
						$options =['2.8.1'=>'Running Schemes', '2.8.2'=>'Announcements', '2.8.3'=>'Product Launch'];		
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'Stay updated with the latest news and updates from our program.', 'TEXT', $options);
						// $this->updateFlowLevel($next_level, 0, $chatID);
						break;
					case '2.8.1':
						$reqData['message_type']= 'DOCUMENT'; 
						$reqData['message'] 	= 'Running Schemes';
						// $reqData['mediaUrl'] 	= 'https://edas-webapi.edas.tech/vaaniSMDev/catalogue_brochure/sample.pdf';
						$reqData['mediaUrl'] 	= 'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/FY24_Q3_SuperHeroScheme.pdf';
						
						$log->debug(__LINE__.'reqData for case - 2.8.1 : ' . print_r(json_encode($reqData)));
						// $msgRes = $this->sendMsg($reqData);
						$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						$log->debug(__LINE__.'msgRes for case - 2.8.1 : ' . print_r(json_encode($msgRes)));
						// $this->updateFlowLevel($next_level, 0, $chatID);
						sleep(5);
						$options =['mm'=>'Main menu', 'end'=>'End session'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						break;
					case '2.8.2':
						$reqData['message_type']= 'DOCUMENT'; 
						$reqData['message'] 	= 'Announcements';
						// $reqData['mediaUrl'] 	= 'https://edas-webapi.edas.tech/vaaniSMDev/catalogue_brochure/sample.pdf';
						$reqData['mediaUrl'] 	= 'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/FY24_Q3_SuperHeroScheme.pdf';

						
						// $msgRes = $this->sendMsg($reqData);
						$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						// $this->updateFlowLevel($next_level, 0, $chatID);
						sleep(5);
						$options =['mm'=>'Main menu', 'end'=>'End session'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						break;
					case '2.8.3':
						$reqData['message_type']= 'DOCUMENT'; 
						$reqData['message'] 	= 'Product Launch';
						// $reqData['mediaUrl'] 	= 'https://edas-webapi.edas.tech/vaaniSMDev/catalogue_brochure/sample.pdf';
						$reqData['mediaUrl'] 	= 'https://edas-webapi.edas.tech/vaaniSMDev/products_brochures/Penta_Wire.png';
						
						// $msgRes = $this->sendMsg($reqData);
						$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						// $this->updateFlowLevel($next_level, 0, $chatID);
						sleep(5);
						$options =['mm'=>'Main menu', 'end'=>'End session'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						break;
					case '2.9':
						$reqData['message']= 'Thank you, LiveChat session has been ended';
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						$this->updateFlowLevel(0, 0, $mobile);
						$this->closedChat($chatID);
						// $this->removeFlowManager($mobile);
						break;
					case '2.11':
						$reqData['message']= 'Lets get you registered! Please provide your *Full Name*(as per *PAN* card)';
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						$this->updateFlowLevel('2', '2.11.1',$mobile);
						break;
					case '2.11.1':
						$this->updateNonRegisteredUser($mobile, ['name' => $textCopy]);
						$reqData['message']= 'Enter your Pincode';
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						$this->updateFlowLevel('2', '2.11.2', $mobile);
						break;
					case '2.11.2':
						$result = $this->validate_pincode($textCopy);
						if($result == 'failed')
						{
							$reqData['message']= 'Please enter valid PIN code';
							$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						}
						else{
							$this->updateNonRegisteredUser($mobile, ['pincode' => $textCopy]);
							$reqData['message']= 'Enter your Mobile Number';
							$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
							$this->updateFlowLevel('2', '2.11.3', $mobile);
						}
						//Send OTP to customer mobile no.
						break;
					case '2.11.3':
						$result = $this->validate_mobile($textCopy);
						if($result == 'failed')
						{
							$reqData['message']= 'Please enter valid mobile no';
							$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						}
						else{
							$otp = $this->generateAlphanumericOTP();
							$this->updateNonRegisteredUser($mobile, ['otp' => $otp]);
							$reqData['message']= "OTP for your mobile verification is *".$otp."*. Do not share this with anyone. \nThis is a case sensitive code.";
							$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
							sleep(2);
							$this->updateNonRegisteredUser($mobile, ['contact_no' => $textCopy]);					
							$reqData['message']= 'We have send an OTP to your Mobile Number, please enter the OTP here.';
							$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
							$this->updateFlowLevel('2', '2.11.4',  $mobile);
						}
						break;
					case '2.11.4':
						if($this->verifyOTP($mobile, $textCopy) == true){
							$this->updateNonRegisteredUser($mobile, ['status' => 2]);
							$reqData['message']= "You have successfully registered. Following are the link for registration \nhttps://play.google.com/store/apps/details?id=com.anchor.views
							\nhttps://apps.apple.com/in/app/anchor-smart-saver/id1399889704
							";
							// $reqData['message']= "You have successfully registered. Following are the link for registration \nhttps://drive.google.com/file/d/1Lq3AVFrM1f202ZLTGap-Rsy-bT8B4jRC/view?usp=drive_link \n\nhttps://apps.apple.com/in/app/anchor-smart-saver/id1399889704
							// 	";
							$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
							$this->updateFlowLevel('1', 0,  $mobile);
							sleep(2);
							$options =['mm'=>'Main menu', 'end'=>'End session'];	
							$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						}
						else{
							
							$reqData['message']= "Please enter valid OTP.It is a case sensitive code";
							$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						}
						break;
					default:
						$reqData['message']= "Invalid request, please choose the right option...";
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						sleep(2);
						$options =['mm'=>'Main menu', 'end'=>'End session'];	
						$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
						break;
				}
				
			}
			else if($flow_level == 3)
			{
				$reqData['message']= "Thank you! Your issue has been reported. You will be contacted soon. ";
				$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
				sleep(5);
				$options =['mm'=>'Main menu', 'end'=>'End session'];	
				$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
			}
			else if($flow_level == 4)
			{
				$this->productCatalog($crudDetails, $client, $campaign, $mobile, $chatID, $optionID,$searchInput);
				
			}
			
			else if($flow_level == 5){
				// $this->productCatalog($crudDetails, $client, $campaign, $mobile, $chatID, '', $textCopy);
				$this->productCatalog($crudDetails, $client, $campaign, $mobile, $chatID, $intID, $textCopy);
				$this->updateFlowLevel(6, 0,  $mobile);
			}else if($flow_level == 6){
				$this->productCatalog($crudDetails, $client, $campaign, $mobile, $chatID, $intID, $textCopy);
			}
			else{
				$reqData['message']= 'Invalid attempt, Please choose the right option';
				$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
				
				sleep(2);
				$options =['mm'=>'Main menu', 'end'=>'End session'];	
				$this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
			}
			
			
         } catch (\Exception $e) {
            // Log any exceptions or errors
			$log->debug(__LINE__.'Error - An error occurred: ' . $e->getMessage());
           // return response()->json(['error' => 'An error occurred'], 500);
        } 
		$log->debug(__LINE__.'Webhook received and processed');
		
		$log->debug(__LINE__.'-----End Webhook-----------\n');
		
		//return response()->json(['status' => 200, 'msg'=>'Webhook received and processed']);
    }
	
	
	public function generateAlphanumericOTP($length = 6) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$otp = '';
		for ($i = 0; $i < $length; $i++) {
			$otp .= $characters[mt_rand(0, strlen($characters) - 1)];
		}
		return $otp;
	}
	
	
	public function updateFlowLevel($flowLevel, $flowSubLevel, $mobile, $language = '')
	{
		$log 		= Log::channel('wp_webhooks');
		$updateData = [
			'flow_level' => $flowLevel,
			'flow_sub_level' => $flowSubLevel,
		];
		if(!empty($language))
		{
			$updateData['language'] = $language;
		}
		$log->debug(__line__."\n updateFlowLevel". json_encode($updateData)." \n"); 
		if(!empty($mobile)){
			$query = DB::connection('panasonic_connection')
				->table('anchor_smart_saver')
				->where('mobile', $mobile)
				->update($updateData);	
		}
	}


	public function setCallback($callback, $mobile)
	{
		$log 		= Log::channel('wp_webhooks');
		$updateData = [
			'callback_datetime' => $callback,
			'callback_inserted_datetime' => \Carbon\Carbon::now(),
		];

		$log->debug(__line__."\n updateFlowLevel". json_encode($updateData)." \n"); 
		if(!empty($mobile)){
			$query = DB::connection('panasonic_connection')
				->table('anchor_smart_saver')
				->where('mobile', $mobile)
				->update($updateData);	
		}
	}
	public function getFlowManager($mobile)
	{
		$flowMData = DB::connection('panasonic_connection')
				->table('anchor_smart_saver')
				->select('flow_level', 'flow_sub_level', 'language')
				->where('mobile', $mobile)
				->first();
		return $flowMData;
	}
	
	public function removeFlowManager($mobile)
	{
		$result = DB::connection('panasonic_connection')
				->table('anchor_smart_saver')
				->where('mobile', $mobile)->delete();
		return $result;
	}
	
	public function AddChatToFlowManager($mobile)
	{
		$log = Log::channel('wp_webhooks');
		$log->debug(__line__ . "inside AddChatToFlowManager"); 
		$exists = DB::connection('panasonic_connection')
					->table('anchor_smart_saver')
					->where('mobile', $mobile)
					->exists();
		if(!$exists){
			DB::connection('panasonic_connection')
				->table('anchor_smart_saver')
				->insert([
				'mobile' => $mobile,
				'flow_level' => 0,
				'flow_sub_level' => 0,
				//'language' => '1.1',
				'created_at' => now()
			]);
			
		}
		$flowMData = DB::connection('panasonic_connection')
				->table('anchor_smart_saver')
				->select('flow_level', 'flow_sub_level', 'language')
				->where('mobile', $mobile)
				->first();
		return $flowMData;
	}
	

	public function validateDateTime($input) {
		$log = Log::channel('wp_webhooks');
		$log->debug(__line__ . "\n input" . print_r($input, true)); 
	
		// Regular expression to validate "DD MMM YYYY - H:i" format
		// $pattern = '/^(0[1-9]|[12][0-9]|3[01])\s(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s\d{4}\s-\s([0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/';
		// $pattern = '/^([0-9]|[12][0-9]|3[01])\s(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s\d{4}\s-\s([0-9]|[01][0-9]|2[0-3]):[0-5][0-9]$/';
		$pattern = '/^([0-9]{1,2})\s(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s\d{4}\s-\s([0-9]|[01][0-9]|2[0-3]):[0-5][0-9]$/';
		

	
		// Check if the input matches the format
		if (!preg_match($pattern, $input)) {
			$log->debug(__line__ . "\n failed: invalid format");
			return "failed";
		}
		
	
		// Extract date and time components using sscanf
		list($day, $month, $year, $time) = sscanf($input, "%d %s %d - %s");
	
		// Convert the month abbreviation to a number (e.g., "Jan" -> 1)
		$months = [
			"Jan" => 1, "Feb" => 2, "Mar" => 3, "Apr" => 4,
			"May" => 5, "Jun" => 6, "Jul" => 7, "Aug" => 8,
			"Sep" => 9, "Oct" => 10, "Nov" => 11, "Dec" => 12
		];
	
		// Check if the month abbreviation is valid
		if (!isset($months[$month])) {
			$log->debug(__line__ . "\n failed: invalid month");
			return "failed";
		}
	
		$monthNumber = $months[$month];
	
		// Check if the date exists (e.g., no "30 Feb")
		if (!checkdate($monthNumber, $day, $year)) {
			$log->debug(__line__ . "\n failed: invalid date");
			return "failed";
		}
	
		$input = trim($input);
		

		// Parse the date using DateTime::createFromFormat
		$providedDateTime = DateTime::createFromFormat('d M Y - G:i', $input);

		// Check for parsing errors
		if ($providedDateTime === false) {
			$errors = \DateTime::getLastErrors();
			$log->debug(__LINE__ . "\n Failed to parse date and time: $input");
			$log->debug(__LINE__ . "\n DateTime errors: " . print_r($errors, true));
			return "failed";
		}
	
		// Check if the provided date is not in the past
		$currentDateTime = new DateTime();
	
		if ($providedDateTime < $currentDateTime) {
			$log->debug(__line__ . "\n failed: date is in the past");
			return "failed";
		}
	
		$log->debug(__line__ . " success");
		return "success";
	}
	
	

	public function convertToMySQLDateTime($input) {
		// Log for debugging
		$log = Log::channel('wp_webhooks');
		$log->debug(__LINE__ . "\n Input: " . print_r($input, true));
	
		// Parse the input using DateTime::createFromFormat
		$dateTime = \DateTime::createFromFormat('d M Y - H:i', $input);
	
		// Check if parsing was successful
		if ($dateTime === false) {
			$log->debug(__LINE__ . "\n Failed to parse date and time: $input");
			return "failed: invalid date and time format";
		}
	
		// Convert to MySQL DATETIME format
		$mysqlDateTime = $dateTime->format('Y-m-d H:i:s');
	
		$log->debug(__LINE__ . "\n Converted MySQL DATETIME: " . $mysqlDateTime);
		return $mysqlDateTime;
	}

	
	public function DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, $textMsg, $msgType = 'TEXT', $buttonData, $mediaUrl = '')
	{
		$log 		= Log::channel('wp_webhooks');
		$log->debug(__line__."\n\n ------------DIYButton ------------ \n");
		$log->debug(__line__.json_encode($buttonData));
		
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

	public function closedChat($chatID, $dispo = 'Dispo_by_system')
	{
		$result = Chats::where('id', $chatID)
		->update(['dispo' => $dispo, 'is_closed' => 2, 'closed_by' => 0, 'closed_at' => now()]);
		
		return $result ? true : false;
	}
	
	
	public function getProductCat()
	{
		return $productCat = DB::connection('panasonic_connection')
			->table('product_category')
			->select('id', 'name', 'desc')
			->whereIn('status', [0,1])
			->get();
			
		
	}
	
	public function getProductList($catID)
	{
		return $product = DB::connection('panasonic_connection')
			->table('product')
			->select('id', 'name', 'desc', 'url', 'file_type')
			->where('category', $catID)
			->whereIn('status', [0,1])
			->get();	
		
	}
	
	public function validate_mobile($mobile)
	{
		$mobile = substr($mobile, -10);
		$pattern = "/^[789]\d{9}$/";
    
		if (preg_match($pattern, $mobile)) {
			 return "success";
		} else {
			return "failed";
		}
	}
	
	function validate_pincode($pincode) {
		// Regular expression for a 6-digit PIN code
		$pattern = "/^\d{6}$/";
		
		if (preg_match($pattern, $pincode)) {
			return "success";
		} else {
			return "failed";
		}
	}
	
	public function verifyOTP($mobile,$otp){
		return $exists = DB::connection('panasonic_connection')
				->table('non_registered_user')
				->where('mobile', $mobile)
				->where(DB::raw('BINARY otp'), $otp)
				->exists();
	 
	}
	
	public function getNonCustDetails($mobile){
		return $exists = DB::connection('panasonic_connection')
				->table('non_registered_user')
				->where('mobile', $mobile)
				->where('status', 2)
				->exists();
	}
	public function updateNonRegisteredUser($mobile, $dataArr)
	{
		
		$exists = DB::connection('panasonic_connection')
					->table('non_registered_user')
					->where('mobile', $mobile)
					->exists();
		if(!$exists){
			$result = DB::connection('panasonic_connection')
				->table('non_registered_user')
				->insert([
				'mobile' => $mobile,
				'created_at' => now()
			]);
			
		}
		else if($exists && !empty($dataArr))
		{
			$result = DB::connection('panasonic_connection')
				->table('non_registered_user')
				->where('mobile', $mobile)
				->update($dataArr);
		}
		else
		{
			$result = 'exists';
		}	
		return $result;
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
			// $textMsg	= 'Enter Product Name';
			// $reqData['client_id']	= $client;
			// $reqData['campaign_id'] = $campaign;
			// $reqData['mobile'] 		= $mobile;
			// $reqData['agent_id'] 	= 0;
			// $reqData['chatID']		= $chatID;
			// $reqData['message_type']= 'text'; 
			// $reqData['event'] 		= '';	
			// $reqData['message'] 	= $textMsg;
			// $reqData['reply_id'] 	= '';
			// $reqData['mediaUrl'] 	= '';					
			// // $this->sendMsg($reqData);
			
			// $autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
			// $log->debug(__line__.'sendWPMsg Res : '.print_r($autRepMsg, true));
			// $returnArr 	= ['status' => 'initiate', 'msg' => 'Initiated'];	
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
			$log->debug(__line__.'Product Category countsd : '.$product->count());
			$header = "Product Category";
			$log->debug(__line__.'Product Category List2  : '.print_r($product, true));
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
				$reqData['message'] 	= "Please choose product category.";
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
					$log->debug(__line__."\n\n Please choose product1. --- \n");
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
				$log->debug(__line__."\n\n exploxdID------------". print_r($exploxdID, true));		
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
							// $query = DB::connection('panasonic_connection')
							// 		->table('product')
							// 		->select('id', 'category', 'name', 'desc', 'url', 'file_type')
							// 		->where('category', $catID)
							// 		->whereIn('status', [0, 1])
							// 		->limit(9);
									
							// $log->debug(__line__.'$query->toSql() '.$query->toSql());
							$product = DB::connection('panasonic_connection')
								->table('product')
								->select('id', 'category', 'name', 'desc', 'url', 'file_type')
								->where('category', $catID)
								->whereIn('status', [0,1])
								->limit(9)
								->get();
								
							$log->debug(__line__.'catID '.$catID);
							$log->debug(__line__.'Product List : '.print_r($product, true));
							if($product)
							{
								$count = $product->count();
								if($count == 1)
								{						
									$log->debug(__line__.'count1 '.$count);			
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
									$log->debug(__line__.'count2 '.$count);			

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
									$log->debug(__line__."\n\n Please choose product2. --- \n");
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
								$log->debug(__line__."\n\n Please choose product3. --- \n");
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
			$textMsg = 'Please choose below option for further process.';
			$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $textMsg, 'TEXT', $options);
		}
		return $returnArr;		
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
		$optionIDArr = []; $key = '';
		if(!empty($optionID))
		{
			$optionIDArr = explode('-',$optionID);
			$key = isset($optionIDArr[2]) ? $optionIDArr[2] : '';
		}
		$crudDetails['optionID'] = $optionIDArr;
		
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

			case "Catalogue":
				$searchInput = ($searchInput != 'Catalogue') ? $searchInput : '';
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
				$returnData =  $this->reqCallBack($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
				break;

			case "Retailer Flow":
				$searchInput = ($searchInput != 'Retailer Flow') ? $searchInput : '';
				$returnData =  $this->sendPredefinedTemplate($key, $crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
				break;

			case "Channel Partner Flow":
				$searchInput = ($searchInput != 'Channel Partner Flow') ? $searchInput : '';
				$returnData =  $this->sendPredefinedTemplate($key, $crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
				break;
				
			case "Help and Support":
				$searchInput = ($searchInput != 'Help and Support') ? $searchInput : '';
				$returnData =  $this->helpSupport($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
				break;

			case "View Rewards":
				$searchInput = ($searchInput != 'View Rewards') ? $searchInput : '';
				$returnData =  $this->assignToExpert($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);			
				break;

			case "FAQs":
				$searchInput = ($searchInput != 'FAQs') ? $searchInput : '';
				$returnData =  $this->faqs($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
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
			$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, 'Please select the option for further process. ', 'TEXT', $options);

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
	
		
	public function productCatalog1($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = '')
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
			$textMsg = 'Please choose below option for further process.';
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
			
			$msgRes = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);	
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
					->limit(20)
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
						if($k <= 18){
							$log->debug(__line__."\n\n values ------------ \n". json_encode($values));
							$textMsg .= "\n\n\nSr No  : ".++$k. " \nName : ".$values->name. " \nNumber:".$values->number. " \nAddress:".$values->address. " \nDistrict:".$values->district. " \nPlace:".$values->city;
						}
					}
					if(!empty($textMsg) && $count > 19)
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
					->limit(20)
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
						if($k <= 18){
							$textMsg .= "\n\n\nSr No  : ".++$k. " \nName : ".$values->name. " \nNumber:".$values->number. " \nAddress:".$values->address. " \nDistrict:".$values->district. " \nPlace:".$values->city;
						}
					}
					$log->debug(__line__."\n Step5");
					if(!empty($textMsg) && $count > 19)
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
			->update(['assigning_flag' => 2, 'req_assigning_at' => date("Y-m-d H:i:s")]);					
		
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
			
			if($campaign == $this->hyperlocalCampaign)
			{
				ApiTraits::getConsent($client, $campaign, $mobile, $logSource = 'wp_webhooks', 'OPT_IN', $crudDetails, $name = '');
			
				Chats::where('id', $chatID)
				->where('client_id', $client)
				->where('campaign_id', $campaign)
				->where('is_closed', 1)
				->where('cust_unique_id', $mobile)
				->update(['assigning_flag' => 2, 'req_assigning_at' => date("Y-m-d H:i:s")]);	

				$returnArr = ['status' => 'success', 'msg' => 'success'];
			}
			else{
				
				
				$returnArr = ['status' => 'failed', 'msg' => 'failed'];
			}
			
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
		$campaign_data = $this->getcrud($waNumber);
		$crudDetails = $campaign_data->wp_crud;
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
		$log->debug(__line__.'Received WhatsApp DLR Data : ' . print_r($data, true));
		 
		$response = isset($data['response']) ? ((is_array($data['response'])) ? $data['response'] : json_decode($data['response'], true)) : [];
		
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
	
	function validateMobile($mobile) {
		$pattern = '/^\+91\d{10}$/';
		
		// Check if the mobile number matches the pattern
		return preg_match($pattern, $mobile);
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

	public function helpLineNo($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = '')
	{
		$log 		= Log::channel('wp_webhooks');
		$log->debug(__line__."-------- Welcome to  helpLineNo Function -----");
		
		$log->debug(__line__."crudDetails : ". print_r($crudDetails, true));
		
		if(isset($crudDetails['optionID'][1]) && $crudDetails['optionID'][1] == 'RF')
		{
			$this->assignToExpert($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $input);
		}
		else
		{		
		
			$msg = 'Helpline No. +9136088606';
			$log->debug(__line__." msg Length : ". strlen($msg));
			if(strlen($msg) > 1020)
			{
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
				
				$msg = 'Please choose below option for further process.';
				sleep(3);
			}
			
			$options =['mm'=>'Main menu', 'end'=>'End session'];	
			$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $msg, 'TEXT', $options);
		}
			
		return $returnArr 	= ['status' => 'success', 'msg' => 'success'];
	}

	public function reqCallBack($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = '')
	{
		$log 		= Log::channel('wp_webhooks');
		$log->debug(__line__."-------- Welcome to  reqCallBack Function -----");
		
		$log->debug(__line__."crudDetails : ". print_r($crudDetails, true));
		
		
		if(isset($crudDetails['optionID'][1]) && $crudDetails['optionID'][1] == 'RF')
		{
			$msg = 'Helpline No. +918657749002';
		}
		else
		{
			$msg = 'Helpline No. +919136088606';
		}
		
		$log->debug(__line__." msg Length : ". strlen($msg));
		if(strlen($msg) > 1020)
		{					
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
			$msg = 'Please choose below option for further process.';
		}
		
		$options =['mm'=>'Main menu', 'end'=>'End session'];
		$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $msg, 'TEXT', $options);
			
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
				
				//https://edas-webapi.edas.tech/vaaniSMDev/panasonic_brochure/ElectricianFlow/SKU/WCTrewards.jpg 
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
		$log 		= Log::channel('wp_webhooks');
		$log->debug(__line__."-------- Welcome to  programInformation Function -----");
		
		$log->debug(__line__."crudDetails : ". print_r($crudDetails, true));
		$start_time = microtime(true);
		
		$reqData['client_id']	= $client;
		$reqData['campaign_id'] = $campaign;
		$reqData['mobile'] 		= $mobile;
		$reqData['agent_id'] 	= 0;
		$reqData['chatID']		= $chatID;
		$reqData['message_type']= 'text'; 
		$reqData['event'] 		= '';
		$reqData['reply_id'] 	= '';
		$reqData['mediaUrl'] 	= '';
		
		$msg = 'Be a part of Anchor Smart Saver, an electrician loyalty program and earn rewards on purchasing Anchor Products.

			To avail the benefit, follow three simple steps.
			1) Register and share your details.
			2) Scan the QR code.
			3) Redeem the cash collected.

			Watch the video to get more information and register today!! 
			https://www.youtube.com/watch?v=arCtggX0MZg&t=16s
			';
		
		if(isset($crudDetails['optionID'][1]) && $crudDetails['optionID'][1] == 'RF')
		{
			$msg = 'A fast & convenient way for Authorised retailers to earn rewards points, redeem exciting gifts!

			In the continuous process of finding new avenues of making a strong & valuable relationship with our esteemed retailers, Anchor by Panasonic is presenting the Samridhi Loyalty Program.

			An Loyalty Program App with a user-friendly interface designed to delight retailers who prefer instant access to their membership account with just a click. The Samridhi App allows all Authorised retailers to earn rewards points by purchasing Anchor by Panasonic power products! Once the points are added to their account, they can redeem numerous exciting gifts & rewards in exchange for the rewards points. Isn’t it amazing?
			
			This free to download App is particularly developed to increase the level of accessibility and engagement of our Authorised Retailers. Also, to seamlessly connect and interact with Retailers, making it a valuable tool for the organization Anchor by Panasonic. To access all its features, you need to log in with your registered mobile number and OTP. After logging in to the App, you will find all the program-related information.
			
			Top Features:

			Using the App features a retailer can:

			• Check their Point Balance,
			• Check their purchases according to Invoice,
			• Earn Rewards Points,
			• Redeem Exciting Gifts,
			• Check Account Statement,
			• Log Queries,
			• Call Sales Team directly, and many more.
			
			Our app is designed to let you focus on your rewards & benefits. We hope you will find Anchor Samridhi mobile App useful, innovative, and informative.

			Bring home the choicest of rewards!

			For more information, kindly refer to our “Anchor Samridhi” program video- https://www.youtube.com/watch?v=WLR5cKIR25E&list=PL9V7vaHxQ0Evqwiy4ZGi2OExc_NyqR5CQ&pp=iAQB';
			/* $reqData['message']	= $msg;
			
			$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
			
			$end_time = microtime(true);
			$log->debug(__line__."Start Time : ". $start_time);
			$log->debug(__line__."End Time : ". $end_time);
			$log->debug(__line__."Delay Time : ". ($end_time - $start_time));
			sleep(3);	
			$msg = 'Please choose below option for further process.';
			$options =['mm'=>'Main menu', 'end'=>'End session'];	
			$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $msg, 'TEXT', $options); */
		}
		else{
			//$msg = 'Please choose below option for further process.';
			$options =['mm'=>'Main menu', 'end'=>'End session'];	
			$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $msg, 'TEXT', $options);
		}
		$log->debug(__line__." msg Length : ". strlen($msg));
		if(strlen($msg) > 1020)
		{
			$reqData['client_id']	= $client;
			$reqData['campaign_id'] = $campaign;
			$reqData['mobile'] 		= $mobile;
			$reqData['agent_id'] 	= 0;
			$reqData['chatID']		= $chatID;
			$reqData['message_type']= 'text'; 
			$reqData['event'] 		= '';
			$reqData['reply_id'] 	= '';
			$reqData['mediaUrl'] 	= '';
			// $log->debug(__line__."Program Information : ". print_r($autRepMsg, true));
			$reqData['message'] 	= $msg;
			$this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
			sleep(2);	
			$reqData['message'] 	= '🕐 Plesae wait, Processing.......';
			$this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
			sleep(9);	
			$msg = 'Please choose below option for further process.';
		}
		$options =['mm'=>'Main menu', 'end'=>'End session'];	
		$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $msg, 'TEXT', $options);
		
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
				
				//https://edas-webapi.edas.tech/vaaniSMDev/panasonic_brochure/ElectricianFlow/SKU/WCTrewards.jpg 
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
	
	public function training($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = '')
	{
		$msg = 'Watch the video to get more information and register today!! 
				https://www.youtube.com/watch?v=arCtggX0MZg&t=16s
				';
				
		$log->debug(__line__." msg Length : ". strlen($msg));
		if(strlen($msg) > 1020)
		{
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
			$msg = 'Please choose below option for further process.';
		}
		
		$options =['mm'=>'Main menu', 'end'=>'End session'];	
		$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $msg, 'TEXT', $options);
		
		return $returnArr 	= ['status' => 'success', 'msg' => 'success'];
	}

	public function sendPredefinedTemplate($templateID, $crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = '')
	{
		$log = Log::channel('wp_webhooks');	
		$log->debug(__LINE__.'------------Welcome to sendPredefinedTemplate function-------------');		
		$autRepMsg = [];		
		$templates = DB::table('templates')
			->select('id','caption', 'media_url', 'name', 'footer', 'list', 'msg_type', 'intractive_type')
			->where('id', $templateID)
			->whereIn('status', [0,1])
			->first();	
			
		$log->debug(__LINE__.'templates ID  : ' . $templateID);	
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
			$reqData['templateID']	= $templateID;
			
			$autRepMsg = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);										
		}
		else{
			$log->debug(__LINE__.' No templates Data  Found: ' . print_r($templates, true));			
		}
	}

	public function faqs($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = '')
	{
		$log 		= Log::channel('wp_webhooks');
		$log->debug(__line__."-------- Welcome to  faqs Function -----");
		
		$log->debug(__line__."crudDetails : ". print_r($crudDetails, true));
		
		if(isset($crudDetails['optionID'][1]) && $crudDetails['optionID'][1] == 'RF')
		{
			$this->assignToExpert($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $input);
		}
			
		return $returnArr 	= ['status' => 'success', 'msg' => 'success'];
		
	}

	public function helpSupport($crudDetails, $client, $campaign, $mobile, $chatID, $optionID = '', $input = '')
	{
		$log 		= Log::channel('wp_webhooks');
		$log->debug(__line__."-------- Welcome to helpSupport Function -----");
		
		$log->debug(__line__."crudDetails : ". print_r($crudDetails, true));
		$msg = '';
		
		if(isset($crudDetails['optionID'][1]) && $crudDetails['optionID'][1] == 'RF')
		{
			$msg = '
			•	Hindi		https://youtu.be/WLR5cKIR25E?si=I70idkbQ13079co4

			•	Marathi 	 https://youtu.be/uE-uIm1l8-s?si=Jv0jGXR1F8cDJaOL

			•	Assamese	 https://youtu.be/A0BFrsTli9o?si=zvr4VR6ux9Vx_xLs

			•	Bengali   	 https://youtu.be/H_5_ZDXr0u8?si=O6C8yj6hf7opX_Ed

			•	Gujarati 	 https://youtu.be/ffqPZLaNRx0?si=oOzK3cEz7cfo4yF2

			•	Kannada	 	https://youtu.be/nYO-Mm8R_wo?si=aADX3MuXr2dLA1m0

			•	Malayalam	 https://youtu.be/6UwIQE3rlfA?si=_MWcz-JHRwa47dk9

			•	Odia       	 https://youtu.be/FLqE7d8zPJc?si=tUt1xhnNYeLZulQ0

			•	Punjabi 	https://youtu.be/plwdS4vjgpY?si=CZpgJhx-KpVebOQk

			•	Tamil      	 https://youtu.be/JUrhXccReGc?si=WkH8KpnnZluhOoUU

			•	Telugu    	 https://youtu.be/hvVAMS5jdLs?si=uQkxU-w0kRLL4Bfy ';
		}
		
		$log->debug(__line__." msg Length : ". strlen($msg));
		if(strlen($msg) > 1020)
		{
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
			$msg = 'Please choose below option for further process.';
		}
		
		$options =['mm'=>'Main menu', 'end'=>'End session'];
		$this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, $msg, 'TEXT', $options);
		
			
		return $returnArr 	= ['status' => 'success', 'msg' => 'success'];
	}
	/* 
	public function getNotsentChats($client_id, $campaign_id, $chatID = '')
	{
		//$this->globalTimestamp;
		$chats = DB::table('chat_log')
			->select('chat_log.id', 'chat_log.timestamp')
			->leftJoin('chats', 'chats.id', '=', 'chat_log.chat_id')
			->whereIn('chats.status', [0,1])
			->where('chats.is_closed', 1)
			->where('chats.assigning_flag', 1)
			->where('chat_log.in_out', 2)
			->where('chat_log.is_sent', 1)
			->where('chat_log.created_at', '>', now()->subSeconds(30))
			->get();
		
	}
 */

	
	//Marked expire for hyperlocal flow if the chat is not being assigned withing a minut.
	public function markExpire()
	{		
		$log 		= Log::channel('cron_activity');
		$log->debug(__line__."-------- Welcome to markExpire Function -----");
		
		$carbonTime 	= Carbon::createFromTimestamp(time());
		$formattedTime 	= $carbonTime->format('Hi');
		$query = DB::table('chats')
			->leftJoin('campaign', 'campaign.id', '=', 'chats.campaign_id')
            ->select('chats.id','chats.client_id','chats.campaign_id','chats.cust_unique_id','campaign.wp_number','campaign.wp_crud','chats.customer_name', DB::raw('TIMESTAMPDIFF(SECOND, chats.req_assigning_at, NOW()) AS time_difference_in_seconds'))
            ->whereIn('chats.status', [0,1,2])
            ->where('chats.assigning_flag', 2)
            ->whereNull('chats.assigned_to')
            ->where('chats.is_closed', 1)
			->where('chats.req_assigning_at', '>', now()->subSeconds(60));
			 
             // ->where('campaign.call_window_from', '<', $formattedTime)
             // ->where('campaign.call_window_to', '>', $formattedTime)
		$chat_data = $query->get();
        //$chat_data_array = json_decode(json_encode($chat_data), true);
		$log->debug(__line__."chat_data : ". print_r($chat_data, true));
		if(!empty($chat_data))
		{
			foreach($chat_data as $k => $value){	
				$reqData['client_id']	= $value->client_id;
				$reqData['campaign_id'] = $value->campaign_id;
				$reqData['mobile'] 		= $value->cust_unique_id;
				$reqData['agent_id'] 	= 0;
				$reqData['chatID']		= $value->id;
				$reqData['message_type']= 'text'; 
				$reqData['event'] 		= '';
				$reqData['message'] 	= "Our agents are currently busy assisting other customers. If immediate assistance is required, please contact our support center. They are prepared to promptly help you with any urgent matters. 👇
				
You can reach them at -
•	Customer Service Number: 📞 02241304130
•	Electrician Program Support Number: 📞 9136088606
•	Retailer Program Support Number: 📞 8657749002
•	Sales Enquiry: 📞 02261406140

Thank you for contacting Anchor by Panasonic 😊.";
				$reqData['reply_id'] 	= '';
				$reqData['mediaUrl'] 	= '';					

			
				$crudDetails['wp_number'] 	= $value->wp_number;
				$crudDetails['wp_crud'] 	= $value->wp_crud;
				$log->debug(__line__."rtgerjtitrjuyoiruyouyoi : ");
				// $res = $this->sendWPMsg($payload, false, 'wp_webhooks', $campData);
				$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
				
				sleep(10);

				$options =['mm'=>'Main menu', 'end'=>'End session'];	
				$log->debug(__line__."options : ". print_r($options, true));
				$this->DIYButton($crudDetails, $value->client_id, $value->campaign_id, $value->cust_unique_id, $value->id, 'What would you like to do next?', 'TEXT', $options);

				// $options =['callback-'.$value->id=>'Call Back ? ', 'mm'=>'Main menu', 'end'=>'End session'];	
				// $options =['step1yes'=>'YES', 'step1no'=>'NO'];	
				// $result = $this->diyReplyButton($crudDetails, $value->client_id, $value->campaign_id, $value->cust_unique_id, $value->id, 'Is there anything else I can assist you with?', 'TEXT', $options);
				
				// $msgID = '';
				// if(!empty($result['msg']))
				// {
				// 	$msgArr = explode('-', $result['msg']);
				// 	$msgID = $msgArr[1];
				// }
				
				// $this->addPromptMsg($value->campaign_id, $value->id, 'We have not received any input, please let us know, is there anything else I can assist you with?','REPLY_BUTTON', ['step1yes'=>'YES', 'step1no'=>'NO'], $msgID, 1, 1,1);
			}
		}
	}


	//Prometing Msg.
	public function promptMsg()
	{		
		$log 		= Log::channel('cron_activity');
		$log->debug(__line__."-------- Welcome to promptMsg Function -----");
		
		$carbonTime 	= Carbon::createFromTimestamp(time());
		$formattedTime 	= $carbonTime->format('Hi');
		// $oneMinuteAgo = Carbon::now()->subMinutes(1);
		$query = DB::table('prompt_msg')
            ->select('prompt_msg.id','prompt_msg.chat_id','prompt_msg.msg','prompt_msg.msg_type','prompt_msg.option','prompt_msg.executed_on','prompt_msg.max_attempt', 'prompt_msg.attempt', 'prompt_msg.msg_id', 'prompt_msg.is_end', 'prompt_msg.route', 'prompt_msg.created_at')
            ->whereIn('prompt_msg.status', [0,1]);
			// ->where('prompt_msg.created_at', '<', $oneMinuteAgo)
			// ->where('prompt_msg.created_at', '>', now()->subSeconds(60));
		$prompt_data = $query->get();
        //$chat_data_array = json_decode(json_encode($chat_data), true);
		$log->debug(__line__."prompt_data : ". print_r($prompt_data, true));
		
		// echo "prompt_data<pre>"; print_r($prompt_data);
		if(!empty($prompt_data))
		{
			$chatIDArr = $chat_log_arr = $chat_data_arr = array();
			foreach($prompt_data as $k => $value){	
				$chatIDArr[] = $value->chat_id;
			}
			$chat_data = DB::table('chats')
            ->leftJoin('campaign', 'campaign.id', '=', 'chats.campaign_id')
            ->select('chats.id', 'chats.client_id','chats.campaign_id', 'chats.cust_unique_id', 'chats.customer_name', 'campaign.wp_number', 'campaign.wp_crud')
            ->whereIn('chats.status', [0,1])
            ->whereIn('chats.id', $chatIDArr)
            ->where('chats.campaign_id', $this->hyperlocalCampaign) // Hyperlocal only
			->get();
			// echo "chat_data<pre>"; print_r($chat_data);
			foreach($chat_data as $k => $value){	
				$chat_data_arr[$value->id] = $value;
			}
			
			 $currentTimestamp = time();
			foreach($prompt_data as $k => $value){
				if(array_key_exists($value->chat_id, $chat_data_arr))
				{						
					$createdAtTimestamp = strtotime($value->created_at); // Convert created_at to UNIX timestamp
					$timeDifferenceSeconds = $currentTimestamp - $createdAtTimestamp;
					if($value->executed_on <= $timeDifferenceSeconds)
					{
						$chatID = $value->chat_id;
						$crudDetails['wp_number'] 	= $chat_data_arr[$chatID]->wp_number;
						$crudDetails['wp_crud'] 	= $chat_data_arr[$chatID]->wp_crud;
						
						$client_id = $chat_data_arr[$chatID]->client_id;;
						$campaign_id = $chat_data_arr[$chatID]->campaign_id;;
						$cust_unique_id = $chat_data_arr[$chatID]->cust_unique_id;
						$msg = $value->msg;
						$option = $value->option;
						$is_end = $value->is_end;
						$max_attempt = $value->max_attempt;
						$attempt = $value->attempt;
						
						if($value->msg_type == 'REPLY_BUTTON')
						{
							$options = json_decode($option, true);	
							$result = $this->diyReplyButton($crudDetails, $client_id, $campaign_id, $cust_unique_id, $chatID, $msg, 'TEXT', $options);
							// echo "<pre>"; print_r($options);
							// echo "<pre>"; print_r($result);
							$this->addPromptMsg($campaign_id, $chatID, 'If you have more questions or need help later, please get in touch. We are here to assist you! 
							🙏 Thank you for contacting Anchor by Panasonic','TEXT', [], '', 2, 2,1);///question is asked from here pooja
								
								//If you have more questions or need help later, please get in touch. We are here to assist you! Thank you for contacting Anchor by Panasonic
						}
						else if($value->msg_type == 'TEXT')
						{
							$reqData['client_id']	= $client_id;
							$reqData['campaign_id'] = $campaign_id;
							$reqData['mobile'] 		= $cust_unique_id;
							$reqData['agent_id'] 	= 0;
							$reqData['chatID']		= $chatID;
							$reqData['message_type']= 'text'; 
							$reqData['event'] 		= '';
							$reqData['message'] 	= $msg;
							$reqData['reply_id'] 	= '';
							$reqData['mediaUrl'] 	= '';				
							
							$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
							// print_r($autRepMsg);
							
							if($attempt != $max_attempt)
							{
								$this->addPromptMsg($campaign_id, $chatID, 'It appears we have not received your query yet. Please provide us with more details so we can assist you better.','TEXT', [], '', 1, 2,1);
							}
							else{
								$this->addPromptMsg($campaign_id, $chatID, "If you have more questions or need help later, please get in touch. We are here to assist you! 
								🙏 Thank you for contacting Anchor by Panasonic $attempt - $max_attempt",'TEXT', [], '', 2, 2,1);
							}
							
							if($is_end == 2)
							{							
								$this->deletePromptMsg($campaign_id, $chatID);
								$this->closedChat($client_id, $campaign_id, $chatID, $cust_unique_id, 'No_Response_by_System');
							}
						}
					}
				}
			}
		}
	}
	
	
	public function getPromptMsgData($campaign, $chatID, $type = ['TEXT', 'REPLY_BUTTON'])
	{
		if($campaign == $this->hyperlocalCampaign)
		{
			$prompt_data = DB::table('prompt_msg')
				->select('prompt_msg.id','prompt_msg.chat_id','prompt_msg.msg','prompt_msg.msg_type','prompt_msg.option','prompt_msg.executed_on','prompt_msg.max_attempt', 'prompt_msg.attempt', 'prompt_msg.msg_id', 'prompt_msg.is_end', 'prompt_msg.route')
				->whereIn('prompt_msg.status', [0,1])
				->whereIn('prompt_msg.msg_type', $type)
				->where('prompt_msg.chat_id', $chatID)->first();
			return $prompt_data;
		}
	}
	
	public function deletePromptMsg($campaign, $chatID)
	{
		if($campaign == $this->hyperlocalCampaign)
		{
			DB::table('prompt_msg')->where('chat_id', $chatID)->delete();
		}
	}
	
	public function deleteIfExists($campaign, $chatID, $type = [])
	{
		if($campaign == $this->hyperlocalCampaign)
		{
			$count = DB::table('prompt_msg')
				->select('prompt_msg.id')
				->whereIn('prompt_msg.status', [0,1])
				->whereIn('prompt_msg.msg_type', $type)
				->where('prompt_msg.chat_id', $chatID)
				->count();
			if($count > 0)
			{
				DB::table('prompt_msg')->where('chat_id', $chatID)->delete();
			}
		}
	}
	
	
	public function addPromptMsg($campaign, $chatID, $msg, $msgType = 'REPLY_BUTTON', $option = [], $msgID = '', $isEnd = 1, $attempt = 1, $route = 1, $executedOn = 60){
		if($campaign == $this->hyperlocalCampaign)
		{
			$this->deletePromptMsg($campaign, $chatID);	
			
			$option = json_encode($option);
			
			DB::table('prompt_msg')->insert([
				'chat_id' => $chatID,
				'msg' => $msg,
				'msg_type' => $msgType,
				'option' => $option,
				'max_attempt' => 2,
				'attempt' => $attempt,
				'is_end' => $isEnd,
				'route' => $route,
				'executed_on' => $executedOn,
			]);
		}
	}

	public function addLog($request, $mobile='', $response){
	
		$api_panasonic_log = new Panasonic_Api_Log;
		$api_panasonic_log->mobile_number = $mobile;
		$api_panasonic_log->request = json_encode($request);
		$api_panasonic_log->response = json_encode($response);

		
		$api_panasonic_log->save();
	}

	public function getToken($url){
		$log = Log::channel('wp_webhooks');
		$log->debug(__line__.__FUNCTION__);
		$payload = array(
			"key" => "androide2d47qaxbkg8cgcok0kow404g0kc4cs",
		);	

		$headers = array(
			'api-version: 3',
			'version: 2.8.0',
			'platform: android',
			'lang: en',
			'tz: Asia/Kolkata',
			'app-name: Anchor'
		);

		$res 	= ApiTraits::curlHit($url, $payload, 'POST',$headers);
		$log->debug(__line__."REsult". print_r($res, true));
		$responseData = json_decode($res['response'], true);

		//update the token
		DB::table('panasonic_token')->update(['token'=> $responseData['respData']['token'], 'updated_at'=>now()]);

		return $responseData['respData']['token'];

	}

	public function getUserisRegistered($token, $mobile,$url){
		$log = Log::channel('wp_webhooks');
		$log->debug(__line__.__FUNCTION__);
		$payload = array(
			"mobile" => "%2B".$mobile,
		);	

		$headers = array(
			'api-version: 3',
			'version: 2.8.0',
			'platform: android',
			'lang: en',
			'tz: Asia/kolkata',
			'app-name: Anchor',
			"Authorization: Bearer $token"
		);

		$res 	= ApiTraits::curlHit($url, $payload, 'GET',$headers);
		$log->debug(__line__."REsult=>". print_r($res, true));
		$responseData = json_decode($res['response'], true);

		$this->addLog($payload, $mobile,$res);

		return $responseData['body']['registered'] ? true : false;
	}

	public function getProfileDetails($url, $mobile, $token){
		$log = Log::channel('wp_webhooks');
		$log->debug(__line__.__FUNCTION__);
		$payload = array(
			"mobile" => "%2B".$mobile,
		);	


		$headers = array(
			'api-version: 3',
			'version: 2.8.0',
			'platform: android',
			'lang: en',
			'tz: Asia/kolkata',
			'app-name: Anchor',
			"Authorization: Bearer $token"
		);
		$res 	= ApiTraits::curlHit($url, $payload, 'GET',$headers);
		$log->debug(__line__."REsult". print_r($res, true));
		$responseData = json_decode($res['response'], true);
		$this->addLog($payload, $mobile,$res);
		array_pop($responseData['body']);
		return $responseData['body'];
	}

	public function checkBalancePoints($url, $mobile , $token){
		$log = Log::channel('wp_webhooks');
		$log->debug(__line__.'-------checkBalancePoints------');
		$payload = array(
			"mobile" => "%2B".$mobile,
		);	 

		// $log->debug(__line__.print_r($payload,true));

		$headers = array(
			'api-version: 3',
			'version: 2.8.0',
			'platform: android',
			'lang: en',
			'tz: Asia/kolkata',
			'app-name: Anchor',
			"Authorization: Bearer $token"
		);
		$res 	= ApiTraits::curlHit($url, $payload, 'GET',$headers);
		$log->debug(__line__."REsult". print_r($res, true));
		$responseData = json_decode($res['response'], true);

		$this->addLog($payload, $mobile,$res);
		return $responseData['body'];
	}

	public function club_benefits($url, $mobile , $token){
		$log = Log::channel('wp_webhooks');
		$log->debug(__line__.'-------clubBenefits------');
		$payload = array(
			"mobile" => "%2B".$mobile,
		);	

		$headers = array(
			'api-version: 3',
			'version: 2.8.0',
			'platform: android',
			'lang: en',
			'tz: Asia/kolkata',
			'app-name: Anchor',
			"Authorization: Bearer $token"
		);
		$res 	= ApiTraits::curlHit($url, $payload, 'GET',$headers);
		$log->debug(__line__."REsult". print_r($res, true));
		$responseData = json_decode($res['response'], true);
		$this->addLog($payload, $mobile,$res);
		return $responseData['body'];
	}
}
