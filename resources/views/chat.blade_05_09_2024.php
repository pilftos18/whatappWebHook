

@extends('layout')

@section('content')

<!-- chat code by mahesh start -->
<link rel="stylesheet" href="{{asset('assets/agent_ui/css/bootstrap.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/agent_ui/css/bootstrap-icons.css')}}" >
<link rel="stylesheet" href="{{asset('assets/agent_ui/emoji/css/style.css')}}">
<link rel="stylesheet" href="{{asset('assets/agent_ui/emoji/emoji-stylesheet.css')}}">

<link rel="stylesheet" href="{{asset('assets/agent_ui/css/layout.css')}}">
<link rel="stylesheet" href="{{asset('assets/agent_ui/css/work-sans-fonts.css')}}">
<link rel="stylesheet" href="{{asset('assets/agent_ui/css/social-media.css')}}">
<link rel="stylesheet" href="{{asset('assets/agent_ui/css/main-app.css')}}">
<link rel="stylesheet" href="{{asset('assets/agent_ui/css/style.css')}}">
<!-- chat code by mahesh end 22 dec 2023  -->
<style>
  .header-super-admin {
    margin-left: 0;
  }
  #changeCampaign:hover {
    background: #1F4585;
  }

  
</style>
  <main>
    <!-- Social Chat Start -->
    <div class="social-chat-for-all d-flex">
      <div class="sidebar-nav chat-list">
        <div class="nav mt-3 align-items-center px-2">
					<div class="nav-item w-100">
						<form class="rounded position-relative">
              <input class="form-control ps-5 bg-light" type="search" placeholder="Search..." aria-label="Search" id="searchInput">
							<button class="btn bg-transparent px-2 py-0 position-absolute top-50 start-0 translate-middle-y" type="submit"><i class="bi bi-search fs-5"> </i></button>
              <a href="#" class="clear-search" id="clearSearch"><i class="bi bi-x-lg"></i></a>
						</form>
					</div>
				</div>
        <ul class="nav nav-pills nav-justified">
          <!-- <li role="presentation " id="allChat" class="chatTab "><a href="#">All</a></li> -->
          <li role="presentation" id="activeChat" class="chatTab "><a href="#">Active</a></li>
          <!-- <li role="presentation" id="closeChat" class="chatTab "><a href="#">Close</a></li> -->
        </ul>
        <div  id="customer_list_default">
        @if($chats)
        @foreach ($chats as $key => $chat)
        @php
          $flag=0;
          $client_id = $chat->client_id;
          $campaign_id = $chat->campaign_id;
        @endphp
        @if($flag < $chat->interaction_per_user)
        <div  data-id="{{$chat->id}}" class="message-chat-box whatsapp-chat " onclick="displaychatpanel({{$chat->id}})">
          <div class="message-body">
            <div class="left-side">
              <div class="profile-pic"></div>
              <!-- <img class="img-responsive" src="revamp/assets/img/user.png" alt=""> -->
            </div>
            <div class="right-side">
              <div class="mr-auto">
                <h4>{{$chat->customer_name}} </h4>
                <p class="chatMsg" style="display: none;">
                </p>
                <!-- <p id="typoText">Typing<span class="dot-pulse"></span></p> -->
                @foreach($chatlogArray2 as $chatlog2)
                @if($chatlog2['chat_id'] == $chat->id)
                <p>{!! $chatlog2['msg'] !!}</p>
              </div>
              <div class="text-right count-time-box">
                <div class="time">{{$chatlog2['chatTime']}}</div>
                @endif
                @endforeach
                <?php
                  if($chatlogArray[$chat->id] > 0){
                    ?>
                    <div class="count 1" id="count_{{$chat->id}}">{{$chatlogArray[$chat->id]}}</div>
                    <?php
                  }
                ?>
                
              </div>
            </div>
          </div>
        </div>
            @php 
                  $flag++;
            @endphp
          @endif
        @endforeach
        @endif
        </div>
        <div  id="customer_list"></div>
      </div> 
      <div class="mr-auto main-content whatsapp-chat" id="chatbox-container">

      <!-- canned response template start 20/11/2023 -->
      <div class="template-box">
          <h3>Templates for canned response</h3>
          <div class="row">
          
          @if($templates)
            @foreach ($templates as $key => $value)
            <div class="col-sm-4">
              <div class="template-content greeting-template">
                <div>
                {{$value->caption}}
                </div>
              </div>
              <h6>{{$value->name}}</h6>
            </div>
              @endforeach
          @endif
          </div>
          <div class="template-btn-box text-end">
            <button type="button" class="btn btn-secondary" id="closeTemplate">Close</button>
            <button type="button" class="btn btn-primary disabled" id="selectTemplate">OK</button>
          </div>
        </div>
        <!-- canned response template end -->

      <!-- Loader start -->
      {{-- <div id="loader" class="loader-wrapper inner-loader">
        <div class="loader-container">
          <div class="loader-box">
            <div class="ring"></div>
            <div class="ring"></div>
            <div class="ring"></div>
            <div class="ring"></div>
            <div class="loading-logo">
              <img src="{{asset('assets/img/edas-logo.png')}}" alt="Edas Logo">
            </div>
          </div>
        </div>
      </div> --}}
      <!-- Loader end -->

        <div class="chat-body">
          <div class="chatting-box">
            <ul class="nav chatting" id="msgList">
              <!-- whatsapp chat list  -->
            </ul>
            
          </div>
        </div>
        <div class="chat-send-box" data-emojiarea data-type="css" data-global-picker="false" style="display: flex;">
          <!-- <textarea id="text-message" class="form-control" placeholder="Send your message..."></textarea> -->
          <!-- <form  id="sendMessageForm" enctype="multipart/form-data"> -->
            <span class="uploaded-file-preview"></span>
              <!-- <label for="attach-image" title="Attach" class="attach-btn">
                <span class="">+</span>
              </label> -->
              <input type="hidden" id='chat_id' >
              <input type="hidden" id='client_name' >
                <input type="hidden" id='campaign_name' >
                <input type="hidden" id='mobile_number' >
              <input  style="display: none" multiple name="upload[]" type="file" class="form-control image-message"
                id="attach-image">

                <!-- modification for canned response and attachment start 20/11/2023 -->
              <div class="more-features" data-toggle="tooltip" title="Attachments">
                <a href="javascript:void(0)" class="mf-btn" id="moreFeatureBtn">
                  <span class="">+</span>
                </a>
                <div class="mf-content">
                  
                  <label for="attach-image"  class="mf-btn" data-toggle="tooltip" title="Attach Files">
                  <i class="bi bi-paperclip"></i>
                </label>
                  <a href="#" class="mf-btn" id="sendChatBtn" data-toggle="tooltip" title="Templates">
                    <svg version="1.1" id="Layer_1"  xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px"
                      y="0px" viewBox="0 0 570 462" style="enable-background:new 0 0 570 462;" xml:space="preserve" width="22px" height="22px" fill="#f48120">
                      <path d="M93,233.2c1.2-7.1,2.2-14.3,3.7-21.3C108.1,160,153.6,117,206,108.8c8.7-1.4,17.6-2.1,26.5-2.1c30.5-0.3,61,0.1,91.5-0.2
                    c20.6-0.1,41,0.2,60.7,7.6c0.8-1.4,1.4-2.6,2-3.8c7.6-16,15.2-32,22.8-48c6.3-13.3,19.2-18.2,32.8-12.2c5.4,2.4,10.7,4.9,15.9,7.6
                    c12.7,6.6,17.1,19.7,11,32.7c-9,19-17.9,37.9-27,56.8c-1.2,2.5-1.1,4,0.8,6.2c19.4,22.2,30.7,48,33.5,77.4c0.1,1.1,0.4,2.2,0.6,3.2
                    c0,8.3,0,16.5,0,24.8c-1.2,7.2-2.2,14.5-3.8,21.7c-11.6,52.6-57.9,95.8-111.2,103.5c-9.1,1.3-18.4,1.6-27.6,1.8
                    c-15.1,0.3-30.2,0.1-45.4,0.1c-1.2,0-2.5,0-3.7-0.2c-2.9-0.4-4.5-2.3-4.7-5.1c-0.2-2.8,1.2-4.9,4-5.6c1.4-0.4,3-0.4,4.5-0.4
                    c18.9-0.1,37.8,0.2,56.6-0.2c57.1-1.4,108.3-45.9,117.8-102.4c6.1-36.1,0.7-69.9-20.6-100.4c-2.3-3.3-4.8-6.3-7.5-9.9
                    c-0.8,1.7-1.5,2.8-2,4c-15.4,32.4-30.7,64.7-46.2,97.1c-1.2,2.5-3,4.9-5,6.8c-14.9,14-30,28-45,41.9c-5.1,4.7-7.1,4.9-13.3,1.7
                    c-1.3-0.7-3.1-0.7-4.7-0.7c-45.4,0-90.7,0-136.1,0c-1.7,0-3.5,0-5.2-0.3c-2.8-0.6-4.2-2.6-4.1-5.5c0.1-2.8,1.6-4.7,4.4-5.2
                    c1.5-0.3,3-0.2,4.5-0.2c44.6,0,89.2,0,133.9,0c1.6,0,3.2,0,5.2,0c0.4-6,0.9-11.7,1.3-17.3c0.9-13.5,1.6-27,2.6-40.4
                    c0.2-3.2,1-6.4,2.3-9.3c7-15.1,14.1-30,21.4-45c0.9-1.8,2.4-3.9,4.1-4.4c1.6-0.5,4.5,0.2,5.6,1.5c1.1,1.4,1.6,4.3,0.9,6
                    c-2,5.3-4.7,10.4-7.2,15.6c-4.5,9.6-9.1,19.1-13.8,29.1c13,6.2,25.9,12.4,39.2,18.7c22.7-47.9,45.4-95.6,68.1-143.5
                    c-13.1-6.3-26-12.4-39.2-18.7c-3.8,8.1-7.5,15.8-11.2,23.6c-8,16.8-15.9,33.6-24,50.4c-0.9,1.8-2.7,3.8-4.5,4.5
                    c-1.4,0.5-4.5-0.4-5.1-1.6c-1-1.9-1.5-4.9-0.6-6.8c5.1-11.5,10.7-22.8,16.1-34.2c0.7-1.5,1.3-2.9,2.4-5.1
                    c-5.4-1.4-10.4-3.2-15.6-3.9c-8.3-1.2-16.6-2.3-24.9-2.3c-36.2-0.2-72.5-0.1-108.7-0.1c-60.5,0-112,40.9-123.7,100.4
                    c-9.5,48.4,2.7,90.9,39.9,124.9c2.8,2.6,4.1,5.3,3.8,9.1c-1,15.1-1.7,30.2-2.5,45.3c-0.1,1,0,1.9,0,3.4c3.3-1.7,6.1-3.1,9-4.5
                    c12.7-6.5,25.2-13.2,38-19.5c2.8-1.4,6.2-2.2,9.3-2.2c18.1-0.2,36.2-0.1,54.4-0.1c1.4,0,2.8,0,4.1,0.4c2.8,0.8,4.2,2.8,4,5.6
                    c-0.2,2.7-1.8,4.6-4.7,5.1c-1.2,0.2-2.5,0.1-3.7,0.1c-17.4,0-34.7-0.1-52.1,0.1c-2.5,0-5.2,0.7-7.4,1.8c-16.1,8.1-32,16.4-48,24.6
                    c-1.3,0.7-2.7,1.4-4.1,1.7c-5.9,1.5-10.3-1.8-10.2-7.9c0.1-6.5,0.5-13,0.9-19.5c0.6-10.7,1.3-21.5,1.7-32.2c0.1-1.7-1-3.8-2.2-5
                    c-25.3-24.1-39.9-53.3-43.3-88.1c-0.1-0.8-0.4-1.7-0.6-2.5C93,250.3,93,241.8,93,233.2z M412,83.3c13.3,6.3,26.2,12.5,39.2,18.6
                    c2.8-6,5.5-11.5,8-17c3.1-6.7,1.4-13.1-5.1-16.7c-5.8-3.2-11.8-6-17.9-8.5c-5.9-2.5-12.4-0.5-15.1,4.6
                    C417.9,70.4,415.1,76.7,412,83.3z M336.6,248.4c-0.5,7.1-1,13.5-1.2,19.8c0,0.8,0.9,2,1.7,2.4c5.5,2.7,11,5.3,16.6,7.9
                    c0.7,0.3,1.9,0.5,2.4,0.1c5-4.4,9.8-9,15-13.9C359.3,259.2,348.3,253.9,336.6,248.4z M346.3,287.6c-4.1-2-7.8-3.7-11.9-5.7
                    c-0.4,5.9-0.7,11.4-1.1,17.8C338.1,295.3,342,291.6,346.3,287.6z" />
                      <path d="M240.4,231.9c-19.4,0-38.7,0-58.1,0c-1.2,0-2.5,0-3.7-0.1c-2.8-0.4-4.5-2-4.8-4.9c-0.3-3,1.1-5.1,4-5.9
                    c1.4-0.4,3-0.4,4.5-0.4c38.7,0,77.4,0,116.2,0c0.6,0,1.2,0,1.9,0c4.3,0.1,6.8,2.3,6.8,5.8c-0.1,3.4-2.4,5.4-6.7,5.4
                    C280.4,232,260.4,231.9,240.4,231.9z" />
                      <path d="M240.6,261c19.4,0,38.7,0,58.1,0c1.1,0,2.3-0.1,3.4,0.1c3,0.4,4.8,2.2,5,5.2c0.2,3-1.4,5-4.3,5.8c-1.3,0.4-2.7,0.2-4.1,0.2
                    c-38.8,0-77.7,0-116.5,0c-1.1,0-2.3,0.1-3.4-0.1c-3.2-0.4-5.2-2.9-5-6c0.3-3.2,2.2-4.9,5.4-5.2c1.5-0.1,3,0,4.5,0
                    C202.6,261,221.6,261,240.6,261z" />
                      <path d="M216.3,180.6c11.7,0,23.5,0,35.2,0c4.7,0,7.3,2,7.2,5.6c-0.1,3.6-2.6,5.6-7.4,5.6c-23.4,0-46.7,0-70.1,0c-0.5,0-1,0-1.5,0
                    c-3.8-0.3-6.1-2.5-6.1-5.8c0-3.4,2.3-5.4,6.2-5.4C192.1,180.6,204.2,180.6,216.3,180.6z" />
                    </svg>
                  </a>
                </div>
              </div>
              <!-- modification for canned response and attachment end -->

            <div class="smiley-emoji-icons-btn emoji emoji-button">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 384" width="25">
                <g id="Layer_2" data-name="Layer 2">
                  <g id="Layer_1-2" data-name="Layer 1">
                    <path
                      d="M180,0h24c2.46.31,4.91.65,7.37.91,38,4.09,72,17.92,101.53,42.16,36.81,30.22,59.71,68.86,68.15,115.85,1.26,7,2,14.05,2.95,21.08v24c-.58,4.69-1.09,9.39-1.76,14.07-5.47,38.35-20.88,72.21-46.66,101.12C306,352.36,269.25,373.09,225.43,381c-7.09,1.29-14.28,2-21.43,3H180c-4.68-.58-9.38-1.08-14.05-1.75-38.07-5.47-71.74-20.66-100.55-46.17C31.9,306.43,11,269.52,3,225.45c-1.29-7.1-2-14.3-3-21.45V180c.84-6.42,1.47-12.87,2.55-19.24,6.79-39.95,24.32-74.5,52.76-103.34C84,28.31,118.59,10,158.93,3,165.91,1.72,173,1,180,0ZM192,24C99.43,24.05,24.12,99.33,24,191.86S99.46,360,192.05,360s167.82-75.33,168-167.87S284.57,24,192,24Z" />
                    <path
                      d="M188.28,308.59c-40-1.39-73.87-19.73-96.84-57.2-4.6-7.51-1.41-16,6.56-18.39,5.36-1.6,10.63.55,14,6.06a95.27,95.27,0,0,0,27.37,29.4C181.68,298,242.67,285.7,270.13,242c.87-1.38,1.7-2.78,2.62-4.11a11.93,11.93,0,0,1,16.19-3.69c5.45,3.28,7.78,10.49,4.17,16C288,258,282.75,266,276.36,272.68,253.5,296.78,225,308.12,188.28,308.59Z" />
                    <path d="M89.1,144.87A23.86,23.86,0,0,1,113,120.73,24,24,0,1,1,89.1,144.87Z" />
                    <path
                      d="M294.9,144.54a23.93,23.93,0,0,1-23.84,24.18,24,24,0,0,1-.34-48A23.84,23.84,0,0,1,294.9,144.54Z" />
                  </g>
                </g>
              </svg>
            </div>
            <button type="button" onclick="sendMessage()"  class="send-btn">
              <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="paper-plane"
                class="svg-inline--fa fa-paper-plane fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 512 512">
                <path fill="currentColor"
                  d="M476 3.2L12.5 270.6c-18.1 10.4-15.8 35.6 2.2 43.2L121 358.4l287.3-253.2c5.5-4.9 13.3 2.6 8.6 8.3L176 407v80.5c0 23.6 28.5 32.9 42.5 15.8L282 426l124.6 52.2c14.2 6 30.4-2.9 33-18.2l72-432C515 7.8 493.3-6.8 476 3.2z">
                </path>
              </svg>
            </button>
          <!-- </form> -->
        </div>
      </div>
        <div class="sidebar-nav chat-info" style="display: flex;" id="chatbox-dispo">
        
          <input type="hidden" id='client_id' value="{{ $client_id ?? '' }}">
          <input type="hidden" id='campaign_id' value="{{$campaign_id ?? ''}}">
          <input type="hidden" id='planid' value="">
          <div class="participant">
              <div class="left-side">
                <img class="img-responsive" src="{{asset('assets/agent_ui/img/user.png')}}" alt="">
              </div>
              <div class="right-side">
                <h4 class='customer_name_show'></h4>
                <div class='mobile_number_show'></div>
                <span class="location"></span>
              </div>
          </div>
          <div class="disposition-container">
            <h1>Disposition</h1>
            <div class="form-group">
              <label for="exampleInputEmail1">Disposition</label>
              <select class="form-control" id="dispo">
                <option>Select</option>
              </select>
            </div>
            <!-- <div class="form-group" id="Level2Disposition" style="display:none"> -->
            <div class="form-group" >
              <label for="exampleInputEmail1">Sub Disposition</label>
              <select class="form-control" id="sub_dispo">
              <option>Select</option>
              </select>
            </div>
            <!-- <div class="form-group" id="Level3Disposition" style="display:none"> -->
            <div class="form-group" id="Level3Disposition" style="display:none">
              <label for="exampleInputEmail1">Sub Sub Disposition</label>
              <select class="form-control" id="sub_sub_dispo">
              <option>Select</option>
              </select>
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Remark</label>
              <textarea class="form-control" name="remarks" id="remarks" rows="7" placeholder="Write here.."></textarea>
            </div>
            <div class="text-center">
              <button type="submit" onclick="submitDispo()" class="btn btn-primary">Submit</button>
            </div>
          </div>
          
        </div>
    </div>
    <!-- Social Chat End -->
  </main>


  <!-- Notification start 20/11/2023 -->
  <div class="notification-pop">
		<div class="message-body">
			<div class="left-side">
				<img class="img-responsive" src="{{asset('assets/agent_ui/img/user.png')}}" alt="">
			</div>
			<div class="right-side">
        <a href="javascript:void(0)" id="close_notification" class="close">x</a>
				<h4></h4>
				<p></p>
			</div>
		</div>
	</div>
  <!-- Notification end -->
  
    <!-- break popup  -->
  <div class="not-ready-reason-container z-index-high" style="display:none;">
    <div class="modal fade show" style="display:block;">
      <div class="modal-dialog">
        <div class="modal-content">
        <a href="#" class="close_break" id="closeBreakModal"><i class="bi bi-x-lg"></i></a>
          <div class="modal-header">
            <h4 class="modal-title">Break Reason</h4>
          </div>
          <div class="modal-body">
            <div class="sip-phone-content">
              <div class="not-ready-reason-list">
              <form id="pauseForm" class="dynamic-radio-set">
              @if($breaks)
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="breakType" id="S1_breakType" value="S1_Soft">
                    <label class="form-check-label" for="S1_breakType"> Soft Break </label>
                </div>
                @foreach ($breaks as  $break)
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="breakType" id="{{$break->id}}_breakType" value="{{$break->id}}_{{$break->name}}">
                    <label class="form-check-label" for="{{$break->id}}_breakType"> {{$break->name}} </label>
                </div>
                @endforeach
              @endif
                
              </form>
              </div>
            </div>
          </div>
        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>
    <!-- <div class="header-list">
      <h4>Break Reason</h4>
    </div> -->
    <div class="modal-backdrop fade show"></div>
  </div>

  <!-- window load Loader start -->
  {{-- <div id="windowLoader" class="loader-wrapper">
    <div class="loader-container">
      <div class="loader-box">
        <div class="ring"></div>
        <div class="ring"></div>
        <div class="ring"></div>
        <div class="ring"></div>
        <div class="loading-logo">
          <img src="{{asset('assets/img/edas-logo.png')}}" alt="Edas Logo">
        </div>
      </div>
    </div>
  </div> --}}
  <!-- Loader end -->

  <!-- <nav class="navbar fixed-bottom">
    <div class="container-fluid justify-content-center">
      Powered by&nbsp; <a href="https://edas.tech/" target="_blank">Edas</a> &nbsp;2023
    </div>
  </nav> -->
  <!-- <script src="js/jquery-3.6.4.min.js.js"></script> -->
  <script src="{{asset('assets/agent_ui/emoji/js/jquery-3.2.1.min.js')}}"></script>
  <script src="{{asset('assets/agent_ui/js/bootstrap.bundle.min.js')}}"></script>
  <!-- emoji JS Plugin -->
  <script src="{{asset('assets/agent_ui/emoji/js/jquery.emojiarea.js')}}"></script>
  <script>


    $(document).ready(function(){
      // $('[data-toggle="tooltip"]').tooltip();
      // loadershow()
      var myflag;
      var oldCount;
      $('#pauseContainer').hide();
       $('#chatbox-container').css('display', 'none');
       $('#chatbox-dispo').css('display', 'none');
       $('#activeChat').addClass('active');
      // $(".notification-pop").show(); 
        });

    var currentfile = '';

    $("#attach-image").change(function (e) {
      var filename = e.target.files[0].name;
      // $(".emoji-editor").text(filename);
      readURL(this);
    });

      

  function readURL(input) {
  currentfile = input;
  var file = input.files[0];
  var fileType = file['type'];
  var validImageTypes = ['image/jpg', 'image/jpeg', 'image/png', 'image/gif'];
  var validVideoTypes = ['video/mp4'];
  var validAudioTypes = ['audio/ogg', 'audio/m4a', 'audio/mpeg', 'audio/mp3'];
  var fileDoc = ['application/pdf', 'application/xhtml+xml', 'application/excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls', 'application/x-xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv', 'application/powerpoint', 'application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];

  if (input.files && file) {
    var fileSizeInMB = file.size / (1024 * 1024); // Convert bytes to MB

    if (fileSizeInMB > 2) {
      alert('File size exceeds 2MB. Please choose a smaller file.');
      $('.uploaded-file-preview').css('background-image', 'none').removeClass('uploadedImg');
      return; // Stop execution if file size is too large
    }

    if (validImageTypes.includes(fileType)) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $('.uploaded-file-preview').css({ 'background-image': 'url(' + e.target.result + ')', 'display': 'inline-block' }).addClass('uploadedImg');
      }
      reader.readAsDataURL(input.files[0]);
    } else if (validVideoTypes.includes(fileType)) {
      // Handle video file preview
      var icon = "{{asset('assets/img/video_icon.png')}}";
      $('.uploaded-file-preview').css({ 'background-image': 'url("' + icon + '")', 'display': 'inline-block' }).addClass('uploadedImg');
    } else if (validAudioTypes.includes(fileType)) {
      // Handle audio file preview
      var icon = "{{asset('assets/img/audio_icon.png')}}";
      $('.uploaded-file-preview').css({ 'background-image': 'url("' + icon + '")', 'display': 'inline-block' }).addClass('uploadedImg');
    } else if (fileDoc.includes(fileType)) {
      var reader = new FileReader();
      var url = "{{asset('assets/img/doc.png')}}";
      reader.onload = function (e) {
        $('.uploaded-file-preview').css({ 'background-image': 'url("' + url + '")', 'display': 'inline-block' }).addClass('uploadedImg');
      }
      reader.readAsDataURL(input.files[0]);
    } else {
      alert('File extension not allowed!');
      $('.uploaded-file-preview').css('background-image', 'none').removeClass('uploadedImg');
    }
  }
}


    function heightScroll() {
      var ChatDiv = $('.chat-body');
      var height = ChatDiv[0].scrollHeight;
      ChatDiv.scrollTop(height);
    }
    heightScroll();

    function sendMessageAndCloseChat(dispo,sub_dispo,remarks){
        var id = $('#chat_id').val();
        var client_id = $('#client_id').val();
        var campaign_id = $('#campaign_id').val();
        

        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        
        $.ajax({
                type: 'GET',
                url: "{{ url('/msg_closed') }}",
                data: {id : id, sub_dispo : sub_dispo, dispo : dispo, remarks : remarks, csrfToken: csrfToken},
                // processData: false, // Prevent jQuery from processing data
                // contentType: false, // Set content type to false to let the server handle it
                success: function(data) {
                    
                  alert('Chat closed successfully.');
                //   console.log(data);
                  // $('#whatsappMSG').val('');
                  // $('#attach-image').val('');
                  // $(".emoji-editor").val('');
                  // heightScroll();
                  $('#chatbox-container').css('display', 'none');
                  $('#chatbox-dispo').css('display', 'none');
                  location.reload();
                },
                error: function() {
                    console.log('Error fetching data from sendMessageAndCloseChat.');
                }
            });
    }

    function submitDispo() {
      
    // Validate all required fields
    var dispo = $('#dispo').val();
    var sub_dispo = $('#sub_dispo').val();
      var remarks = $('#remarks').val();
      // if (!sub_dispo || !remarks) {
      //   alert('Please fill in all required fields.');
      //   return; // Stop execution if any required field is empty
      // }

      // Confirmation prompt before proceeding
      var confirmation = confirm('Do you want to close this chat?');
      if (!confirmation) {
        return; // Stop execution if user clicks Cancel
      }

      $(".emoji-editor").text('Thank you, LiveChat session has been ended');
      sendMessage();

      setTimeout(function() {
        sendMessageAndCloseChat(dispo, sub_dispo, remarks);
      }, 3000);

        
    }

    document.addEventListener('keydown', function(event) {
      // Check if the pressed key is 'Enter' (key code 13)
      var msg =  $(".emoji-editor").text();
      var files = $('#attach-image')[0].files;
      if (msg.trim() === "" && (!files || files.length === 0)) {}else{
          if (event.key === 'Enter') {
          // Call the sendMessage function
          sendMessage();
        }
      }
      
    });

    function sendMessage() {
     
      $('.send-btn').prop('disabled', true);
      
            var msg =  $(".emoji-editor").text();
            var files = $('#attach-image')[0].files;
            if (msg.trim() === "" && (!files || files.length === 0)) {
              // Show an error message or perform any other action
              alert("Error: Message cannot be empty!");
              $('.send-btn').prop('disabled', false);
              return; // Exit the function if the message is empty
            }
            
            var id = $('#chat_id').val();
            var client_name = $('#client_name').val();
            var campaign_name = $('#campaign_name').val();
            var mobile_number = $('#mobile_number').val();
            // console.log('mobile_number '+mobile_number);
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            var formData = new FormData();

            // Append files to the formData object
            
            for (var i = 0; i < files.length; i++) {
              var file = files[i];
             
              // Check if file size exceeds 2MB (2 * 1024 * 1024 bytes)
              if (file.size > 10 * 1024 * 1024) {
                  alert('File size should not exceed 10MB.');
                  $('.send-btn').prop('disabled', false);
                  $(".uploaded-file-preview").removeAttr('style');
                  $(".uploaded-file-preview").removeClass('uploadedImg');
                  $('#attach-image').val('');
                  return; // Stop further execution
              }
                formData.append('upload[]', file);
                
            }
            // console.log('new=======>');
            // console.log(formData.getAll('upload[]'));
            // alert(formData.getAll('upload[]'));
            // Append other data
            formData.append('id', id);
            formData.append('msg', msg);
            formData.append('client_name', client_name);
            formData.append('campaign_name', campaign_name);
            formData.append('mobile_number', mobile_number);
            formData.append('_token', csrfToken);


            $.ajax({
                type: 'POST',
                url: "{{ url('/store_msg') }}",
                data: formData,
                processData: false, // Prevent jQuery from processing data
                contentType: false, // Set content type to false to let the server handle it
                success: function(data) {
                    
                    if(data[0]=='Fail'){
                      //alert(data[1]);
                    }
                  // alert('Files and message have been saved.');
                //   console.log(data);
                  // $('#whatsappMSG').val('');
                  
                  displaychatpanel(id);
                  
                  $(".uploaded-file-preview").removeAttr('style');
                  $(".uploaded-file-preview").removeClass('uploadedImg');
                  // $(".message-chat-box").trigger("click");
                  $('#attach-image').val('');
                  $(".emoji-editor").text('');
                  heightScroll();
                  $('.send-btn').prop('disabled', false);
                },
                error: function() {
                    console.log('Error fetching data from sendMessage.');
                    $('.send-btn').prop('disabled', false);
                }
            });

            
    }

    $(document).on('click', '.uploaded-img', function () {
      
      if (!$(this).parent().is(".pop-up-img")) {
        // $(this).unwrap();
        $('.chatting-box .nav.chatting li span').css("position", 'static');
        $(this).wrap("<div class='pop-up-img'>");
      }
      // console.log(removePop);
    });

    $(document).on('click', '.pop-up-img', function () {
      $('.chatting-box .nav.chatting li span').css("position", 'relative');
      $(this).find('.uploaded-img').unwrap();
    });

    function generateUniqueId() {
      return Math.floor(100000 + Math.random() * 900000); // Generates a random 6-digit number
    }

    
    function updateChatPanel(id) {
      // console.log('id ---> '+id);
      // console.log('id: ' + id); // Output: id: 56
      // $('#chat_id').val(id);
      var count_id = '#count_'+id;
      $(count_id).css('display', 'none');
      $('#chatbox-container').css('display', 'flex');
      $('#chatbox-dispo').css('display', 'flex');
      var client_id = $('#client_id').val();
      var campaign_id = $('#campaign_id').val();
      
      
      $.ajax({
                type: 'GET',
                url: "{{ url('/fetch_chatdata_latest') }}",
                data: {
                    id: id, client_id: client_id,campaign_id: campaign_id 
                },
                success: function(data) {
                  // console.log('update fetch_chatdata');
                  // console.log('=============================== ');
                  // console.log('newchats ==> '+data.newchats[0].is_closed);
                  if(data.newchats[0].is_closed==2){
                    location.reload();
                  }
                  // console.log('data '+ data.newchatLogsCount);
                  // var oldCount;
                  if(myflag==0){
                     oldCount = data.newchatLogsCount;
                     myflag=1;
                  }
                    var newCount = data.newchatLogsCount;
                    // console.log('oldCount '+ oldCount);
                    // console.log('newCount '+ newCount);
                    if(newCount>oldCount){
                      // console.log('new count is > old count ');
                    
                    
                    
                    var newchats = data.newchats;
                    
                    var newchatLogs = data.newchatLogs;
                    var timestampArray = data.timestampArray;
                    var dispositionData = data.dispositionData;
                    var maxLevel = data.maxLevel;
                    // console.log('maxLevel '+ maxLevel);
                    $('.customer_name_show').text(newchats[0].customer_name);
                    $('.mobile_number_show').text(newchats[0].cust_unique_id);
                    $('#client_name').val(newchats[0].client_name);
                    $('#campaign_name').val(newchats[0].campaign_name);
                    $('#customer_name').val(newchats[0].customer_name);
                    $('#campaign_id').val(newchats[0].campaign_id);
                    $('#client_id').val(newchats[0].client_id);
                    $('#mobile_number').val(newchats[0].cust_unique_id);
                    

                    var divider = 1;
                    $('#msgList').empty();

                    var previousDate = null; // Variable to store the previous date

                    for (var i = 0; i < newchatLogs.length; i++) {
                      var firstTenDigits = timestampArray[i].timestamp;
                      var chatDateTime = firstTenDigits.substring(0, 10);
                        // var chatDateTime = timestampArray[i].timestamp;
                        var date = new Date(chatDateTime);
                        var currentDate = new Date();
                        var msgdate = ''; // Initialize msgdate as an empty string

                        // Function to check if the date is within the current week
                        function isThisWeek(date) {
                            var today = currentDate;
                            var todayDayNumber = today.getDay();
                            var thisWeekStart = new Date(today.getFullYear(), today.getMonth(), today.getDate() - todayDayNumber);
                            var thisWeekEnd = new Date(thisWeekStart.getFullYear(), thisWeekStart.getMonth(), thisWeekStart.getDate() + 6);

                            return date >= thisWeekStart && date <= thisWeekEnd;
                        }

                        // Check conditions and assign msgdate accordingly
                        if (date.toDateString() === currentDate.toDateString()) {
                            msgdate = 'Today';
                        } else if (date.toDateString() === new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate() - 1).toDateString()) {
                            msgdate = 'Yesterday';
                        } else if (isThisWeek(date)) {
                            var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            msgdate = days[date.getDay()];
                        } else if (date < currentDate) {
                            var options = { year: 'numeric', month: 'long', day: 'numeric' };
                            msgdate = date.toLocaleDateString(undefined, options);
                        }

                        // Display the date only if it's different from the previous one
                        if(msgdate){
                          if (msgdate !== previousDate) {
                              $('#msgList').append('<li class="conversation-block"><div class="conversation-date">' + msgdate + '</div></li>');
                              previousDate = msgdate; // Update the previousDate variable
                          }
                        }
                        
                        var options = { timeZone: 'Asia/Kolkata' };

                        // Format chatDate as "Month Day, Year" in the Indian time zone
                        var chatDate = new Intl.DateTimeFormat('en-US', { year: 'numeric', month: 'long', day: '2-digit', timeZone: options.timeZone }).format(date);

                        // Format chatTime as "hh:mm AM/PM" in the Indian time zone
                        var chatTime = new Intl.DateTimeFormat('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: options.timeZone }).format(date);

                        var checkToday=0;
                        var html;
                        var message;
                      
                        if (checkToday == 1 && divider == 1 ) {
                            // $('#msgList').append('<li class="conversation-block"><div class="conversation-date">Today</div></li>');
                            divider = 0;
                        }else{
                          // $('#msgList').append('<li class="conversation-block"><div class="conversation-date">' + msgdate + '</div></li>');
                        }

                        if(newchatLogs[i].message !='' && newchatLogs[i].message !=null){
                            message =newchatLogs[i].message;
                            if (newchatLogs[i].in_out == 2) {
                              html = '<li class="sender">' +  
                                        '<span class="sender-text">' + message + 
                                          '<div class="time">' + chatTime + '<i class="bi bi-check-all chat-read"></i>' +
                                          '</div></span></li>';
                            
                            } else {

                              
                              html = '<li class="received">' +  
                                        '<span class="received-text">' + message + 
                                          '<div class="time">' + chatTime + '<i class="bi bi-check-all chat-read"></i>' +
                                          '</div></span></li>';
                               
                            }
                            $('#msgList').append(html);
                        }
                        
                        if(newchatLogs[i].media_path !='' && newchatLogs[i].media_path !=null){
                            message =newchatLogs[i].media_path;
                            var filename = message.substring(message.lastIndexOf("/") + 1);
                            var fileExtension = filename.split('.').pop();
                            var uniqueId = generateUniqueId();
                            var newImgId = filename + uniqueId;
                            var docImage = 'doc.png';
                            var docImagePath = '{{ asset('assets/agent_ui/img/') }}'+ '/' + docImage;
                            if (newchatLogs[i].in_out == 2) {
                                    if (["jpg", "jpeg", "png", "gif"].includes(fileExtension)) {

                                        html = '<li class="sender">' + 
                                        '<span class="sender-text">' + 
                                        '<img id="' + newImgId + '" class="uploaded-img" src="' + message +'">' +  
                                        '<div class="time 04">' + chatTime + '<i class="bi bi-check-all chat-read"></i></div></span></li>';
                                       
                                    } else {

                                      if (["mp4"].includes(fileExtension)) {
                                        html = '<li class="sender">' +
                                        '<span class="sender-text">' +
                                        '<video width="320" height="240" controls>' +
                                        '<source src="' + message + '" type="video/mp4">' +
                                        'Your browser does not support the video tag.' +
                                        '</video>' +
                                        '<div class="time 05">' + chatTime + ' <i class="bi bi-check-all chat-read"></i></div>' +
                                        '</span></li>';
                                      }else if(["m4a", "mp3", "ogg"].includes(fileExtension)){
                                        html = '<li class="sender">' +
                                        '<span class="sender-text">' +
                                        '<audio width="320" height="240" controls>' +
                                        '<source src="' + message + '" type="audio/ogg">' +
                                        'Your browser does not support the audio element.' +
                                        '</audio>' +
                                        '<div class="time 06">' + chatTime + ' <i class="bi bi-check-all chat-read"></i></div>' +
                                        '</span></li>';
                                      }else{
                                        html = '<li class="sender">' + 
                                        '<span class="sender-text">' + 
                                        '<a href="' + message + '" download="' + message + '">' +  
                                        '<img src="'+docImagePath+'" alt="'+docImage+'">' + filename +
                                        '</a> <div class="time 07">' + chatTime + ' <i class="bi bi-check-all chat-read"></i></div></span></li>';
                                      }
                                      
                                    }

                              }else{
                                    if (["jpg", "jpeg", "png", "gif"].includes(fileExtension)) {
                                      html = '<li class="received">' + 
                                        '<span class="received-text">' + 
                                        '<img id="' + newImgId + '" class="uploaded-img" src="' + message +'">' +  
                                        '<div class="time 08">' + chatTime + '<i class="bi bi-check-all chat-read"></i></div></span></li>';
                                    } else {

                                        if (["mp4"].includes(fileExtension)) {
                                          html = '<li class="received">' +
                                          '<span class="received-text">' +
                                          '<video width="320" height="240" controls>' +
                                          '<source src="' + message + '" type="video/mp4">' +
                                          'Your browser does not support the video tag.' +
                                          '</video>' +
                                          '<div class="time 09">' + chatTime + ' <i class="bi bi-check-all chat-read"></i></div>' +
                                          '</span></li>';
                                        }else if(["m4a", "mp3", "ogg"].includes(fileExtension)){
                                          html = '<li class="received">' +
                                          '<span class="received-text">' +
                                          '<audio width="320" height="240" controls>' +
                                          '<source src="' + message + '" type="audio/ogg">' +
                                          'Your browser does not support the audio element.' +
                                          '</audio>' +
                                          '<div class="time 10">' + chatTime + ' <i class="bi bi-check-all chat-read"></i></div>' +
                                          '</span></li>';
                                        }else{
                                          html = '<li class="received">' + 
                                          '<span class="received-text">' + 
                                          '<a href="' + message + '" download="' + message + '">' +  
                                          '<img src="'+docImagePath+'" alt="'+docImage+'">'
                                          '</a> <div class="time 11">' + chatTime + ' <i class="bi bi-check-all chat-read"></i></div></span></li>';
                                        }
                                      
                                    }
                                }
                                $('#msgList').append(html);
                        }

                        

                        
                        // $('[data-id="' + id + '"]').addClass('active');
                    }
                    // console.log('dispo code');
                    // console.log(dispositionData);
                    // if (dispositionData.length > 0) {
                    //   var selectElement = $('#dispo');

                    //   // Clear existing options except the 'Select' option
                    //   selectElement.find('option:not(:first)').remove();

                    //   // Iterate through the dispositionData array
                    //   dispositionData.forEach(function(item) {
                    //     // console.log(item.dispocode);
                    //     // Create an option element
                    //     var option = $('<option></option>');

                    //     // Set the value attribute of the option tag
                    //     // option.attr('value', item.dispocode);
                    //     option.attr('value', item.disponame);
                    //     // Set the text inside the option tag
                    //     option.text(item.disponame);

                    //     // Append the option to the select element
                    //     selectElement.append(option);
                    //   });
                    // }


                    // hideLoader();
                    heightScroll();
                    oldCount = newCount;
                  }
                },
                error: function() {
                    console.log('Error fetching data from fetch_chatdata_latest');
                }
            });
    }
 
   
    // left side bar click  or open chat function call
    // $(".message-chat-box").click(function () {
      var myIntervals;
    function displaychatpanel(id){
      myflag=0;
      oldCount=0;
    //  console.log('myIntervals '+myIntervals);
     clearInterval(myIntervals);
      updateChatPanel(id);
      myIntervals = setInterval(function() {
        updateChatPanel(id);
      }, 1000);

    
      $('.message-chat-box').removeClass("active");
      $('[data-id="' + id + '"]').addClass('active');
      // Function to show loader
      function showLoader() {
        $('#loader').show();
      }

      // Function to hide loader
      function hideLoader() {
        $('#loader').hide();
      }

      // Show loader immediately when the document is ready
      // showLoader();

      // Event handler when the page has finished loading
      $(window).on('load', function() {
        hideLoader(); // Hide loader when the page has loaded completely
      });

   
      
      
      // id.classList.add('active');
      $('#chat_id').val(id);
      var count_id = '#count_'+id;
      $(count_id).css('display', 'none');
      $('#chatbox-container').css('display', 'flex');
      $('#chatbox-dispo').css('display', 'flex');
      var client_id = $('#client_id').val();
      var campaign_id = $('#campaign_id').val();
      
      $.ajax({
                type: 'GET',
                url: "{{ url('/fetch_chatdata') }}",
                data: {
                    id: id, client_id: client_id,campaign_id: campaign_id 
                },
                success: function(data) {
                 
                    var newchats = data.newchats;
                    var newchatLogs = data.newchatLogs;
                    var timestampArray = data.timestampArray;
                    var dispositionData = data.dispositionData;
                    var maxLevel = data.maxLevel;
                    // console.log('maxLevel '+ maxLevel);
                    $('.customer_name_show').text(newchats[0].customer_name);
                    $('.mobile_number_show').text(newchats[0].cust_unique_id);
                    $('#client_name').val(newchats[0].client_name);
                    $('#campaign_name').val(newchats[0].campaign_name);
                    $('#customer_name').val(newchats[0].customer_name);
                    $('#campaign_id').val(newchats[0].campaign_id);
                    $('#client_id').val(newchats[0].client_id);
                    $('#mobile_number').val(newchats[0].cust_unique_id);
                    

                    var divider = 1;
                    $('#msgList').empty();

                    var previousDate = null; // Variable to store the previous date

                    for (var i = 0; i < newchatLogs.length; i++) {
                        // var chatDateTime = timestampArray[i].timestamp;
                        var firstTenDigits = timestampArray[i].timestamp;
                        // console.log('firstTenDigits : '+ firstTenDigits);
                      // var chatDateTime = firstTenDigits.substring(0, 10);
                      // console.log('chatDateTime : '+ chatDateTime);
                        var date = new Date(firstTenDigits);
                        // console.log('date : '+ date);
                        var currentDate = new Date();
                        var msgdate = ''; // Initialize msgdate as an empty string

                        // Function to check if the date is within the current week
                        function isThisWeek(date) {
                            var today = currentDate;
                            var todayDayNumber = today.getDay();
                            var thisWeekStart = new Date(today.getFullYear(), today.getMonth(), today.getDate() - todayDayNumber);
                            var thisWeekEnd = new Date(thisWeekStart.getFullYear(), thisWeekStart.getMonth(), thisWeekStart.getDate() + 6);

                            return date >= thisWeekStart && date <= thisWeekEnd;
                        }

                        // Check conditions and assign msgdate accordingly
                        if (date.toDateString() === currentDate.toDateString()) {
                            msgdate = 'Today';
                        } else if (date.toDateString() === new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate() - 1).toDateString()) {
                            msgdate = 'Yesterday';
                        } else if (isThisWeek(date)) {
                            var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            msgdate = days[date.getDay()];
                        } else if (date < currentDate) {
                            var options = { year: 'numeric', month: 'long', day: 'numeric' };
                            msgdate = date.toLocaleDateString(undefined, options);
                        }

                        // Display the date only if it's different from the previous one
                        if(msgdate){
                          if (msgdate !== previousDate) {
                              $('#msgList').append('<li class="conversation-block"><div class="conversation-date">' + msgdate + '</div></li>');
                              previousDate = msgdate; // Update the previousDate variable
                          }
                        }
                        
                        var options = { timeZone: 'Asia/Kolkata' };

                        // Format chatDate as "Month Day, Year" in the Indian time zone
                        var chatDate = new Intl.DateTimeFormat('en-US', { year: 'numeric', month: 'long', day: '2-digit', timeZone: options.timeZone }).format(date);

                        // Format chatTime as "hh:mm AM/PM" in the Indian time zone
                        // var chatTime = new Intl.DateTimeFormat('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: options.timeZone }).format(date);
                        var options = { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'Asia/Kolkata' };
                        var chatTime = new Intl.DateTimeFormat('en-US', options).format(date);
                        // console.log('chatTime : '+ chatTime);
                        var checkToday=0;
                        var html;
                        var message;
                      
                        if (checkToday == 1 && divider == 1 ) {
                            // $('#msgList').append('<li class="conversation-block"><div class="conversation-date">Today</div></li>');
                            divider = 0;
                        }else{
                          // $('#msgList').append('<li class="conversation-block"><div class="conversation-date">' + msgdate + '</div></li>');
                        }

                        if(newchatLogs[i].message !='' && newchatLogs[i].message !=null){
                            message =newchatLogs[i].message;
                            if (newchatLogs[i].in_out == 2) {
                              html = '<li class="sender">' +  
                                        '<span class="sender-text">' + message + 
                                          '<div class="time 12">' + chatTime + '<i class="bi bi-check-all chat-read"></i>' +
                                          '</div></span></li>';
                            
                            } else {

                              
                              html = '<li class="received">' +  
                                        '<span class="received-text">' + message + 
                                          '<div class="time 13">' + chatTime + '<i class="bi bi-check-all chat-read"></i>' +
                                          '</div></span></li>';
                               
                            }
                            $('#msgList').append(html);
                        }
                        
                        if(newchatLogs[i].media_path !='' && newchatLogs[i].media_path !=null){
                            message =newchatLogs[i].media_path;
                            var filename = message.substring(message.lastIndexOf("/") + 1);
                            var fileExtension = filename.split('.').pop();
                            var uniqueId = generateUniqueId();
                            var newImgId = filename + uniqueId;
                            var docImage = 'doc.png';
                            var docImagePath = '{{ asset('assets/agent_ui/img/') }}'+ '/' + docImage;
                            if (newchatLogs[i].in_out == 2) {
                                    if (["jpg", "jpeg", "png", "gif"].includes(fileExtension)) {

                                        html = '<li class="sender">' + 
                                        '<span class="sender-text">' + 
                                        '<img id="' + newImgId + '" class="uploaded-img" src="' + message +'">' +  
                                        '<div class="time 14">' + chatTime + '<i class="bi bi-check-all chat-read"></i></div></span></li>';
                                       
                                    } else {

                                      if (["mp4"].includes(fileExtension)) {
                                        html = '<li class="sender">' +
                                        '<span class="sender-text">' +
                                        '<video width="320" height="240" controls>' +
                                        '<source src="' + message + '" type="video/mp4">' +
                                        'Your browser does not support the video tag.' +
                                        '</video>' +
                                        '<div class="time 15">' + chatTime + ' <i class="bi bi-check-all chat-read"></i></div>' +
                                        '</span></li>';
                                      }else if(["m4a", "mp3", "ogg"].includes(fileExtension)){
                                        html = '<li class="sender">' +
                                        '<span class="sender-text">' +
                                        '<audio width="320" height="240" controls>' +
                                        '<source src="' + message + '" type="audio/ogg">' +
                                        'Your browser does not support the audio element.' +
                                        '</audio>' +
                                        '<div class="time 16">' + chatTime + ' <i class="bi bi-check-all chat-read"></i></div>' +
                                        '</span></li>';
                                      }else{
                                        html = '<li class="sender">' + 
                                        '<span class="sender-text">' + 
                                        '<a href="' + message + '" download="' + message + '">' +  
                                        '<img src="'+docImagePath+'" alt="'+docImage+'">' + filename +
                                        '</a> <div class="time 17">' + chatTime + ' <i class="bi bi-check-all chat-read"></i></div></span></li>';
                                      }
                                      
                                    }

                              }else{
                                    if (["jpg", "jpeg", "png", "gif"].includes(fileExtension)) {
                                      html = '<li class="received">' + 
                                        '<span class="received-text">' + 
                                        '<img id="' + newImgId + '" class="uploaded-img" src="' + message +'">' +  
                                        '<div class="time 18">' + chatTime + '<i class="bi bi-check-all chat-read"></i></div></span></li>';
                                    } else {

                                      if (["mp4"].includes(fileExtension)) {
                                          html = '<li class="received">' +
                                          '<span class="received-text">' +
                                          '<video width="320" height="240" controls>' +
                                          '<source src="' + message + '" type="video/mp4">' +
                                          'Your browser does not support the video tag.' +
                                          '</video>' +
                                          '<div class="time 19">' + chatTime + ' <i class="bi bi-check-all chat-read"></i></div>' +
                                          '</span></li>';
                                        }else if(["m4a", "mp3", "ogg"].includes(fileExtension)){
                                          html = '<li class="received">' +
                                          '<span class="received-text">' +
                                          '<audio width="320" height="240" controls>' +
                                          '<source src="' + message + '" type="audio/ogg">' +
                                          'Your browser does not support the audio element.' +
                                          '</audio>' +
                                          '<div class="time 20">' + chatTime + ' <i class="bi bi-check-all chat-read"></i></div>' +
                                          '</span></li>';
                                        }else{
                                          html = '<li class="received">' + 
                                          '<span class="received-text">' + 
                                          '<a href="' + message + '" download="' + message + '">' +  
                                          '<img src="'+docImagePath+'" alt="'+docImage+'">'
                                          '</a> <div class="time 21">' + chatTime + ' <i class="bi bi-check-all chat-read"></i></div></span></li>';
                                        }
                                      
                                    }
                                }
                                $('#msgList').append(html);
                        }

                        

                        
                        // $('[data-id="' + id + '"]').addClass('active');
                    }
                    
                    if (dispositionData.length > 0) {
                      
                      var selectElement = $('#dispo');

                      // Clear existing options except the 'Select' option
                      selectElement.find('option:not(:first)').remove();

                      // Iterate through the dispositionData array
                      dispositionData.forEach(function(item) {
                        // console.log(item.dispocode);
                        // Create an option element
                        $('#planid').val(item.planid);
                        var option = $('<option></option>');

                        // Set the value attribute of the option tag
                        // option.attr('value', item.dispocode);
                        option.attr('value', item.disponame);
                        // Set the text inside the option tag
                        option.text(item.disponame);

                        // Append the option to the select element
                        selectElement.append(option);
                      });
                    }

                    if(maxLevel==3){
                      $("#Level3Disposition").show();
                    }

                    // if(maxLevel==2){
                    //   $("#Level2Disposition").show();
                    // }elseif(maxLevel==3){
                    //   $("#Level2Disposition").show();
                    //   $("#Level3Disposition").show();
                    // }else{
                    //   // do nothing 
                    // }

                    hideLoader();
                    heightScroll();
                },
                error: function() {
                    
                    console.log('Error fetching data from displaychatpanel.');
                }
            });
	
            // ajax end 
        

      $(".chat-send-box").css('display', 'flex');
      $(".sidebar-nav.chat-list").removeClass('single-col-width');
      $(".sidebar-nav.chat-info").css('display', 'flex');
    // setInterval(function() {
    //   updateChatPanel(id);
    // }, 1000);
      

  }

  // $('#closeBreakModal').click(function() {
  //   $(".not-ready-reason-container").hide();
  // });

  $(document).on('click', '#closeBreakModal', function() {
    
    $(".not-ready-reason-container").hide();
});

  $('#clearSearch').click(function() {
    $('#customer_list_default').css('display', 'block');
        $('#customer_list').css('display', 'none');
        $("#clearSearch").css('opacity', 0);
        $('#searchInput').val('');
  });

    $('#searchInput').keyup(function() {
      
      
      $("#clearSearch").css('opacity', 1);
      // Get the value entered in the search input
      var searchcust = $(this).val();
      if(searchcust!=''){
        $('#customer_list_default').css('display', 'none');
        $('#customer_list').css('display', 'block');
      }else{
        $('#customer_list_default').css('display', 'block');
        $('#customer_list').css('display', 'none');
        $("#clearSearch").css('opacity', 0);
      }
      var csrfToken = $('meta[name="csrf-token"]').attr('content');
      
      // Perform AJAX request
      $.ajax({
        url: "{{ url('/search_chats') }}", // Replace with your server-side search endpoint
        type: 'GET',
        data: { searchcust: searchcust, csrfToken: csrfToken },
        success: function(data) {
          // console.log('search_chats checking');
          var newchats = data.newchats;
          // console.log('newchats '+newchats[0].interaction_per_user);
          // console.log('mahesh');
          var chatlogArray = data.chatlogArray;
          var chatlogArray2 = data.chatlogArray2;
          // console.log(chatlogArray2[0].chatTime);
            $('#customer_list').empty();
            for (var i = 0; i < newchats[0].interaction_per_user; i++) {

              //start code 15 jan 2024
              var chatDateTime = chatlogArray2[0].timestamp * 1000;
                        // console.log('chatDateTime ==> '+chatDateTime);
                        var date = new Date(chatDateTime);
                        // console.log('date ==> '+date);
                        var currentDate = new Date();
                        var msgdate = ''; // Initialize msgdate as an empty string
                        var previousDate = null; // Variable to store the previous date

                        // Function to check if the date is within the current week
                        function isThisWeek(date) {
                            var today = currentDate;
                            var todayDayNumber = today.getDay();
                            var thisWeekStart = new Date(today.getFullYear(), today.getMonth(), today.getDate() - todayDayNumber);
                            var thisWeekEnd = new Date(thisWeekStart.getFullYear(), thisWeekStart.getMonth(), thisWeekStart.getDate() + 6);

                            return date >= thisWeekStart && date <= thisWeekEnd;
                        }

                        // Check conditions and assign msgdate accordingly
                        if (date.toDateString() === currentDate.toDateString()) {
                            msgdate = 'Today';
                        } else if (date.toDateString() === new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate() - 1).toDateString()) {
                            msgdate = 'Yesterday';
                        } else if (isThisWeek(date)) {
                            var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            msgdate = days[date.getDay()];
                        } else if (date < currentDate) {
                            var options = { year: 'numeric', month: 'long', day: 'numeric' };
                            msgdate = date.toLocaleDateString(undefined, options);
                        }
                        console.log('msgdate ==> '+msgdate);
                        var chattimedisplay ='';
                        // Display the date only if it's different from the previous one
                        if(msgdate){
                          console.log('msgdate2 ==> '+msgdate);
                          if (msgdate !== previousDate) {
                            console.log('msgdate3 ==> '+msgdate);
                            console.log('previousDate ==> '+previousDate);
                              chattimedisplay = '<div class="time 22">' + msgdate + '</div>';
                              // console.log('chattimedisplay inside ==> '+chattimedisplay);
                              previousDate = msgdate; // Update the previousDate variable
                              console.log('previousDate 2 ==> '+previousDate);
                          }
                        }else{
                              chattimedisplay = '<div class="time 22">' + chatlogArray2[i].chatTime + '</div>';
                        }
                        // console.log('chattimedisplay ==> '+chattimedisplay);
                        //End Code 15 jan 2024

              html = '<div data-id="' + newchats[i].id + '" class="message-chat-box whatsapp-chat" onclick="displaychatpanel(' + newchats[i].id + ')" >' + 
                          '<div class="message-body">' +
                            '<div class="left-side">' +
                              '<div class="profile-pic"></div>' +
                            '</div>' +
                            '<div class="right-side">' +
                              '<div class="mr-auto">' +
                                '<h4>' + newchats[i].customer_name  + '</h4>' +
                                '<p class="chatMsg" style="display: none;">' +
                                '</p>' +
                                '<p>' + chatlogArray2[i].msg + '</p>' +
                              '</div>' +
                              '<div class="text-right count-time-box">' +
                              chattimedisplay
                                  
                              if (chatlogArray[newchats[i].id] > 0) {
                                  html += '<div class="count 2" id="count_' + newchats[i].id + '">'+ chatlogArray[newchats[i].id] +'</div>'; 
                                  //$(".notification-pop").show();
                                }

                                html += '</div>';
                            '</div>' +
                              '</div>' +
                            '</div>' +
                          '</div>' +
                        '</div>';
                        $('#customer_list').append(html);
            }
            
          },
          error: function() {
            console.log('Error fetching data from searchInput.');
          }
      });
    });
    
    $('#customer_list').on('click', '.message-chat-box', function() {
      var chatId = $(this).data('id');
      displaychatpanel(chatId);
    });

//     setInterval(function() {
//     $('#customer_list').on('click', '.message-chat-box', function() {
//         var chatId = $(this).data('id');
//         displaychatpanel(chatId);
//     }).trigger('click');
// }, 1000);

    $('#dispo').change(function() {
    // Get the selected value
    var client_id = $('#client_id').val();
    var campaign_id = $('#campaign_id').val();
    var planid = $('#planid').val();
    var disponame = $(this).val();
      $.ajax({
          type: 'GET',
          url: "{{ url('/get_sub_dispo') }}",
          data: {
            disponame: disponame, client_id: client_id, campaign_id: campaign_id, planid: planid
          },
          success: function(data) {
            var subDispositionData = data.subDispositionData;
            // console.log(subDispositionData);
            $('#sub_dispo').empty();
              if(subDispositionData.length > 0){
                  var selectElement = $('#sub_dispo');

                  // Clear existing options except the 'Select' option
                  selectElement.find('option:not(:first)').remove();

                  // Iterate through the dispositionData array
                  subDispositionData.forEach(function(item) {
                    // Create an option element
                    var option = $('<option></option>');

                    // Set the value attribute of the option tag
                    option.attr('value', item.disponame);

                    // Set the text inside the option tag
                    option.text(item.disponame);

                    // Append the option to the select element
                    selectElement.append(option);
                  });
              }else {
                  // If there is no sub disposition data, append 'No sub disposition'
                  $('#sub_dispo').append('<option value="">No sub disposition</option>');
              }
            
          },
          error: function() {
              
              console.log('Error fetching data from dispo.');
          }
      });

  });

  $('#sub_dispo').change(function() {
    // Get the selected value
    var client_id = $('#client_id').val();
    var campaign_id = $('#campaign_id').val();
    var planid = $('#planid').val();
    var subdisponame = $(this).val();
      $.ajax({
          type: 'GET',
          url: "{{ url('/get_sub_sub_dispo') }}",
          data: {
            subdisponame: subdisponame, client_id: client_id, campaign_id: campaign_id, planid: planid
          },
          success: function(data) {
            var subsubDispositionData = data.subsubDispositionData;
            // console.log(subDispositionData);
            $('#sub_sub_dispo').empty();
              if(subsubDispositionData.length > 0){
                  var selectElement = $('#sub_sub_dispo');

                  // Clear existing options except the 'Select' option
                  selectElement.find('option:not(:first)').remove();

                  // Iterate through the dispositionData array
                  subsubDispositionData.forEach(function(item) {
                    // Create an option element
                    var option = $('<option></option>');

                    // Set the value attribute of the option tag
                    option.attr('value', item.disponame);

                    // Set the text inside the option tag
                    option.text(item.disponame);

                    // Append the option to the select element
                    selectElement.append(option);
                  });
                }
            
          },
          error: function() {
            console.log('Error fetching data from sub_dispo.');
          }
      });

  });


  // Canned Response preview 20/11/2023
  $("#sendChatBtn").click(function () {
			$(".template-box").toggleClass("show-temp");
		});

    $(".template-content").click(function () {
      $(".template-content").removeClass("active");
      $(this).addClass("active");
      $(".template-btn-box .btn").removeClass("disabled");
		});

    // canned Response close 20/11/2023
    $("#closeTemplate").click(function () {
      $(".template-box").removeClass("show-temp");
    });

    // canned Response inserting to send message 20/11/2023
    $("#selectTemplate").click(function () {
      $(".emoji-editor").empty();
      var text = $(this).parents(".template-box").children().children().children('.template-content.active').text();
      $(".emoji-editor").append(text);
      $(".template-box").removeClass("show-temp");
    });

    // open canned and attachment btn 20/11/2023
    $('#moreFeatureBtn').click(function (){
      $(".mf-content").toggleClass("show");
    });

    $('.mf-content .mf-btn').click(function (){
      $('.mf-content').removeClass("show");
    });
    
    // Notification 07/12/2023
    // $(".notification-pop").addClass("pop-right-to-left").delay(8000).fadeOut(1000);
    
    $('#close_notification').click(function (){
      $(".notification-pop").hide();
  });


      
     
    

    $(document).ready(function() {
      refreshChatBar();
      



      function refreshChatBar() {
      
      
      // $("#clearSearch").css('opacity', 1);
      // Get the value entered in the search input
      var click_chat_id = $('#chat_id').val();
     
     
      var searchcust = 'ALL';
      if(searchcust!=''){
        $('#customer_list_default').css('display', 'none');
        $('#customer_list').css('display', 'block');
      }else{
        $('#customer_list_default').css('display', 'block');
        $('#customer_list').css('display', 'none');
        // $("#clearSearch").css('opacity', 0);
      }
      var csrfToken = $('meta[name="csrf-token"]').attr('content');
      
      // Perform AJAX request
      $.ajax({
        url: "{{ url('/search_chats') }}", // Replace with your server-side search endpoint
        type: 'GET',
        data: { searchcust: searchcust, csrfToken: csrfToken },
        success: function(data) {
            // console.log(click_chat_id);
          if (data.redirect) {
            // Redirect the user to the signout URL received from the server
            window.location.href = data.url;
        } else {
            // Handle other logic if needed
        
          var newchats = data.newchats;
          if(newchats && newchats.length > 0){

          
              var chatidcheck = data.chatid;
              // console.log('newchats '+newchats[0].id);
              var chatlogArray = data.chatlogArray;
              var chatlogArray2 = data.chatlogArray2;
              // console.log('check id ===>'+newchats[id]);
              // console.log('timestamp '+chatlogArray2[0].timestamp);
              if(newchats.length>= newchats[0].interaction_per_user){
                datalength = newchats.length;
              }else{
                datalength = newchats[0].interaction_per_user;
              }
             $('#customer_list').empty();
                for (var i = 0; i < 9; i++) {

                        //start code 15 jan 2024
                        var chatDateTime = chatlogArray2[i].timestamp * 1000;
                        var date = new Date(chatDateTime);
                        var currentDate = new Date();
                        var msgdate = ''; // Initialize msgdate as an empty string
                        var previousDate = null; // Variable to store the previous date

                        // Function to check if the date is within the current week
                        function isThisWeek(date) {
                            var today = currentDate;
                            var todayDayNumber = today.getDay();
                            var thisWeekStart = new Date(today.getFullYear(), today.getMonth(), today.getDate() - todayDayNumber);
                            var thisWeekEnd = new Date(thisWeekStart.getFullYear(), thisWeekStart.getMonth(), thisWeekStart.getDate() + 6);

                            return date >= thisWeekStart && date <= thisWeekEnd;
                        }

                        // Check conditions and assign msgdate accordingly
                        if (date.toDateString() === currentDate.toDateString()) {
                            // If the date is today, display only the time
                            var timeString = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            msgdate = timeString;
                        } else if (date.toDateString() === new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate() - 1).toDateString()) {
                            // If the date is yesterday, display 'Yesterday'
                            msgdate = 'Yesterday';
                        } else if (isThisWeek(date)) {
                            // If the date is within the current week, display the date in the format 'mm/dd/yy'
                            msgdate = (date.getMonth() + 1).toString().padStart(2, '0') + '/' + date.getDate().toString().padStart(2, '0') + '/' + date.getFullYear().toString().slice(2);
                        } else if (date < currentDate) {
                            // If the date is before the current week, display the date in the format 'YY/MM/DD'
                            // var formattedDate = date.toLocaleDateString('en-GB').split('/').reverse().join('/');
                            // msgdate = formattedDate.slice(2);
                            msgdate = (date.getMonth() + 1).toString().padStart(2, '0') + '/' + date.getDate().toString().padStart(2, '0') + '/' + date.getFullYear().toString().slice(2);
                        }

                        var chattimedisplay = '';
                        // Display the date only if it's different from the previous one
                        if (msgdate) {
                            if (msgdate !== previousDate) {
                                chattimedisplay = '<div class="time 23">' + msgdate + '</div>';
                                previousDate = msgdate; // Update the previousDate variable
                            }
                        } else {
                            chattimedisplay = '<div class="time 23">' + chatlogArray2[i].chatTime + '</div>';
                        }

                        // console.log('chattimedisplay ==> '+chattimedisplay);
                        //End Code 15 jan 2024
                  
                  var activeClass = "";
                
                  if(click_chat_id!='' && click_chat_id==newchats[i].id){
                    activeClass = "active";
                  }
                  
                  // else{
                  //   $('#chatbox-container').css('display', 'none');
                  //     $('#chatbox-dispo').css('display', 'none');
                  // }
                  // console.log('chatid : '+click_chat_id);
                  // console.log('id : '+newchats[i].id);
                  // console.log('activeClass : '+activeClass);
                  html = '<div data-id="' + newchats[i].id + '" class="message-chat-box whatsapp-chat ' + activeClass + ' " onclick="displaychatpanel(' + newchats[i].id + ')" >' + 
                              '<div class="message-body">' +
                                '<div class="left-side">' +
                                  '<div class="profile-pic"></div>' +
                                '</div>' +
                                '<div class="right-side">' +
                                  '<div class="mr-auto">' +
                                    '<h4>' + newchats[i].customer_name  + '</h4>' +
                                    '<p class="chatMsg" style="display: none;">' +
                                    '</p>' +
                                    '<p>' + chatlogArray2[i].msg + '</p>' +
                                  '</div>' +
                                  '<div class="text-right count-time-box">' +
                                  chattimedisplay
                                      
                                  if (chatlogArray[newchats[i].id] > 0) {
                                      html += '<div class="count 3" id="count_' + newchats[i].id + '">'+ chatlogArray[newchats[i].id] +'</div>'; 
                                      // $(".notification-pop").show();
                                    }

                                    html += '</div>';
                                '</div>' +
                                  '</div>' +
                                '</div>' +
                              '</div>' +
                            '</div>';
                            $('#customer_list').append(html);
                }
              }
            }
          },
          error: function() {
            console.log('Error fetching data from refreshChatBar.');
          }
      });

      setTimeout(() => {
        refreshChatBar();
    }, 5000);
    }




});



  </script>
  <script>



    $('#closeBreakModal').click(function() {
      $(".not-ready-reason-container").hide();
    });
    $(".action-btn").click(function(){
     
      if($(this).hasClass("readybtn")){
        $(this).removeClass('active-btn');
        $(".pasueBtn").addClass('active-btn');
      }else if($(this).hasClass("pasueBtn")){
        $(this).removeClass('active-btn');
        $('.readybtn').addClass('active-btn');
      }
    });
  $(document).on('click', '#readybtn', function () {
    
    
    
    $('.vaani_action').toggle();
    $('.vaani_action').toggleClass('disabled');
    $(".agent-status-box").addClass("agent-ready");
    $(".agent-status-box").removeClass("agent-login");
    // $("#cur_Status").text("Ready");
    $('.agent-status-box').removeClass("flip");
    $(".agent-status-box").removeClass("agent-pause");
        var breakId = 'R1';
        var breakName = 'Ready';
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            type: 'GET', // or 'GET'
            url: "{{ url('/store_break') }}", // Replace with your backend endpoint
            data: { breakId: breakId,  breakName: breakName,  csrfToken: csrfToken},
            success: function(response) {
                // Handle success
                console.log('AJAX success:', response);
                // Close the modal or perform other actions
                $("#cur_Status").text(breakName);
                // $("#break_status").text(' Ready');
                alert('You are on Ready');
            },
            error: function(xhr, status, error) {
                // Handle errors
                console.error('AJAX error:', status, error);
            }
        });
  });
  $(document).on('click', '#pasueBtn', function () {
    
    $(".close_break").show();
    $('.vaani_action').toggle();
    $('.vaani_action').toggleClass('disabled');
    // $("#cur_Status").text("Pause");
    $('.agent-status-box').addClass("flip");
    $(".agent-status-box").addClass("agent-pause");
    $(".agent-status-box").removeClass("agent-ready");
    $(".not-ready-reason-container").show();
  });

  $(document).on('change', '#pauseForm input[name=breakType]', function() {
  // $('input[name=breakType]').change(function() {
    $('.close_break').hide();
          var breakValue = $(this).val();
          var breakParts = breakValue.split('_');
          var csrfToken = $('meta[name="csrf-token"]').attr('content');
          if (breakParts.length === 2) {
              var breakId = breakParts[0];
              var breakName = breakParts[1]

              // AJAX call
              $.ajax({
                  type: 'GET', // or 'GET'
                  url: "{{ url('/store_break') }}", // Replace with your backend endpoint
                  data: { breakId: breakId,  breakName: breakName,  csrfToken: csrfToken},
                  success: function(response) {
                      // Handle success
                      console.log('AJAX success:', response);
                      // Close the modal or perform other actions
                      $('.not-ready-reason-container').hide();
                      $(this).removeClass('active-btn');
                      $('#readybtn').addClass('active-btn');
                      $('#pasueBtn').removeClass('active-btn');
                      
                      $("#cur_Status").text(breakName +' Break');
                      // $("#break_status").text(breakName +' Break');
                      alert('You are on '+ breakName +' Break');
                      if(breakName != 'Soft'){
                        window.location.href = "{{ url('/dashboard') }}";
                      }else{
                        window.location.reload();
                      }
                  },
                  error: function(xhr, status, error) {
                      // Handle errors
                      console.error('AJAX error:', status, error);
                  }
              });
            }
      });

      // timer  
      var timerInterval;
      var timerRunning = false;
      var startTime = 0;

      function startTimer() {
        startTime = Date.now() - (startTime > 0 ? startTime : 0);
        timerRunning = true;
        timerInterval = setInterval(updateTimer, 1000);
      }

      function stopTimer() {
        
        clearInterval(timerInterval);
        timerRunning = false;
        startTime = 0;
      }

      function updateTimer() {
        var currentTime = Date.now() - startTime;
        var hours = Math.floor(currentTime / 3600000);
        var minutes = Math.floor((currentTime % 3600000) / 60000);
        var seconds = Math.floor((currentTime % 60000) / 1000);

        // document.getElementById("cur_Statustimer").textContent =
        //   formatTime(hours) + ":" + formatTime(minutes) + ":" + formatTime(seconds);
      }

      function formatTime(time) {
        return time < 10 ? "0" + time : time;
      }

      document.getElementById("readybtn").addEventListener("click", function () {
        stopTimer();
        if (!timerRunning) {
          startTimer();
        }
      });

      document.getElementById("pasueBtn").addEventListener("click", function () {
        stopTimer();
        if (!timerRunning) {
          startTimer();
        }
        
      });



// Loader code 

$(document).ready(function() {

// Function to show loader
function showWindowLoader() {
  $('#windowLoader').show();
}

// Function to hide loader
function hideWindowLoader() {
  $('#windowLoader').hide();
}

// Show loader immediately when the document is ready
showWindowLoader();

// Event handler when the page has finished loading
$(window).on('load', function() {
  hideWindowLoader(); // Hide loader when the page has loaded completely
});


});


$(document).ready(function() {
  
  var checkLoginAgentID=0;
  var checkLoginAgentRole=0;
  var checkLoginAgentClient=0;
// Run the check every second


  checkAgentIsLogin();
  refreshBreakTime();
      
  
  

      function refreshBreakTime() {
      
      $.ajax({
        url: "{{ url('/refresh_break_time') }}", // Replace with your server-side search endpoint
        type: 'GET',
        success: function(data) {
          var current_break = data.current_break;
          var break_name = data.break_name;
          // console.log(break_name);
          // console.log(current_break);
           if(break_name == 'Login'){
            // $("#cur_Status").text("Login");
           }else if(break_name == 'Ready'){
            $('.vaani_action').toggle();
            $('.vaani_action').toggleClass('disabled');
            $(".agent-status-box").addClass("agent-ready");
            $(".agent-status-box").removeClass("agent-login");
            // $("#cur_Status").text("Ready");
            $('.agent-status-box').removeClass("flip");
            $(".agent-status-box").removeClass("agent-pause");
            $(".readybtn").removeClass('active-btn');
            $(".pasueBtn").addClass('active-btn');
            // $('#pasueBtn').addClass('active-btn');
            // $('#readybtn').removeClass('active-btn');
           }else{
            $('.vaani_action').toggle();
            $('.vaani_action').toggleClass('disabled');
            // $("#cur_Status").text("Pause");
            $('.agent-status-box').addClass("flip");
            $(".agent-status-box").addClass("agent-pause");
            $(".agent-status-box").removeClass("agent-ready");
            $(".agent-status-box").removeClass("agent-login");
            $(".pasueBtn").removeClass('active-btn');
            $('.readybtn').addClass('active-btn');
            // $('#readybtn').addClass('active-btn');
            // $('#pasueBtn').removeClass('active-btn');
            // $('#pasueBtn').removeClass('active-btn');
           }
           $("#cur_Status").text(break_name);
           document.getElementById("cur_Statustimer").textContent = current_break;
            
          },
          error: function() {
              console.log('Error fetching data. from refresh_break_time');
          }
      });
      setTimeout(() => {
        refreshBreakTime();
    }, 1000);
     
    }



    var sessionFlag = 0;
    function checkAgentIsLogin() {
        $.ajax({
            url: "{{ url('/check_agent_is_login') }}",
            type: 'GET',
            success: function (data) {
             
                if (data.checkLogin !== 'login') {
                    if(checkLoginAgentID != 0 ){
                      // alert("You have been logged out. Someone is currently using this specified ID.");
                      forceAgentToSignout(checkLoginAgentID, checkLoginAgentRole, checkLoginAgentClient);
                    }else{
                      console.log('check_agent_is_login : ALL session variable is 0');
                    }
                    
                }else{
                   checkLoginAgentID = data.agentid;
                   checkLoginAgentRole = data.agentrole;
                   checkLoginAgentClient = data.clientid;
                }
            },
            error: function () {
                console.error('Error fetching data.from checkAgentIsLogin');
            }
        });
        setTimeout(checkAgentIsLogin, 1000);
    }

    function forceAgentToSignout(agentid, agentrole, clientid) {
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{ url('/signout') }}",
            type: 'GET', // Considering this is a logout action, use POST for more security.
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            data: { agentid: agentid, agentrole: agentrole, clientid: clientid },
            success: function (data) {
                // alert('Successfully logged out.');
                // alert("You have been logged out. \nSomeone is currently using this specified ID.");
                if(sessionFlag==0){
                  alert("Your session has expired. Please log in again.");
                  location.reload();
                  sessionFlag=1;
                }
                
            },
            error: function () {
                console.error('Error during forceAgentToSignout process.');
            }
        });
    }


});




    
     

</script>
@endsection
