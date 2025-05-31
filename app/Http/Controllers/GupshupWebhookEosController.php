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
use DateTime;

class GupshupWebhookEosController extends Controller
{
    use ApiTraits;
	use CommonTraits;
	// use PanasonicTraits;
	
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
	public $hyperlocalCampaign = 46;
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
    public function handleEosWebhook(Request $request)
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

		if (!($mobile && $type && $timestamp && $waNumber)) {
			$log->debug(__line__.'Error : Invalid Data Received');			
            return response()->json(['error' => 'Invalid Data Received'], 400);
        }

        try {
            $campaign_data = $this->getcrud($waNumber);
				
			// $log->debug(__LINE__.'Query campaign_data : ' . print_r($campaign_data, true));
			
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
				}else if($type == 'button'){
                    $button_data = json_decode($data['button'],true);	
                    $log->debug(__LINE__.'text --- : '.print_r($button_data,true));		

                    $text = $button_data['text'];
                    $log->debug(__LINE__.'text --- : '.$text);		
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

			$this->deletePromptMsg($campaign, $chatID);	
			
			$log->debug(__LINE__.' opID : ' . $opID);	

			$log->debug(__LINE__.'Chat Details : ' . print_r($chatDetails, true));			
			
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
			$log->debug(__LINE__.'textCopy:'.$textCopy);
			
			$isChatEnd = $this->getChatisEnd($mobile);
			$log->debug(__LINE__.' isChatEnd : ' . $isChatEnd);	
			if($isChatEnd == 2){
				$reqData['message']= "Your details has been saved to our system, \nWe will contact you shortly!";
				$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);	
				$this->closedChat($chatID);
				exit;
			}
			
			//Flow_leve & language management option
			$flowMData = $this->AddChatToFlowManager($mobile);	

            if($textCopy == "Not Interested"){

                $reqData['message']= "Thank you for your time. Don't hesitate to contact us at career@eosglobe.com if you change your mind and want to join us in the future.";
                $autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);	
                $log->debug(__LINE__.'Not Interested');		
				// sleep(1);
				// $reqData['message']= 'Thank you, LiveChat session has been ended';
				// $autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
				// $this->closedChat($chatID);	
				// $this->updateUserDetails($mobile, ['ischatend'=> 2]);

				// $flow_level = 0;
				// $text = '';
				// $this->updateFlowLevel($flow_level, 0, $mobile);
				// $flowMData = $this->getFlowManager($mobile);
				exit;
            }else if($textCopy == "Interested"){
                $log->debug(__LINE__.'Interested');			
				$flow_level = 1;
				$text = '';
				$this->updateFlowLevel($flow_level, 1.1, $mobile);
				$flowMData = $this->getFlowManager($mobile);
            }
			

			$flow_level = $flowMData->flow_level;
			$flow_sub_level = $flowMData->flow_sub_level;
			$language = $flowMData->language;
			$next_level = ($flow_level + 1);
			$pre_level = ($flow_level > 0) ? ($flow_level - 1) : $flow_level;
			
			$log->debug(__LINE__.'Flow Level : ' . $flow_level);
			$msgID = '';
			switch($flow_level){
				case '1';
					$reqData['message']= "Are you currently employed and looking for a change? ";
					$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);	
					$log->debug(__LINE__.'autRepMsg: ' . $autRepMsg['status']);
					// if($autRepMsg['status'] == 'success'){
					// 	$isEnd =2;
					// }
					// $log->debug(__LINE__.'msgID: ' .$msgID);
					$this->updateFlowLevel($next_level, 2, $mobile);
					sleep(2);
					$options =['2.1'=>'Yes', '2.2'=>'No'];	
					$result = $this->diyReplyButton($crudDetails, $client, $campaign, $mobile, $chatID, '', 'TEXT', $options);
					$log->debug(__LINE__.'result: ' . print_r($result,true));
					
					// if(!empty($result['msg']))
					// {
					// 	$msgArr = explode('-', $result['msg']);
					// 	$msgID = $msgArr[1];
					// }
					
					break;
				case '2';
					if(strtolower($textCopy) == 'yes'){
						
						$listArr=["sections" => [],"button" => 'Select you CTC'];
						$listArr["sections"][] = ["rows" => array(array(
							"id" => '2.1.1',
							"title" => 'Below 13,000'
						), array(
							"id" => '2.1.2',
							"title" => '13,000 - 17,000'
						), array(
							"id" => '2.1.3',
							"title" => '17,000 - 21,000'
						), array(
							"id" => '2.1.4',
							"title" => 'Above 21,000'
						))];	
						$reqData['message']= "Please mention your current Monthly CTC (Overall):";				
						$list = json_encode($listArr, JSON_PRETTY_PRINT);
						$reqData['templateType']= 'LIST';	
						$reqData['action'] 		= $list;
						$reqData['templateID'] 	= 0;	
						$reqData['interactive_type']= 'list';
						$log->debug(__LINE__.'Payload: =>'.print_r($reqData,true));		
						$autRepMsg = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);
						$log->debug(__LINE__.'autRepMsg: ' . $autRepMsg['status']);
						// if($autRepMsg['status'] == 'success'){
						// 	$isEnd = 2;
						// }
					    // $log->debug(__LINE__.'msgID: ' .$msgID);
						$this->updateFlowLevel($next_level, 3, $mobile);
						$flowMData = $this->getFlowManager($mobile);

					}else if(strtolower($textCopy) == 'no'){
						$reqData['message']= "Thank you for your time. Don't hesitate to contact us at career@eosglobe.com if you change your mind and want to join us in the future.";
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);	
						$log->debug(__LINE__.'Not Interested');		
						// $this->updateUserDetails($mobile, ['ischatend'=> 2]);
						// sleep(1);
						// $reqData['message']= 'Thank you, LiveChat session has been ended';
						// $autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
						// $this->closedChat($chatID);	
		
						$isChatEnd =2;
						// $flow_level = 0;
						// $text = '';
						// $this->updateFlowLevel($flow_level, 0, $mobile);
						// $flowMData = $this->getFlowManager($mobile);
					}else{
						$reqData['message']= "Invalid response, please choose the right option...";
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
					}

					break;
				case '3':
					$this->updateUserDetails($mobile, ['monthly_ctc' => $textCopy]);
					if(in_array($opID,['2.1.1','2.1.2','2.1.3','2.1.4'])){
						$listArr=["sections" => [],"button" => 'Select any option'];
						$listArr["sections"][] = ["rows" => array(array(
							"id" => '3.1.1',
							"title" => 'Immediately'
						), array(
							"id" => '3.1.2',
							"title" => 'Within 7 days'
						), array(
							"id" => '3.1.3',
							"title" => 'Within 15 days'
						), array(
							"id" => '3.1.4',
							"title" => 'After 15 days'
						))];	
						$reqData['message']= "When can you join?";				
						$list = json_encode($listArr, JSON_PRETTY_PRINT);
						$reqData['templateType']= 'LIST';	
						$reqData['action'] 		= $list;
						$reqData['templateID'] 	= 0;	
						$reqData['interactive_type']= 'list';
						$log->debug(__LINE__.'Payload: =>'.print_r($reqData,true));		
						$autRepMsg = $this->sendWPMsg($reqData, true,'wp_webhooks', $crudDetails);
						$this->updateFlowLevel($next_level, 4, $mobile);
						$flowMData = $this->getFlowManager($mobile);
					}else{
						$reqData['message']= "Invalid response, please choose the right option...";
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
					}

					break;
				case 4:
					if(in_array($opID,['3.1.1','3.1.2','3.1.3','3.1.4'])){
						$this->updateUserDetails($mobile, ['joining_days' => $textCopy]);
						$reqData['message']= "Please specify the languages you can speak apart from Hindi & Marathi:";
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);	
						$this->updateFlowLevel($next_level, 4.1, $mobile);
					}else{
						$reqData['message']= "Invalid response, please choose the right option...";
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
					}

					break;
				case 5:
					$this->updateUserDetails($mobile, ['languages_known' => $textCopy]);
					$reqData['message']= "Please mention your preferred date and time(in 24 hours format like 12 Nov 2024 - 10:30) to connect with our Human Resource Recruiter:";
					$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);	
					$this->updateFlowLevel($next_level, 5.1, $mobile);

					break;
				case 6:
					$log->debug(__LINE__.'textCopy: =>'.$textCopy);		
					$result = $this->validateDateTime($textCopy);	
					$log->debug(__LINE__.'validateDateTime: =>'.print_r($result,true));	

					if($result == "failed"){
						$reqData['message']= 'Please enter a valid datetime';
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
					}else{
						$conv_datetime = $this->convertToMySQLDateTime($textCopy);
						$log->debug(__LINE__.'converted datetime: =>'.print_r($conv_datetime,true));

						$this->updateUserDetails($mobile, ['preferred_time_to_talk' => $conv_datetime,'ischatend'=> 2]);
						$reqData['message']= "Thank you for your interest! One of our Human Resource Recruiters will get in touch with you soon!";
						$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);	
						$flow_level = 0;
						$text = '';
						$isChatEnd =2;
						$this->updateFlowLevel($flow_level, 0, $mobile);
						$flowMData = $this->getFlowManager($mobile);
						$this->closedChat($chatID);
					}
					
					break;
				default:
					$reqData['message']= "Invalid request, please choose the right option...";
					$autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
					// sleep(2);
					// $options =['mm'=>'Main menu', 'end'=>'End session'];	
					// $this->DIYButton($crudDetails, $client, $campaign, $mobile, $chatID, 'What would you like to do next?', 'TEXT', $options);
					break;
			}

			// $msgID = '';
			// $log->debug(__LINE__.' $isEnd' .  $isEnd);
			if($isChatEnd == 1){
				$this->addPromptMsg($campaign, $chatID, 'We have not received any input from you!','TEXT', '', $msgID, 1, 1,1);
			}
			
        
        } catch (\Exception $e) {
            // Log any exceptions or errors
            $log->debug(__LINE__.'Error - An error occurred: ' . $e->getMessage());
        } 
    }

	public function closedChat($chatID, $dispo = 'Dispo_by_system')
	{
		$result = Chats::where('id', $chatID)
		->update(['dispo' => $dispo, 'is_closed' => 2, 'closed_by' => 0, 'closed_at' => now()]);
		
		return $result ? true : false;
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
			$query = DB::connection('dev_eosglobe')
				->table('user_flow_settings')
				->where('mobile', $mobile)
				->update($updateData);	
		}
	}


	public function validateTiming($timing){
		// Regular expression for HH:MM in 24-hour format
		// $pattern = '/^(?:[01]\d|2[0-3]):[0-5]\d$/';
		// $pattern = '/^(?:[01]\d|2[0-3]):[0-5]\d$/';
		$pattern = '/^(?:[0-9]|[01][0-9]|2[0-3]):[0-5][0-9]$/';

		// Validate the input time against the pattern
		// return preg_match($pattern, $timing) === 1;

		if (preg_match($pattern, $timing)) {
			return "success";
		} else {
			return "failed";
		}
	}

	public function validateDateTime($input) {
		$log = Log::channel('wp_webhooks');
		$log->debug(__line__ . "\n input" . print_r($input, true)); 
	
		// Regular expression to validate "DD MMM YYYY - H:i" format
		// $pattern = '/^(0[1-9]|[12][0-9]|3[01])\s(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s\d{4}\s-\s([0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/';
		$pattern = '/^([0-9]|[12][0-9]|3[01])\s(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s\d{4}\s-\s([0-9]|[01][0-9]|2[0-3]):[0-5][0-9]$/';
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
	
	

	public function getFlowManager($mobile)
	{
		$flowMData = DB::connection('dev_eosglobe')
				->table('user_flow_settings')
				->select('flow_level', 'flow_sub_level', 'language')
				->where('mobile', $mobile)
				->first();
		return $flowMData;
	}
	
	public function removeFlowManager($mobile)
	{
		$result = DB::connection('dev_eosglobe')
				->table('user_flow_settings')
				->where('mobile', $mobile)->delete();
		return $result;
	}

	public function AddChatToFlowManager($mobile)
	{

		$exists = DB::connection('dev_eosglobe')
					->table('user_flow_settings')
					->where('mobile', $mobile)
					->exists();
	
		
		if(!$exists){
			DB::connection('dev_eosglobe')
				->table('user_flow_settings')
				->insert([
				'mobile' => $mobile,
				'flow_level' => 0,
				'flow_sub_level' => 0,
				//'language' => '1.1',
				'created_at' => now()
			]);
			
		}
		$flowMData = DB::connection('dev_eosglobe')
				->table('user_flow_settings')
				->select('flow_level', 'flow_sub_level', 'language')
				->where('mobile', $mobile)
				->first();

				
		return $flowMData;
	}

	public function getChatisEnd($mobile){
		$res = DB::connection('dev_eosglobe')
		->table('user_details')->select('ischatend')->where('mobile', $mobile)->get()->toArray();
		if (!empty($res) && isset($res[0]->ischatend)) {
			return $res[0]->ischatend;
		}
		
		return 1;
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

	public function deletePromptMsg($campaign, $chatID)
	{
		if($campaign == $this->hyperlocalCampaign)
		{
			DB::table('prompt_msg')->where('chat_id', $chatID)->delete();
		}
	}

	public function addPromptMsg($campaign, $chatID, $msg, $msgType = 'REPLY_BUTTON', $option = [], $msgID = '', $isEnd = 1, $attempt = 1, $route = 1, $executedOn = 60){

		$log = Log::channel('wp_webhooks');
		$log->debug(__line__."isEnd". $isEnd);

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


	public function updateUserDetails($mobile, $dataArr)
	{
		
		$exists = DB::connection('dev_eosglobe')
					->table('user_details')
					->where('mobile', $mobile)
					->exists();
		if(!$exists){
			$result = DB::connection('dev_eosglobe')
				->table('user_details')
				->insert([
				'mobile' => $mobile,
				'created_at' => now()
			]);
			
		}
		else if($exists && !empty($dataArr))
		{
			$result = DB::connection('dev_eosglobe')
				->table('user_details')
				->where('mobile', $mobile)
				->update($dataArr);
		}
		else
		{
			$result = 'exists';
		}	
		return $result;
	}

	//Prometing Msg.
	public function promptMsg()
	{		
		$log 		= Log::channel('cron_activity');
		$log->debug(__line__."-------- Welcome to promptMsg Function -----");
		
		$carbonTime 	= Carbon::createFromTimestamp(time());
		$formattedTime 	= $carbonTime->format('Hi');
		$oneMinuteAgo = Carbon::now()->subMinutes(1);
		$query = DB::table('prompt_msg')
            ->select('prompt_msg.id','prompt_msg.chat_id','prompt_msg.msg','prompt_msg.msg_type','prompt_msg.option','prompt_msg.executed_on','prompt_msg.max_attempt', 'prompt_msg.attempt', 'prompt_msg.msg_id', 'prompt_msg.is_end', 'prompt_msg.route', 'prompt_msg.created_at')
            ->whereIn('prompt_msg.status', [0,1])
			->where('prompt_msg.created_at', '<', $oneMinuteAgo);
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
								$this->addPromptMsg($campaign_id, $chatID, "Still we do not received your input yet!",'TEXT', [], '',1, 2,1);
							}
							else{
								$this->addPromptMsg($campaign_id, $chatID, "If you have any questions then please get touch in with us at career@eosglobe.com",'TEXT', [], '', 2, 2,1);
							}
							
							if($is_end == 2)
							{							
								$this->deletePromptMsg($campaign_id, $chatID);
								// $this->closedChat($client_id, $campaign_id, $chatID, $cust_unique_id, 'No_Response_by_System');
								// $this->updateUserDetails($cust_unique_id, ['ischatend'=> 2]);
								// sleep(2);
								// $reqData['message']= 'Thank you, LiveChat session has been ended';
								// $autRepMsg = $this->sendWPMsg($reqData, false,'wp_webhooks', $crudDetails);
							}
						}
					}
				}
			}
		}
	}
}
