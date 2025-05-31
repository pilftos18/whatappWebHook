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


class GupshupGenericWebhookController extends Controller
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
	public $hyperlocalCampaign = 37;
	public $whatsappcrud = array();
	
	public function __construct()
    {
		// $log = Log::channel('webhook_generic');
		// $log->debug(__line__."\n\n ---- Start handleWebhook - for at : ". date("l jS \of F Y h:i:s A"));
		
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
		$log = Log::channel('webhook_generic');
		$log->debug(__line__."\n\n ---- Start handleWebhook - for ". $uniqueID ." at : ". date("l jS \of F Y h:i:s A"));
		// $data = $request->all();
		$data = $request->json()->all();
		$log->debug('Received WhatsApp Webhook Data : ' . $request);
		$log->debug(__line__.'Webhook Row Data Test : ' . print_r($data, true));
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
		//$log->debug(__line__.'Webhook Row Data Test2 : ' . print_r($data, true));
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
			if(empty($chatID) && $type == 'text')
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

			if($newChat === true)
			{
				// $this->deletePromptMsg($campaign, $chatID);
				
				Chats::where('id', $chatID)
				->where('client_id', $client)
				->where('campaign_id', $campaign)
				->where('is_closed', 1)
				->where('cust_unique_id', $mobile)
				->update(['assigning_flag' => 1]);	
				
				$log->debug(__LINE__.' MM-M: ' . $opID);
				$templates = DB::table('templates')
						->select('id','caption', 'media_url', 'name', 'footer', 'list', 'msg_type', 'intractive_type')
						->where('id', $a_reply_id)
						->whereIn('status', [0,1])
						->first();	

				$log->debug(__LINE__.'a_reply_id  : ' . $a_reply_id);	
				$log->debug(__LINE__.'templates Data  : ' . print_r($templates, true));	
				
				if($templates)
				{
					$caption 	= 'Hi '.ucfirst($name).',

'.$templates->caption;
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
					
					//$log->debug(__line__.'reqData : ' . print_r($reqData, true));
					// $autRepMsg = $this->sendTemplate($reqData);
					$autRepMsg = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);
					$log->debug(__LINE__.'response autRepMsg : ' . print_r($autRepMsg, true));
				}
		
			}
				
			
			
			$endArr = ['end', 'step1no'];
			 
			if((($chatDetails['assigned_to'] == null || empty($chatDetails['assigned_to'])) && strtolower($text) == 'end' && $chatDetails['is_closed'] == '1') || in_array($opID, $endArr))
			{
				$message = 	"Thank you 🙏, LiveChat session has been ended.";
				$reqData['client_id']	= $client;
				$reqData['campaign_id'] = $campaign;
				$reqData['mobile'] 		= $mobile;
				$reqData['agent_id'] 	= 0;
				$reqData['chatID']		= $chatID;
				$reqData['message_type']= 'text'; 
				$reqData['event'] 		= '';
				$reqData['message'] 	= $message;
				$reqData['reply_id'] 	= '';
				$reqData['mediaUrl'] 	= '';					
				// $autRepMsg = $this->sendMsg($reqData);
				$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
				$log->debug(__LINE__.'sendWPMsg Res : '. print_r($autRepMsg, true));
				$this->closedChat($client, $campaign, $chatID, $mobile);
				$log->debug(__LINE__.'End the chat ID : '.$client);				
			} 
			/* else if($chatDetails['assigning_flag'] == 1){			
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
						
						$caption 	= 'Hi '.ucfirst($name).',

'.$templates->caption;
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
					
					// $this->updateSetting($client, $campaign, $chatID, ['lsto' => '']);
				}
			}
			 */
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
	
	/* public function interactiveReply($crudDetails, $option, $client, $campaign, $mobile, $chatID, $optionID, $searchInput = '')
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
			case "Talk to our experts":
				$searchInput = ($searchInput != 'Talk to our experts') ? $searchInput : '';
				$returnData =  $this->assignToExpert($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
				break;

			case "End session":
				$searchInput = ($searchInput != 'End session') ? $searchInput : '';
				$returnData =  $this->endChat($crudDetails, $client, $campaign, $mobile, $chatID, $optionID, $searchInput);				
				break;
			default:
				return ['status' => 'failed', 'msg' => 'Invalid Response'];
				// Handle unknown message type or provide an error message
				break;
		}
	}
	 */
	
	public function endChat($crudDetails, $client, $campaign, $mobile, $chatID, $isOption = false, $input = '')
	{
		if($campaign == $this->hyperlocalCampaign){
			$message = 	"If you have more questions or need help later, please get in touch. We are here to assist you! Thank you for contacting Anchor by Panasonic. 🙏";
		}
		else{
			$message = 	"Thank you 🙏, LiveChat session has been ended.";
		}
		
		$returnArr 	= ['status' => '', 'msg' => ''];
		$reqData['client_id']	= $client;
		$reqData['campaign_id'] = $campaign;
		$reqData['mobile'] 		= $mobile;
		$reqData['agent_id'] 	= 0;
		$reqData['chatID']		= $chatID;
		$reqData['message_type']= 'text'; 
		$reqData['event'] 		= '';
		$reqData['message'] 	= $message;
		$reqData['reply_id'] 	= '';
		$reqData['mediaUrl'] 	= '';					
		// $autRepMsg = $this->sendMsg($reqData);
		$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
		
		$this->closedChat($client, $campaign, $chatID, $mobile);
		return $returnArr = ['status' => 'success', 'msg' => 'success'];
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
				->update(['assigning_flag' => 2]);	 //, 'req_assigning_at' => date("Y-m-d H:i:s")

				$returnArr = ['status' => 'success', 'msg' => 'success'];
			}
			else{
				
				
				$returnArr = ['status' => 'failed', 'msg' => 'failed'];
			}
			
		}
		return $returnArr;
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
	


	public function closedChat($client, $campaign,  $chatID,  $mobile, $dispo = 'Dispo_by_system')
	{
		$result = Chats::where('id', $chatID)
		->update(['dispo' => $dispo, 'is_closed' => 2, 'closed_by' => 0, 'closed_at' => now()]);
		
		return $result ? true : false;
	}
	
	
}
