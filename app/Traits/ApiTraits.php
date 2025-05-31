<?php
namespace App\Traits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\Chats;
use App\Models\Chat_log;
use App\Models\Api_log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use App\Traits\CommonTraits;

trait ApiTraits	
{	
	use CommonTraits;
	public static function curlHit($url, $postFields, $method = 'POST', $headers = array())
    {
		
		if(empty($url)){
			$url = 'https://mediaapi.smsgupshup.com/GatewayAPI/rest';			
		}
		$response 	= array();
		$curl 		= curl_init();
		
		if(empty($headers)){
			$headers 	= array();
		}
		
		switch($method){
			case 'POST': // not in use
				curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields); 
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);		
				
				break;
			case 'GET':		
				$params 	= http_build_query($postFields);
				$params = str_replace('%25', '%', $params);

				// $params 	= http_build_query($postFields);
				// curl_setopt($curl, CURLOPT_URL, "$url?$params");
                // curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                // curl_setopt($curl, CURLOPT_TIMEOUT, 0);
                // curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);	
				curl_setopt($curl, CURLOPT_URL, "$url?$params");
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_TIMEOUT, 0);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);	
				
				break;
			default:
                // Handle other responses or invalid choices
                $response   = ['status'=>'failed','status_code'=>400, 'msg'=>'Invalid choice. Please select a valid option', 'response'=>[]];
                break;
		}
		$result = curl_exec($curl);
		if ($result === false) {
            $message 	= curl_error($curl);
            $statusCode = curl_errno($curl);
            $remark 	= 'Curl Error';
            $response   = ['status'=>'failed','status_code'=>$statusCode, 'msg'=>$message, 'response'=>[]];
        }
        else{
            $response   = ['status'=>'success','status_code'=>'', 'msg'=>'', 'response'=>$result];
        }		
		return $response;
    }    

	public function isWorkingHours($campID,$mobile, $chatID)
	{
		$log = Log::channel('wp_webhooks');
		$log->debug("\n\n--------------------isWorkingHours Start Here------------------------------------\n");
		
		$isWorking = true;
		$campaign_data = DB::table('campaign')
		->select('id', 'wp_number', 'name', 'client_id', 'auto_reply_id', 'allocation_type', 'call_window_from', 'call_window_to', 'working_days', 'holiday_start', 'holiday_end', 'holiday_name', 'wp_crud')
		->whereIn('status', [0,1])
		->where('id', $campID)
		->first();
		$log->debug("campaign_data : ". print_r($campaign_data, true));
		
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
		$wp_number			= isset($campaign_data->wp_number) ? $campaign_data->wp_number : '';
		$wp_crud			= isset($campaign_data->wp_crud) ? $campaign_data->wp_crud : '';
		
		// Create a Carbon instance from the timestamp
		$carbonTime 	= Carbon::createFromTimestamp(time());
		$currentTime 	= $carbonTime->format('Hi');			
		$dayShortName 	= Carbon::now()->shortEnglishDayOfWeek;
		$today 			= date('Ymd');
		
		if($today >= date('Ymd', strtotime($holiday_start)) && $today <= date('Ymd', strtotime($holiday_end)))
		{
			$log->debug("\n Step 1");
			$isWorking = false;
			
			$dateofReturn = date('Y-m-d', strtotime($holiday_end . ' +1 day'));
			$parsedFromTime = Carbon::createFromFormat('Hi', sprintf('%04d', ltrim($campaign_data->call_window_from)))->format('h:i A');
			$parsedToTime = Carbon::createFromFormat('Hi', sprintf('%04d', ltrim($campaign_data->call_window_to)))->format('h:i A');
			
			$message = "Hello, and thank you for reaching out to " . strtoupper($campaignName) . ".\n";
			$message .= "We hope this message finds you well. Currently, we are not operating on the occasion of " . strtoupper($holiday_name) . ".\n";
			$message .= "Please note that our chat support is temporarily unavailable during this time. We will be back and ready to assist you on " . $dateofReturn . ".";
			
			$reqData['client_id']	= $client;
			$reqData['campaign_id'] = $campaign;
			$reqData['mobile'] 		= $mobile;
			$reqData['agent_id'] 	= 0;
			$reqData['chatID']		= $chatID;
			$reqData['message'] 	= $message;
			$reqData['wp_number']	= $wp_number;
			$response = ApiTraits::sendWPMsg($reqData, false,'wp_webhooks', ['wp_number'=> $wp_number, 'wp_crud'=>$wp_crud]);
			
			// $this->sendtextMsg($client, $campaign, $mobile, $chatID, $message, $wp_number);
			
				$log->debug("\n client : ". $client);
				$log->debug("\n campaign : ". $campaign);
				$log->debug("\n mobile : ". $mobile);
				$log->debug("\n chatID : ". $chatID);
				$log->debug("\n message : ". $message);
				$log->debug("\n wp_number : ". $wp_number);
				$log->debug("\n campID : ". $campID);				
				
				if($response['status'] == 'success')
				{
					Chats::where('id', $chatID)
						->where('client_id', $client)
						->where('campaign_id', $campaign)
						->where('is_closed', 1)
						->where('cust_unique_id', $mobile)
						->update(['dispo' => 'Dispo_by_system', 'is_closed' => 2, 'closed_by' => 0, 'closed_at' => now()]);
				}			
			
			$log->debug("\n Step 2");
		}
		else if($currentTime < $campaign_data->call_window_from || $currentTime > $campaign_data->call_window_to || !in_array(strtoupper($dayShortName), $working_days))
		{
			$log->debug("\n Step 3");
			$isWorking = false;
			$expWeeks = explode(',', $campaign_data->working_days);
			
			$parsedFromTime = Carbon::createFromFormat('Hi', sprintf('%04d', ltrim($campaign_data->call_window_from)))->format('h:i A');
			$parsedToTime = Carbon::createFromFormat('Hi', sprintf('%04d', ltrim($campaign_data->call_window_to)))->format('h:i A');	
			//$message = 'We will be happy to assist you during our operational hours from '.ucfirst(current($expWeeks)).' to '.ucfirst(end($expWeeks)).' between '.$parsedFromTime. ' to '.$parsedToTime.'.'; 
			
			$reqData['client_id']	= $client;
			$reqData['campaign_id'] = $campaign;
			$reqData['mobile'] 		= $mobile;
			$reqData['agent_id'] 	= 0;
			$reqData['chatID']		= $chatID;
			//$reqData['message'] 	= $message;
			$reqData['wp_number']	= $wp_number;
			// $response = ApiTraits::sendWPMsg($reqData, false,'wp_webhooks', ['wp_number'=> $wp_number, 'wp_crud'=>$wp_crud]);
			
			if($campaign == '37')
			{
				// $reqData['message'] = 'Thank you for reaching out to '. strtoupper($campaignName) .'. Our customer care team operates from '.ucfirst(current($expWeeks)).' to '.ucfirst(end($expWeeks)).' between '.$parsedFromTime. ' to '.$parsedToTime.'.  We will get back to you promptly next working day.';  ///'. ucfirst($campaignName) .'
				$reqData['message'] = 'Thank you 🙏 for getting in touch with Anchor by Panasonic. Our customer care team is available from '.ucfirst(current($expWeeks)).' to '.ucfirst(end($expWeeks)).' between '.$parsedFromTime. ' to '.$parsedToTime.' 🕐. We will get back to you 😊 as soon as possible. Should you have any more questions or need further assistance, please do not hesitate to contact us.📞'; 
				
				$response = ApiTraits::sendWPMsg($reqData, false,'wp_webhooks', ['wp_number'=> $wp_number, 'wp_crud'=>$wp_crud]);
				
				sleep(5);
				$reqData['message'] = 'Please leave a message or your query.';
				$response = ApiTraits::sendWPMsg($reqData, false,'wp_webhooks', ['wp_number'=> $wp_number, 'wp_crud'=>$wp_crud]);
				
				/* DB::table('prompt_msg')->insert([
					'chat_id' => $chatID,
					'msg' => 'We have not received any query, please let us know your query',
					'msg_type' => 'TEXT',
					'max_attempt' => 2,
					'attempt' => 1,
					'route' => 2,
				]);
				 */
				$log->debug("\n current : ". ucfirst(current($expWeeks)));
				$log->debug("\n current : ". ucfirst(end($expWeeks)));
			}
			else{
				$reqData['message'] = 'We will be happy to assist you during our operational hours from '.ucfirst(current($expWeeks)).' to '.ucfirst(end($expWeeks)).' between '.$parsedFromTime. ' to '.$parsedToTime.'.'; 
				$response = ApiTraits::sendWPMsg($reqData, false,'wp_webhooks', ['wp_number'=> $wp_number, 'wp_crud'=>$wp_crud]);
			}
			
			//$response = $this->sendtextMsg($client, $campaign, $mobile, $chatID, $message, $wp_number);
			
			// $log->debug("\n client : ". $client);
			// $log->debug("\n campaign : ". $campaign);
			// $log->debug("\n mobile : ". $mobile);
			// $log->debug("\n chatID : ". $chatID);
			// $log->debug("\n message : ". $message);
			// $log->debug("\n wp_number : ". $wp_number);
			// $log->debug("\n campID : ". $campID);
			$log->debug("\n response : ". print_r($response, true));
				
				if($response['status'] == 'success' && $campaign != '37')
				{
					Chats::where('id', $chatID)
					->where('client_id', $client)
					->where('campaign_id', $campaign)
					->where('is_closed', 1)
					->where('cust_unique_id', $mobile)
					->update(['dispo' => 'Dispo_by_system', 'is_closed' => 2, 'closed_by' => 0, 'closed_at' => now()]);
				}
		}
		else{
			$log->debug("\n Step 4");
			$isWorking = true;			
		}
		return $isWorking;
	}

	public function sendtextMsg($client, $campaign, $mobile, $chatID, $text, $wp_number, $users = 0)
	{
		$url	= Config::get('custom.SendMSG.UAT_URL');
		if(empty($users))
		{
			$users = 0;
		}
		$url = 'https://edas-webapi.edas.tech/vaaniSMDev/send';
		$payload = array(
			'client_id' => $client,
			'campaign_id' => $campaign,
			'mobile' => $mobile,
			'agent_id' => $users,
			'chatID' => $chatID,
			'message_type' => 'Text',
			'message' => $text,
			'wp_number' => $wp_number,
			'mediaUrl' => ''										
		);
		
		return $res = ApiTraits::curlHit($url, $payload, 'POST');
	}

	
	// to send msg using the internal function you can use this function
	public function sendWPMsg($postData, $isTemplate = false, $logSource = 'wp_webhooks', $campData = []){
		$url = '';
		if(isset($logSource) && !empty($logSource))
		{
			$log = Log::channel($logSource);
		}
		else
		{
			$log = Log::channel('wp_send');
		}
		
		$log->debug("\n--------------------Start sendWPMsg ---------\n");
		$log->debug(__line__.'Input postData : ' . print_r($postData, true));
		$log->debug(__line__.'isTemplate : ' . $isTemplate);
		$log->debug(__line__.'logSource : ' . $logSource);
		$log->debug(__line__.'campData : ' . print_r($campData, true));
		
		$result 	= ['msg'=>'Initiated msg','status'=> 'failed', 'status_code'=>'101', 'data' => []];
		$timestamp 	= round(microtime(true) * 1000);
		$messageId 	= CommonTraits::uuid();
		$templateID = 0; $filename = ''; $data = []; $payload = []; $apiRes = []; $methodFlag 	= false; $validateFlag = true;
		
		$wp_number 	= isset($postData['wp_number']) ? $postData['wp_number'] : '';
		$clientID 	= $postData['client_id'];
		$campaignID = $postData['campaign_id'];
		$sendTo 	= $postData['mobile'];
		$agent_id 	= isset($postData['agent_id']) ? $postData['agent_id'] : 0;
		$chatID 	= isset($postData['chatID']) ? $postData['chatID'] : '';
		$msg_type 	= strtolower(isset($postData['message_type']) ? $postData['message_type'] : 'text');
		$event 		= isset($postData['event']) ? $postData['event'] : '';
		$message 	= isset($postData['message']) ? $postData['message'] : '';
		$reply_id 	= isset($postData['reply_id']) ? $postData['reply_id'] : '';
		$mediaUrl 	= isset($postData['mediaUrl']) ? $postData['mediaUrl'] : '';
		$method 	= ($msg_type == 'text') ? 'SendMessage' : 'SendMediaMessage';
		
		if(!empty($sendTo) && !empty($chatID) && !empty($msg_type) && (!empty($message) || !empty($mediaUrl)))
		{
			if(ApiTraits::isChatExist($chatID) === false)
			{
				$validateFlag = false;
				$result = ['msg'=>'Invalid Chat ID','status'=> 'failed', 'status_code'=>'400', 'data' => ['chatID' => $chatID]];
			}
			
			if(ApiTraits::isClientExist($clientID) === false)
			{
				$validateFlag = false;
				$result = ['msg'=>'Invalid Client ID','status'=> 'failed', 'status_code'=>'400', 'data' => ['clientID' => $clientID]];
			}
			
			
			if(ApiTraits::isCampaingExist($campaignID) === false)
			{
				$validateFlag = false;
				$result = ['msg'=>'Invalid Campaing ID','status'=> 'failed', 'status_code'=>'400', 'data' => ['campaingID' => $campaignID]];
			}
			
			if(!empty($mediaUrl)){
				$file 		= basename($mediaUrl);
				$filename 	= pathinfo($file, PATHINFO_FILENAME);
			}
			
			if($validateFlag === true)
			{
				if(empty($campData['wp_number']) || empty($campData['wp_crud']))
				{
					$campData 	= CommonTraits::getCampaignDetails($campaignID);
				}
				
				$log->debug(__line__.'Campaign Data : ' . print_r($campData, true));
				
				$wp_number	= isset($campData['wp_number']) ? $campData['wp_number'] : '';
				
				if(isset($campData['wp_crud']) && !empty($campData['wp_crud']))
				{					
					$crudArr	= json_decode($campData['wp_crud'], true);
					$twoway_userid 		= $crudArr['twoway_userid'];
					$twoway_password 	= $crudArr['twoway_password'];
					$hsm_userid 		= $crudArr['hsm_userid'];
					$hsm_password 		= $crudArr['hsm_password'];
					$auth_scheme 		= isset($crudArr['auth_scheme']) ? $crudArr['auth_scheme'] : 'plain';
					$version 			= isset($crudArr['version']) ? $crudArr['version'] : '1.1';
					$data_encoding 		= isset($crudArr['data_encoding']) ? $crudArr['data_encoding'] : 'Unicode_text';
			
					if($isTemplate === false)
					{
						
						$payload = array(
							"format" => "json",
							"userid" => $twoway_userid,
							"password" => $twoway_password,
							"auth_scheme" => $auth_scheme,
							"v" => $version,
							"send_to" => $sendTo
						);		
							
						if ($msg_type == "text") {
							$methodFlag = true;
							$payload = array_merge($payload, array(
								"method" => $method,
								"msg" => $message,
								"msg_id" => $messageId,
								"msg_type" => $msg_type,
								"data_encoding" => $data_encoding,
							));
						} else if (preg_match('/image|document|voice|audio|video|application/', $msg_type)) {
							if(!empty($mediaUrl))
							{
								$methodFlag = true;
								$payload = array_merge($payload, array(
									"method" => $method,
									"msg" => $message,
									"msg_id" => $messageId,
									"msg_type" => $msg_type,
									"media_url" => $mediaUrl,
									"filename" => $filename,
									"isHSM" => "false",
									"caption" => $message,
									"data_encoding" => $data_encoding,
								));
							}
							else{
								$result = ['msg'=>'Missing Required paramenters','status'=>'failed', 'status_code'=>'400','data' =>['mediaUrl'=>$mediaUrl]];
							}
						}
						else{
							$result = ['msg'=>'Incomplete payload message or media missing','status'=> 'failed', 'status_code'=>'400', 'data' => $postData];
						}
						$log->debug(__line__.'Error Msg : ' . print_r($result, true));		
						$log->debug(__line__.'Complete Payload for non template : ' . print_r($payload, true));						
					}else{
						
						// For template only
						$tempType 	= strtoupper(isset($postData['templateType']) ? $postData['templateType'] : 'REPLY_BUTTON');
						$action 	= isset($postData['action']) ? $postData['action'] : '{}';
						$intType 	= isset($postData['interactive_type']) ? $postData['interactive_type'] : '';
						$footer 	= isset($postData['footer']) ? $postData['footer'] : '';		
						$templateID = isset($postData['templateID']) ? $postData['templateID'] : '';
						
						if(!empty($tempType) && !empty($action) && !empty($intType))
						{
							if($tempType == 'REPLY_BUTTON' || $tempType == 'LIST')
							{
								$log->debug(__line__.'twoway_userid : ' . $twoway_userid);		
								$payload = array(
									"userid" => $twoway_userid,
									"password" => $twoway_password,
									"auth_scheme" => $auth_scheme,
									"v" => $version,
									"send_to" => $sendTo
								);
								
								if($msg_type == 'text')
								{
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
								else if (preg_match('/image|document|voice|audio|video|application/', $msg_type)) {
									if(!empty($mediaUrl))
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
										$result = ['msg'=>'Missing Required paramenters','status'=>'failed', 'status_code'=>'400','data' =>['mediaUrl'=>$mediaUrl]];
									}							
								}
								else{
									$result = ['msg'=>'Incomplete payload message or media missing','status'=> 'failed', 'status_code'=>'400', 'data' => $postData];
								}
							}
						}						
						else
						{					
							$result = ['msg'=>'Missing Required paramenters','status'=>'failed', 'status_code'=>'400','data' =>['tempType'=>$tempType, 'action'=>$action, 'intType'=>$intType]];
						}
						
						$log->debug(__line__.'Error Msg : ' . print_r($result, true));		
						$log->debug(__line__.'Complete Payload for template : ' . print_r($payload, true));		
					}


					if($methodFlag === true)
					{
						$url	= '';
						$res 	= ApiTraits::curlHit($url, $payload, 'GET');
						
						$log->debug(__line__.'res : ' . print_r($res, true));
						if(!empty($res['response']))
						{
							$log->debug(__line__.'response : ' . print_r($res['response'], true));
							$apiRes = json_decode($res['response'], true);
							if(json_last_error() != JSON_ERROR_NONE){
								$apiRes = explode('|', $res['response']);
							}
						}
						
						$log->debug(__line__.'json_last_error : ' . json_last_error());
						$log->debug(__line__.'apiRes : ' . print_r($apiRes, true));
						$log->debug(__line__.'URL : ' . $url);
						$log->debug(__line__.'Payload : ' . print_r($payload, true));			
						
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
						
						$log->debug(__line__.'Insert Chat_log ID : '. $chat_log_data->id);
									
						
						if((isset($apiRes[0]) && $apiRes[0] == 'success') || isset($apiRes['response']['status']) && $apiRes['response']['status'] == 'success')
						{
							$result = ['msg'=>'success','status'=> 'success', 'status_code'=>'200', 'data' => $res['response']];
						}
						else{
							
							$result = ['msg'=>$apiRes[2],'status'=> $apiRes[0], 'status_code'=>$apiRes[1], 'data' => $res['response']];
						}
					
					}
					else{
						$result = ['msg'=>'Incomplete payload message or media missing','status'=> 'failed', 'status_code'=>'400', 'data' => $postData];
					}
				}
				else{
					$result = ['msg'=>'API Details Missing','status'=> 'failed', 'status_code'=>'400', 'data' => $campData];
				}
			}
		}		
		else{
			
			$result = ['msg'=>'Missing Required paramenters','status'=> 'failed', 'status_code'=>'400', 'data' => ['sendTo'=>$sendTo, 'chatID'=>$chatID, 'msg_type'=>$msg_type, 'message'=>$message, 'mediaUrl'=>$mediaUrl]];
		}		
		
		$log->debug(__line__.' Result Log : '. print_r($result, true));
		// Log API response
		$api_log = new api_log();
		$api_log->client_id = $clientID;
		$api_log->campaign_id = $campaignID;
		$api_log->ref_table = 'chat_log';
		$api_log->ref_id = isset($chat_log_data->id) ? $chat_log_data->id : 0;
		$api_log->type = 'API';
		$api_log->cust_no = $sendTo;
		$api_log->org_no = $wp_number;
		$api_log->response_data = json_encode($result);
		$api_log->save();
		
		$log->debug(__line__.'Last Inserted ID of APILOG Table : '. $api_log->id);
		$log->debug(__line__.'\n-------------- End sendWPMsg ----------------------');
		return $result;
	}
	

	
	public function getConsent($clientID, $campaignID, $phone, $logSource = 'wp_webhooks', $type = 'OPT_IN', $campData, $name = ''){
		
		if(isset($logSource) && !empty($logSource))
		{
			$log = Log::channel($logSource);
		}
		else
		{
			$log = Log::channel('wp_send');
		}
		
		$log->debug(__line__."\n-------- Start getOptIN Here ---------------\n");
		
		$returnArr = ['status'=> 'failed', 'status_code' => 400];			
		$wp_number	= isset($campData['wp_number']) ? $campData['wp_number'] : '';			
		if(isset($campData['wp_crud']) && !empty($campData['wp_crud']))
		{					
			$crudArr	= json_decode($campData['wp_crud'], true);
			$twoway_userid 		= $crudArr['twoway_userid'];
			$twoway_password 	= $crudArr['twoway_password'];
			$hsm_userid 		= $crudArr['hsm_userid'];
			$hsm_password 		= $crudArr['hsm_password'];
			$auth_scheme 		= isset($crudArr['auth_scheme']) ? $crudArr['auth_scheme'] : 'plain';
			$version 			= isset($crudArr['version']) ? $crudArr['version'] : '1.1';
			$data_encoding 		= isset($crudArr['data_encoding']) ? $crudArr['data_encoding'] : 'Unicode_text';
		}
		
		$payload = array(
			"method" => $type,
			"format" => "json",
			"userid" => $hsm_userid,
			"password" => $hsm_password,
			"auth_scheme" => $auth_scheme,
			"v" => $version,
			"phone_number" => $phone,
			"channel" => "WHATSAPP"
		);
		
		$url	= '';
		$res 	= ApiTraits::curlHit($url, $payload, 'GET');
		$log->debug(__line__.'Optin Status : ' . print_r($res, true));
		$response 	= json_decode($res['response'], true);
		
		$response_messages = $response['response']['status'];
		$optin = 1;
		$returnArr = array('status'=> $response['response']['status'], 'status_code' => 101, 'msg'=>$response['response']['details']);
		if($response['response']['status'] == 'success' && strtoupper($type) == 'OPT_IN')
		{
			$optin = 2;
			$returnArr = array('status'=> 'success', 'status_code' => 200);
		}
		
			
		$data = [
			'client_id' => $clientID,
			'campaign_id' => $campaignID,
			'name' => $name,
			'org_no' => $wp_number,
			'mobile' => $phone,
			'is_optin' => $optin,
			'status' => 1,
		];

		$conditions = [
			'mobile' => $phone,
			'org_no' => $wp_number
		];

		// Perform the update or insert
		$status = DB::table('customer_master')->updateOrInsert($conditions, $data);
		
		$log->debug(__line__.'Optin Status captured in table : ' . $status);
		
		$log->debug(__line__."\n\n--------------------End  getOptIN Here------------------------------------\n");
		
		return $returnArr;
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
		else{
			return false;
		}
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
		else{
			return false;
		}
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
		else{
			return false;
		}
	}
	
	public function sendWPMsgByChatID($chatIDs, $logSource = 'wp_webhooks')
	{
		if(isset($logSource) && !empty($logSource))
		{
			$log = Log::channel($logSource);
		}
		else
		{
			$log = Log::channel('wp_send');
		}
		
		$log->debug(__LINE__."---------- Wecome to sendWPMsgByChatID Function ---------------------");
		$log->debug(__LINE__."chatIDs : ". print_r($chatIDs, true));
		
		if(is_array($chatIDs))
		{
			$chatsDetails = DB::table('chats')
				->leftJoin('users', 'users.id', '=', 'chats.assigned_to')
				->select('chats.id', 'chats.client_id', 'chats.campaign_id', 'chats.cust_unique_id', 'chats.assigned_to', 'users.name', 'users.username')
				->whereIn('chats.status', [0,1])
				->whereIn('chats.id', $chatID)
				->get();
				$campIDs = $chatsDetails->pluck('campaign_id')->toArray();
				
				$log->debug(__LINE__."Step1 : ". print_r($campIDs, true));
		}
		else{
			$chatsDetails[] = DB::table('chats')
				->leftJoin('users', 'users.id', '=', 'chats.assigned_to')
				->select('chats.id', 'chats.client_id', 'chats.campaign_id', 'chats.cust_unique_id', 'chats.assigned_to', 'users.name', 'users.username')
				->whereIn('chats.status', [0,1])
				->where('chats.id', $chatID)
				->first();
				
				$campIDs = $chatsDetails[0]->campaign_id;
				$log->debug(__LINE__."Step2 : ". print_r($campIDs, true));
		}
		
		$log->debug(__LINE__."Step3 : ". print_r($chatsDetails, true));
		
		$campaignDetails = DB::table('campaign')
		->select('id', 'wp_number',  'wp_crud')
		->whereIn('status', [0,1])
		->whereIn('id', $campIDs)
		->get();
		
		$campDataArr = [];
		if(!empty($campaignDetails))
		{
			foreach($campaignDetails as $k => $campData)
			{
				$campDataArr[$campData->id] =  $chatData;				
			}
			$log->debug(__LINE__."Step4 : ". print_r($campDataArr, true));

			foreach($chatsDetails as $k => $chatData)
			{
				$log->debug(__LINE__."Step5 :  ". print_r($chatData, true));
				
				$reqData = $crudDetails = []; //'client_id',  'campaign_id',  'cust_unique_id'
				$reqData['client_id']	= $chatData->client_id;
				$reqData['campaign_id'] = $chatData->campaign_id;
				$reqData['mobile'] 		= $chatData->cust_unique_id;
				$reqData['agent_id'] 	= 0;
				$reqData['chatID']		= $chatData->id;
				$reqData['message_type']= 'text'; 
				$reqData['event'] 		= '';
				$reqData['message'] 	= 'Agent ('.ucfirst($chatData->name).') has been assinged to you.';
				$reqData['reply_id'] 	= '';
				$reqData['mediaUrl'] 	= '';	
				
				$wp_number = $campDataArr[$chatData->campaign_id]->wp_number;
				$wp_crud = $campDataArr[$chatData->campaign_id]->wp_crud;

				$Result = ApiTraits::sendWPMsg($reqData, false,'wp_webhooks', ['wp_number'=> $wp_number, 'wp_crud'=>$wp_crud]);	
					
			}

			$log->debug(__LINE__."Step6 :  Complete Process ");		

			$log->debug(__LINE__."---------- End sendWPMsgByChatID Function ---------------------");			
		}
	}
}