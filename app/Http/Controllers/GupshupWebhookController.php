<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
// use League\Flysystem\Util;
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


class GupshupWebhookController extends Controller
{
	use ApiTraits;
	use CommonTraits;
	
	public $url = 'https://mediaapi.smsgupshup.com/GatewayAPI/rest';
	public $wp_number = '';
	public $hsm_userid = '';
	public $hsm_password = '';
	public $twoway_userid = '';
	public $twoway_password = '';
	public $auth_scheme	= 'plain';
	public $version	= '1.1';
	public $data_encoding = 'Unicode_text';
	public $globalTimestamp = '';
	
	public function __construct()
    {
		$this->url           	= Config::get('custom.gupshup.whatsapp.send-api.url');
		$this->wp_number		= Config::get('custom.gupshup.whatsapp.send-api.wp_number');
		$this->hsm_userid		= Config::get('custom.gupshup.whatsapp.send-api.hsm-userid');
		$this->hsm_password		= Config::get('custom.gupshup.whatsapp.send-api.hsm-password');
		$this->twoway_userid	= Config::get('custom.gupshup.whatsapp.send-api.twoway-userid');
		$this->twoway_password	= Config::get('custom.gupshup.whatsapp.send-api.twoway-password');
		$this->globalTimestamp	= round(microtime(true) * 1000);
	}
	//Request $request
    public function handleWebhook(Request $request)
    {		
		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '2048M');
		$log = Log::channel('wp_webhooks');
		$log->debug("\n\n--------------------Start Here------------------------------------\n");
		$data = $request->all();
		$log->debug('Received WhatsApp Webhook Data : ' . $request);
		$log->debug('Received WhatsApp Webhook json_encode : ' . json_encode($data));
			// echo "<pre>"; print_r($request->input);die;
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
		
		// $currentTime = time();
		// Validate the incoming request, ensuring it comes from Gupshup
		if (!($mobile && $type && $timestamp && $waNumber)) {
			$log->debug('Error : Invalid Data Received');			
            return response()->json(['error' => 'Invalid Data Received'], 400);
        }
		
		try {
			
			$campaign_data = DB::table('campaign')
                ->select('campaign.id', 'campaign.name', 'campaign.client_id', 'campaign.auto_reply_id', 'campaign.allocation_type', 'campaign.call_window_from', 'campaign.call_window_to', 'campaign.working_days', 'campaign.holiday_start', 'campaign.holiday_end', 'campaign.holiday_name')
                ->whereIn('campaign.status', [0,1])
                ->where('campaign.wp_number', $waNumber)
                ->get()
                ->first();
				
			$log->debug('Qeury campaign_data : ' . json_encode($campaign_data));
			
			$client 	= isset($campaign_data->client_id) ? $campaign_data->client_id : '1001';				
			$campaign 	= isset($campaign_data->id) ? $campaign_data->id : '2001';
			$alloc_type	= isset($campaign_data->allocation_type) ? $campaign_data->allocation_type : '';
			$a_reply_id	= isset($campaign_data->auto_reply_id) ? $campaign_data->auto_reply_id : '';
			$call_window_from= isset($campaign_data->call_window_from) ? $campaign_data->call_window_from : '';
			$call_window_to	= isset($campaign_data->call_window_to) ? $campaign_data->call_window_to : '';
			$working_days	= (isset($campaign_data->working_days) && !empty($campaign_data->working_days)) ? explode(',',$campaign_data->working_days) : [];
			$holiday_start	= isset($campaign_data->holiday_start) ? $campaign_data->holiday_start : '';
			$holiday_end	= isset($campaign_data->holiday_end) ? $campaign_data->holiday_end : '';
			$holiday_name	= isset($campaign_data->holiday_name) ? $campaign_data->holiday_name : '';
			$campaignName	= isset($campaign_data->name) ? $campaign_data->name : '';

			// Generate Message ID if not present
			$message_id = isset($data['messageId']) ? $data['messageId'] : CommonTraits::uuid();
			$reply_id 	= isset($data['replyId']) ? $data['replyId'] : "";
			$optinFlag = false;
			//$reply_messageId 	= isset($data['messageId']) ? $data['messageId'] : "";
			
			$log->debug('One : ');
			
			$chat_data = DB::table('chats')
                ->select('chats.id')
                ->whereIn('chats.status', [0,1])
                ->where('chats.org_no', $waNumber)
                ->where('chats.cust_unique_id', $mobile) 
				->whereIn('chats.is_closed', [0,1])
                ->get()
                ->first();			
			$log->debug('Qeury chats : ' . json_encode($chat_data));
			if($chat_data)
			{
				$newChat = false;
				$chatID = $chat_data->id;
			}
			else{				
				$newChat 	= true;
				$chat_data = new Chats();
				$chat_data->unique_id = $uniqueUuid;
				$chat_data->client_id = $client;
				$chat_data->campaign_id = $campaign;
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
			
			
			
			//echo "<pre>".$timestamp; print_r($campaign_data);die;
			
			// Handle the event based on its type
			if ($eventType === 'message') {	
				$log->debug('Two : ');
				if (preg_match('/image|document|voice|audio|video/', $type)) 
				{					
						$media  	= (is_array($data[$type])) ? $data[$type] : json_decode($data[$type], true);
						$mediaUrl 	= $media['url']. $media['signature'];
						$text		= isset($media['caption']) ? $media['caption'] : '';
						$log->debug('Three : ');
						// Generate a unique filename or use the original filename
						$originalFilename 	= pathinfo($mediaUrl, PATHINFO_BASENAME);
						$uniqueFilename 	= strtoupper(substr($type, 0, 3)). $message_id. ".". $this->mime2ext($media['mime_type']);
						$storagePath 		= "{$client}/{$campaign}/received/{$mobile}/{$type}";
						$destinationFilePath= "{$storagePath}/{$uniqueFilename}";
						// Check if the folder structure exists; if not, create it
						try {
							if (!Storage::disk('public')->exists($storagePath)) {
								Storage::disk('public')->makeDirectory($storagePath);
								$log->debug('Dir Created : ' . $storagePath);
							}else{
								$log->debug('Dir exits :'. $storagePath);
							}
						} catch (\Exception $e) {
							$log->debug('Error creating directory : ' . $e->getMessage());
						}
					
						// Download the file
						$response = Http::get($mediaUrl);					
						$log->debug('Download file response  : ' . json_encode($response));

						// Check if the download was successful
						if ($response->successful()) {
							$publicPath = Storage::disk('public')->put($destinationFilePath, $response->body());
							$url 		= request()->root();
							$parsedUrl 	= parse_url($url);
							$baseUrl 	= $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
							//storepath and download path both are different
							// $filePath = storage_path("app/public/uploads/rcbulk/{$storagePath}/{$uniqueFilename}");
							 $publicUrl = $baseUrl."/public/storage/{$storagePath}/{$uniqueFilename}";
						
							$log->debug('Media received and stored: ' . $publicPath);
							
						} else {
							$log->debug('Media failed to received and stored: ' . $publicUrl);
						}					
				}
				else if($type == 'text') {
					$text = $data['text'];
					
					if(strtolower($text) == 'end')
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
						$autRepMsg = $this->sendMsg($reqData);
						
						$result = Chats::where('id', $chatID)
							->where('client_id', $client)
							->where('campaign_id', $campaign)
							->where('is_closed', 1)
							->where('cust_unique_id', $mobile)
							->update(['is_closed' => 2, 'closed_by' => 0, 'closed_at' => now()]);
							
						$log->debug('End the chat ID : '.$client);
						
					}
				}
				else if($type == 'interactive') {	
					$interactiveData = json_decode($data['interactive'], true);
					$log->debug('Error : '. $data['interactive']);
					// $text = $interactiveData['button_reply']['title'];
					$text = $interactiveData[$interactiveData['type']]['title'];
					$intrIdArr = explode('-', $interactiveData[$interactiveData['type']]['id']);
					$templateID = $intrIdArr[0];
					// unknow type
					
					if($text == 'Kwon More' || $text == 'connect to representative' || $text == 'Proceed')
					{
						$this->getConsent($mobile, 'OPT_IN', $name);
						$optinFlag = true;
					}
					
					if($text == 'Not Intrested')
					{
						$this->getConsent($mobile, 'OPT_OUT', $name);
					}
				}
				
				//$log->debug('End : ');
			}
			
			// Insert new chat  into chat_log table
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
			
			$log->debug('Add new record in Chat_log for ID : '.$chat_log_data->id);
			
			// Create a Carbon instance from the timestamp
			$carbonTime 	= Carbon::createFromTimestamp(time());
			$currentTime 	= $carbonTime->format('Hi');			
			$dayShortName 	= Carbon::now()->shortEnglishDayOfWeek;
			$today 			= date('Ymd');
			$log->debug('\n dayShortName : '.$dayShortName);
			$log->debug('\n working_days : '.$campaign_data->working_days);
			$log->debug('\n working_days : '.json_encode($working_days));
			
			if($today >= date('Ymd', strtotime($holiday_start)) && $today <= date('Ymd', strtotime($holiday_end)))
			{
				$dateofReturn = date('Y-m-d', strtotime($holiday_end . ' +1 day'));
				$parsedFromTime = Carbon::createFromFormat('Hi', $campaign_data->call_window_from)->format('h:i A');
				$parsedToTime = Carbon::createFromFormat('Hi', $campaign_data->call_window_to)->format('h:i A');
				
				$message = "Hello, and thank you for reaching out to " . strtoupper($campaignName) . ".\n";
				$message .= "We hope this message finds you well. Currently, we are not operating on the occasion of " . strtoupper($holiday_name) . ".\n";
				$message .= "Please note that our chat support is temporarily unavailable during this time. We will be back and ready to assist you on " . $dateofReturn . ".";				
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
				$autRepMsg = $this->sendMsg($reqData);
				
				Chats::where('id', $chatID)
				->where('client_id', $client)
				->where('campaign_id', $campaign)
				->where('is_closed', 1)
				->where('cust_unique_id', $mobile)
				->update(['is_closed' => 2, 'closed_by' => 0, 'closed_at' => now()]);
				
			}
			else if($currentTime < $campaign_data->call_window_from || $currentTime > $campaign_data->call_window_to || !in_array(strtoupper($dayShortName), $working_days))
			{
				$parsedFromTime = Carbon::createFromFormat('Hi', $campaign_data->call_window_from)->format('h:i A');
				$parsedToTime = Carbon::createFromFormat('Hi', $campaign_data->call_window_to)->format('h:i A');				
				$reqData['client_id']	= $client;
				$reqData['campaign_id'] = $campaign;
				$reqData['mobile'] 		= $mobile;
				$reqData['agent_id'] 	= 0;
				$reqData['chatID']		= $chatID;
				$reqData['message_type']= 'text'; 
				$reqData['event'] 		= '';
				// $reqData['message'] 	= 'Please connect us back in our working window, between '.$parsedFromTime. ' and '.$parsedToTime;
				$reqData['message'] 	= 'Please connect us back in our oprational hours, which are from '.$parsedFromTime. ' to '.$parsedToTime.' on '.$campaign_data->working_days.'.';
				$reqData['reply_id'] 	= '';
				$reqData['mediaUrl'] 	= '';					
				$autRepMsg = $this->sendMsg($reqData);
				
				Chats::where('id', $chatID)
				->where('client_id', $client)
				->where('campaign_id', $campaign)
				->where('is_closed', 1)
				->where('cust_unique_id', $mobile)
				->update(['is_closed' => 2, 'closed_by' => 0, 'closed_at' => now()]);
				
			}
			else{
				
				if($optinFlag === true)
				{					
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
					$autRepMsg = $this->sendMsg($reqData);
				}
			
				//Auto Reply Functionality		
				if($newChat === true)
				{	
					$autRepMsg = [];		
					$templates = DB::table('templates')
						->select('id','caption', 'media_url', 'name', 'footer', 'list', 'msg_type', 'intractive_type')
						->where('client_id', $client)
						->where('campaign_id', $campaign)
						->where('id', $a_reply_id)
						->whereIn('status', [0,1])
						->get()
						->first();			
					if($templates)
					{
						$caption 	= $templates->caption;
						$media_url 	= $templates->media_url;
						$name 		= $templates->name;
						$footer 	= $templates->footer;
						$list 		= $templates->list;
						$msg_type 	= $templates->msg_type;
						$intractive_type 	= $templates->intractive_type;
						
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
						$reqData['templateType'] = $msg_type;	
						$reqData['action'] 		= $list;	
						$reqData['interactive_type']= $intractive_type;	
						$reqData['footer'] 		= $footer;
						$reqData['templateID'] 		= $a_reply_id;
						
						$log->debug('reqData : ' . json_encode($reqData));
						$autRepMsg = $this->sendTemplate($reqData);					
						
					}
					$log->debug('Auto Reply Msg : ' . json_encode($autRepMsg));
				}				
				
			}
			
			
			// Log API response
			$api_log = new api_log();
			$api_log->client_id = $client;
			$api_log->campaign_id = $campaign;
			$api_log->ref_table = 'chat_log';
			$api_log->ref_id = $chat_log_data->id;
			$api_log->type = 'WEBHOOK';
			$api_log->cust_no = $mobile;
			$api_log->org_no = $waNumber;
			$api_log->response_data = json_encode($data);
			$api_log->save();
			
			$log->debug('Add new record in api_log for ID : '.$api_log->id);
			
         } catch (\Exception $e) {
            // Log any exceptions or errors
			$log->debug('Error - An error occurred: ' . $e->getMessage());
           // return response()->json(['error' => 'An error occurred'], 500);
        } 
		$log->debug('Webhook received and processed');
		
		$log->debug('\n-----------------------------------------End Webhook---------------------------------------\n');
		
		//return response()->json(['status' => 200, 'msg'=>'Webhook received and processed']);
    }
	
	
	
	
	// to call an api to send the msg call below function 
	public function sendNewMessage(Request $request)
	{
		
		$data = $request->all();
		$reqData['client_id']= $request->input('client_id');
		$reqData['campaign_id'] = $request->input('campaign_id');
		$reqData['mobile'] 	= $request->input('mobile');
		$reqData['agent_id'] 	= $request->input('agent_id');
		$reqData['chatID']	= $request->input('chatID');
		$reqData['message_type'] 	= $request->input('message_type'); 
		$reqData['event'] 		= $request->input('event');
		$reqData['message'] 	= $request->input('message');
		$reqData['reply_id'] 	= $request->input('reply_id');
		$reqData['mediaUrl'] 	= $request->input('mediaUrl');
		
		$result = $this->sendMsg($reqData);
		
		// echo "<pre>"; print_r($response);
		// echo "<pre>"; print_r($chat_log_data);die;
	   
		/* if(strtoupper($message) == "STOP")
		{
			// send_optout_api($sender_id, $agent_id);
			$optin_template = 'optout';
			sendtemplate($sender_id, $agent_id, $optin_template);
		} elseif(strtoupper($message) == "UNSTOP")
		{
			updatenumber($sender_id, $wa_approve, $agent_id);
		}
		if (!$result) {
			echo json_encode(['msg'=>"Message not received",'status'=>"201"]) ;
		}    
		echo json_encode(['msg'=>"Success",'status'=>"200"]) ; */
		
		return json_encode($result);
	}

	// to send msg using the internal function you can use this function
	public function sendMsg($postData, $isTemplate = false){
		
		$log = Log::channel('wp_send');
		$log->debug("\n\n--------------------Start Here------------------------------------\n");
		$log->debug('Input Data : ' . json_encode($postData));		
		
		$result 	= ['msg'=>'Initiated msg','status'=> 'failed', 'status_code'=>'101', 'data' => []];
		$timestamp 	= $this->globalTimestamp;
		$clientID 	= $postData['client_id'];
		$campaignID = $postData['campaign_id'];
		$sendTo 	= $postData['mobile'];
		$agent_id 	= isset($postData['agent_id']) ? $postData['agent_id'] : '';
		$chatID 	= isset($postData['chatID']) ? $postData['chatID'] : '';
		$messageId 	= CommonTraits::uuid();
		$msg_type 	= isset($postData['message_type']) ? $postData['message_type'] : 'text'; 
		$event 		= isset($postData['event']) ? $postData['event'] : '';
		$message 	= isset($postData['message']) ? $postData['message'] : '';
		$reply_id 	= isset($postData['reply_id']) ? $postData['reply_id'] : '';
		$mediaUrl 	= isset($postData['mediaUrl']) ? $postData['mediaUrl'] : '';
		$method 	= (strtolower($msg_type) == 'text') ? 'SendMessage' : 'SendMediaMessage';
		$templateID = 0; $data = [];
		// $mediaCaption= $postData['mediaCaption'];
		// $isExisting	= $postData['isExisting'];
		
		if(!empty($sendTo) && !empty($chatID) && !empty($msg_type) && (!empty($message) || !empty($mediaUrl)))
		{
			if($this->isChatExist($chatID) === false)
			{
				$result = ['msg'=>'Invalid Chat ID','status'=> 'failed', 'status_code'=>'400', 'data' => ['chatID' => $chatID]];
				$log->debug('Error :'. json_encode($result));
				return $result;
			}
			
			if($this->isClientExist($clientID) === false)
			{
				$result = ['msg'=>'Invalid Client ID','status'=> 'failed', 'status_code'=>'400', 'data' => ['clientID' => $clientID]];
				$log->debug('Error :'. json_encode($result));
				return $result;
			}
			
			
			if($this->isCampaingExist($campaignID) === false)
			{
				$result = ['msg'=>'Invalid Campaing ID','status'=> 'failed', 'status_code'=>'400', 'data' => ['campaingID' => $campaignID]];
				$log->debug('Error :'. json_encode($result));
				return $result;
			}
			
			$methodFlag = false;		
			
			if($isTemplate === false)
			{		
				$payload = array(
					"format" => "json",
					"userid" => $this->twoway_userid, //2000200387',//
					"password" => $this->twoway_password, //'tuC3vd#z',
					"auth_scheme" => $this->auth_scheme,//"plain",
					"v" => $this->version,//"1.1",
					"send_to" => $sendTo
				);		
				
				if (strtolower($msg_type) == "text") {
					$methodFlag = true;
					$payload = array_merge($payload, array(
						"method" => $method,
						"msg" => $message,
						"msg_id" => $messageId,
						"msg_type" => strtolower($msg_type),
						"data_encoding" => $this->data_encoding//"Unicode_text",
					));
				} elseif (preg_match('/image|document|voice|audio|video|application/', strtolower($msg_type))) {
					
					$methodFlag = true;
					$payload = array_merge($payload, array(
						"method" => $method,
						"msg" => $message,
						"msg_id" => $messageId,
						"msg_type" => strtolower($msg_type),
						"media_url" => $mediaUrl,
						"isHSM" => "false",
						"caption" => $message,
						"data_encoding" => $this->data_encoding//"Unicode_text",
					));
				}
			}
			else{
				
				/* if(strtoupper($templateType) == 'REPLY_BUTTON')
				{
					$payload = array(
						"userid" => $this->twoway_userid, //2000200387',//
						"password" => $this->twoway_password, //'tuC3vd#z',
						"auth_scheme" => $this->auth_scheme,//"plain",
						"v" => $this->version,//"1.1",
						"send_to" => $sendTo
					);	
					
					if(!empty($mediaUrl))
					{
						$payload = array_merge($payload, array(
							"method" => "SendMediaMessage",
							"msg_type" => "IMAGE",
							"caption" => $message,
							"action" => $action,
							"interactive_type" => $interactive_type,
							"footer" => $footer,
							"media_url" => $media_url,
							"template_id" => $template_id,
							"msg_id" => $messageId
						));
						$methodFlag = true;
					}
					else{
						$payload = array_merge($payload, array(
							"method" => "SendMediaMessage",
							"msg_type" => "IMAGE",
							"caption" => $message,
							"action" => $action,
							"interactive_type" => $interactive_type,
							"footer" => $footer,
							"media_url" => $media_url,
							"template_id" => $template_id,
							"msg_id" => $messageId
						));
						$methodFlag = true;
					}
				} */
			}
			
			if($methodFlag === true)
			{				
				$res 		= ApiTraits::curlHit($this->url, $payload, 'GET');
				$response 	= json_decode($res['response'], true);
				$log->debug('URL : ' . $this->url);
				$log->debug('Payload : ' . json_encode($payload));
				$log->debug('Gupshup API response : ' . json_encode($response));
				
				
				// echo "payload <pre>"; print_r($payload);
				// echo "<pre>"; print_r($response['response']);die;
				// Insert new chat  into chat_log table
				$chat_log_data = new Chat_log();
				$chat_log_data->chat_id = $chatID;
				$chat_log_data->timestamp = $timestamp;
				$chat_log_data->message_id = $messageId;
				$chat_log_data->in_out = 2;
				$chat_log_data->message_type = CommonTraits::MessageType($msg_type);
				$chat_log_data->event = '';
				$chat_log_data->message = $message;
				$chat_log_data->reply_id = $reply_id;
				$chat_log_data->media_path = $mediaUrl;
				$chat_log_data->created_by = $agent_id;
				$chat_log_data->is_delivered = 1;
				$chat_log_data->is_read = 1;
				$chat_log_data->is_deleted = 1;
				$chat_log_data->template_id = $templateID;
				$chat_log_data->save();
				
				$log->debug('Insert Chat_log ID : '. $chat_log_data->id);
				
				// Log API response
				$api_log = new api_log();
				$api_log->client_id = $clientID;
				$api_log->campaign_id = $campaignID;
				$api_log->ref_table = 'chat_log';
				$api_log->ref_id = $chat_log_data->id;
				$api_log->type = 'API';
				$api_log->cust_no = $sendTo;
				$api_log->org_no = $this->wp_number;
				$api_log->response_data = json_encode($response);
				$api_log->save();
								
				$result = ['msg'=>'success','status'=> 'success', 'status_code'=>'200', 'data' => $response];
				$log->debug('Insert api_log ID : '. $api_log->id);
				$log->debug('Internal response data : '. json_encode($result));
			}
			else{
				
				$result = ['msg'=>'Incomplete payload message or media missing','status'=> 'failed', 'status_code'=>'400', 'data' => $postData];
				$log->debug('Error :'. json_encode($result));
			}
		}
		else{
			
			$result = ['msg'=>'Missing Required paramenters','status'=> 'failed', 'status_code'=>'400', 'data' => $postData];
			$log->debug('Error : '. json_encode($result));
		}
		$log->debug('---------------------------------- End API ------------------------------------------------');
		return $result;
	}
	
	
	
	public function sendTemplate($postData)
	{
		$log = Log::channel('wp_send');
		$log->debug("\n\n--------------------Start Here------------------------------------\n");
		$log->debug('Input Data : ' . json_encode($postData));		
		
		$result 	= ['msg'=>'Initiated msg','status'=> 'failed', 'status_code'=>'101', 'data' => []];
		$timestamp 	= $this->globalTimestamp;
		$clientID 	= $postData['client_id'];
		$campaignID = $postData['campaign_id'];
		$sendTo 	= $postData['mobile'];
		$agent_id 	= isset($postData['agent_id']) ? $postData['agent_id'] : '';
		$chatID 	= isset($postData['chatID']) ? $postData['chatID'] : '';
		$messageId 	= CommonTraits::uuid();
		$msg_type 	= isset($postData['message_type']) ? $postData['message_type'] : 'text'; 
		$msg_type 	= strtoupper($msg_type); 
		$event 		= isset($postData['event']) ? $postData['event'] : '';
		$message 	= isset($postData['message']) ? $postData['message'] : '';
		$reply_id 	= isset($postData['reply_id']) ? $postData['reply_id'] : '';
		$mediaUrl 	= isset($postData['mediaUrl']) ? $postData['mediaUrl'] : '';
		$tempType 	= isset($postData['templateType']) ? $postData['templateType'] : 'REPLY_BUTTON';
		$action 	= isset($postData['action']) ? $postData['action'] : '{}';
		$intType 	= isset($postData['interactive_type']) ? $postData['interactive_type'] : '';
		$footer 	= isset($postData['footer']) ? $postData['footer'] : '';
		$method 	= ($msg_type == 'TEXT') ? 'SendMessage' : 'SendMediaMessage';		
		$templateID = isset($postData['templateID']) ? $postData['templateID'] : '';
		$data = '';
		//&& !empty($chatID) 
		
		if(!empty($sendTo) && !empty($tempType) && !empty($action) && !empty($intType) && !empty($msg_type) && (!empty($message) || !empty($mediaUrl)))
		{
			$methodFlag = false;
			if(strtoupper($tempType) == 'REPLY_BUTTON' || strtoupper($tempType) == 'LIST')
			{
				$payload = array(
					"userid" => $this->twoway_userid, //2000200387',//
					"password" => $this->twoway_password, //'tuC3vd#z',
					"auth_scheme" => $this->auth_scheme,//"plain",
					"v" => $this->version,//"1.1",
					"send_to" => $sendTo
				);	
				
				if(!empty($mediaUrl) && $msg_type == 'IMAGE')
				{
					$payload = array_merge($payload, array(
						"method" => $method,
						"msg_type" => $msg_type,
						"caption" => $message,
						"action" => $action,
						"interactive_type" => $intType,
						"footer" => $footer,
						"media_url" => $mediaUrl,
						"msg_id" => $messageId
					));
					$methodFlag = true;
				}
				else{
					$payload = array_merge($payload, array(
						"method" => $method,
						"msg_type" => $msg_type,
						"msg" => $message,
						"action" => $action,
						"interactive_type" => $intType,
						"footer" => $footer,
						"msg_id" => $messageId
					));
					$methodFlag = true;
				}
			}
			
			if($methodFlag === true)
			{				
				$res 		= ApiTraits::curlHit($this->url, $payload, 'GET');
				// echo "payload <pre>"; print_r($res);die;
				$response 	= json_decode($res['response'], true);
				$log->debug('URL : ' . $this->url);
				$log->debug('Payload : ' . json_encode($payload));
				$log->debug('Gupshup API response : ' . $response);
				
				
				 //
				// echo "<pre>"; print_r($response['response']);die;
				// Insert new chat  into chat_log table
				$chat_log_data = new Chat_log();
				$chat_log_data->chat_id = $chatID;
				$chat_log_data->timestamp = $timestamp;
				$chat_log_data->message_id = $messageId;
				$chat_log_data->in_out = 2;
				$chat_log_data->message_type = CommonTraits::MessageType($msg_type);
				$chat_log_data->event = '';
				$chat_log_data->message = $message;
				$chat_log_data->reply_id = $reply_id;
				$chat_log_data->media_path = $mediaUrl;
				$chat_log_data->created_by = $agent_id;
				$chat_log_data->is_delivered = 1;
				$chat_log_data->is_read = 1;
				$chat_log_data->is_deleted = 1;				
				$chat_log_data->template_id = $templateID;
				$chat_log_data->save();
				
				$log->debug('Insert Chat_log ID : '. $chat_log_data->id);
				
				// Log API response
				$api_log = new api_log();
				$api_log->client_id = $clientID;
				$api_log->campaign_id = $campaignID;
				$api_log->ref_table = 'chat_log';
				$api_log->ref_id = $chat_log_data->id;
				$api_log->type = 'API';
				$api_log->cust_no = $sendTo;
				$api_log->org_no = $this->wp_number;
				$api_log->response_data = $res['response'];
				$api_log->save();
								
				$result = ['msg'=>'success','status'=> 'success', 'status_code'=>'200', 'data' => $response];
				$log->debug('Insert api_log ID : '. $api_log->id);
				$log->debug('Internal response data : '. json_encode($result));
			}
			else{
				
				$result = ['msg'=>'Incomplete payload message or media missing','status'=> 'failed', 'status_code'=>'400', 'data' => $postData];
				$log->debug('Error :'. json_encode($result));
			}
		}
		else{
			
			$result = ['msg'=>'Missing Required paramenters','status'=> 'failed', 'status_code'=>'400', 'data' => $postData];
			$log->debug('Error : '. json_encode($result));
		}
		$log->debug('---------------------------------- End API ------------------------------------------------');
		return $result;
		
	}

	// public function updatestatus($messageid, $eventTs, $eventType, $destAddr, $cause)
	public function updateDLR(Request $request)
	{
		$log = Log::channel('wp_dlr');
		$log->debug("\n\n--------------------Start Here for DLR------------------------------------\n");		
		$data = $request->all();
		$log->debug('Received WhatsApp DLR Data : ' . $request);
		
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
					$log->debug('Status for update : ' . json_encode($conArr));
					
					$externalID = explode('-', $data['externalId']);
					$messageID = $externalID[1];
					
					$query = DB::table('chat_log')
					->where('message_id', $messageID)
					->update($conArr);
					
					$log->debug('Update DLR Status : ' . $query);
				}
				else{
					$log->debug('Empty Data for update');
				}
			}
		}
		else{
			$log->debug('Empty Response');
		}
		
		$log->debug("\n\n--------------------End DLR------------------------------------\n");		
	}

	public function getConsent($phone, $type = 'OPT_IN', $name = ''){
		
		$log = Log::channel('wp_send');
		$log->debug("\n\n--------------------Start getOptIN Here------------------------------------\n");
		
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
		$log->debug('URL : ' . $this->url);
		$log->debug('Payload : ' . json_encode($payload));
		$log->debug('Gupshup API response : ' . json_encode($response));
		
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
		
		$log->debug("\n\n--------------------End  getOptIN Here------------------------------------\n");
		
		return $returnArr;
	}

	
	public function getBulkData()
	{
		$custom_log = Log::channel('wp_send');
		$custom_log->debug("\n\n--------------------Start Here------------------------------------\n");
			
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
				$custom_log->debug('Qeury chats : ' . json_encode($chat_data));
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
								
				$result = $this->sendTemplate($postData);
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
		$data = DB::table('chats')
			->select('chats.id')
			->whereIn('chats.status', [0,1])
			->where('chats.id', $id)
			->whereIn('chats.is_closed', [0,1])
			->first();
		if($data)
		{
			return true;
		}
		return false;
	}
	
	public function isClientExist($id)
	{		
		$data = DB::table('clients')
			->select('id')
			->whereIn('status', [0,1])
			->where('id', $id)
			->first();
		if($data)
		{
			return true;
		}
		return false;
	}
	
	public function isCampaingExist($id)
	{		
		$data = DB::table('campaign')
			->select('id')
			->whereIn('status', [0,1])
			->where('id', $id)
			->first();
		if($data)
		{
			return true;
		}
		return false;
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

}
