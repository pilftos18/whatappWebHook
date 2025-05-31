<!DOCTYPE html>
<html lang="en">
  
  <link rel="stylesheet" href="{{asset('assets/socialmedia/css/bootstrap.min.css')}}">
  <link href="{{asset('assets/socialmedia/css/bootstrap-icons.css')}}" rel="stylesheet">
  <link rel="stylesheet" href="{{asset('assets/socialmedia/emoji/css/style.css')}}">
  <link rel="stylesheet" href="{{asset('assets/socialmedia/emoji/emoji-stylesheet.css')}}">
  <link rel="stylesheet" href="{{asset('assets/socialmedia/css/layout.css')}}">
  <link rel="stylesheet" href="{{asset('assets/socialmedia/css/work-sans-fonts.css')}}">
  <link rel="stylesheet" href="{{asset('assets/socialmedia/css/style.css')}}">
  <link rel="stylesheet" href="{{asset('assets/socialmedia/css/social-media.css')}}">
<body>
  <main>
    @php
    $currentDate = null; // Initialize a variable to keep track of the current date
    @endphp

    @foreach ($chat as $item)

          @php
    $timestamp = (string) $item->timestamp; // code by mahesh
    $firstTenDigits = substr($timestamp, 0, 10); // code by mahesh
   $messageDate = date('j F Y', $firstTenDigits);
    $today = date('j F Y');
    if ($messageDate != $currentDate) {
        $currentDate = $messageDate;
    } else {
        $messageDate = '';
    }
          @endphp
            <table width="100%" style="width:100%; border:1px solid #000;">
              @if ($messageDate)
              <tr>
                <td colspan="2" style="margin: 5px; padding: 10px;">
                    <div class="conversation-block" style="text-align: center;">
                        <div class="conversation-date">
                            @if ($messageDate == $today)
                                Today
                            @else
                                {{$messageDate}}
                            @endif
                        </div>
                    </div>
                </td>
              </tr>
                @endif
            <?php
              if($item->message !='' && $item->message !=null){
                  if ($item->in_out == 2) {
                      ?>
                  <tr>
                    <td style="margin: 5px; padding: 10px;">
                        <div class="sender" style="padding: 5px; margin-bottom:25px; display:block;"> <span class="sender-text" style="border: 1px solid ##EEE; float:right; display:inline-block;"> {{$item->message}} <div class="time">
                            <?php echo date('H:i A', $firstTenDigits); ?> <i class="fas fa-check-double"></i>
                          </div></span></div>
                    </td>
                    </tr>
                    <?php
                } else {
                    ?>  
                    <tr>
                  <td style="margin: 5px; padding: 10px;">
                    <div class="received" style="padding: 5px; margin-bottom:25px; display:block;"><span class="received-text" style="border: 1px solid ##EEE; float:left;display: inline-block;">{{$item->message}}<div class="time"><?php echo date('H:i A', $firstTenDigits); ?></div>
                    </span></div>
                  </td>
                  </tr>
                  <?php
                }
            }
            ?>
              <?php
              if($item->media_path !='' && $item->media_path !=null){
                  if ($item->in_out == 2) {
                      $ext = pathinfo( $item->media_path, PATHINFO_EXTENSION);
                      $extensions = ["jpg", "jpeg", "png", "gif"];
                      $mp4ext = ["mp4"];
                      $audioext = ["m4a", "mp3", "ogg"];
                      if(in_array($ext, $extensions))
                      {
                          ?>
                    <tr>
                    <td style="margin: 5px; padding: 10px;">
                        <div class="sender" style="padding: 5px; margin-bottom:25px; display:block;">
                            <span class="sender-text"  style="border: 1px solid ##EEE; float:right;display: inline-block;">
                              <div id="" class="uploaded-sender-img" href="{{$item->media_path}}"><i class="fa fa-download"></i>(<?php echo $ext="image";?>)</div>
                                    <div class="time"><?php echo date('H:i A', $firstTenDigits); ?><i class="fa fa-check-double"></i></div></span></div>
                    </td>
                    </tr>
                    <?php
                        }
                        elseif(in_array($ext, $mp4ext)){
                          ?>
                            <tr>
                              <td style="margin: 5px; padding: 10px;">
                                  <div class="sender" style="padding: 5px; margin-bottom:25px; display:block;">
                                      <span class="sender-text"  style="border: 1px solid ##EEE; float:right;display: inline-block;">
                                        <div class="downloadanchor" href="{{$item->media_path}}" download>
                                          <i class="fa fa-download"></i>(<?php echo $ext?>)
                                      </div>
                                      <div class="time">{{date('H:i A', $firstTenDigits);}}<i class="fa fa-check-double"></i></div>
                                    </span></div>
                              </td>
                              </tr>
                          <?php
                        }
                        elseif(in_array($ext, $audioext)){
                          ?>
                            <tr>
                              <td style="margin: 5px; padding: 10px;">
                                  <div class="sender" style="padding: 5px; margin-bottom:25px; display:block;">
                                      <span class="sender-text"  style="border: 1px solid ##EEE; float:right;display: inline-block;">
                                        <div class="downloadanchor" href="{{$item->media_path}}" download>
                                          <i class="fa fa-download"></i>(<?php echo $ext?>)
                                      </div>
                                      <div class="time">{{date('H:i A', $firstTenDigits);}}<i class="fa fa-check-double"></i></div>
                                    </span></div>
                              </td>
                              </tr>
                          <?php
                        }
                        else{
                          ?>
                            <tr>
                            <td style="margin: 5px; padding: 10px;">
                            <div class="sender" style="padding: 5px; margin-bottom:25px; display:block;">
                              <span class="sender-text"  style="border: 1px solid ##EEE; float:right;display: inline-block;">
                                  @if (in_array(pathinfo($item->media_path, PATHINFO_EXTENSION), ['xlsx', 'pdf', 'docx','csv','xls']))
                                      <div class="downloadanchor" href="{{$item->media_path}}" download>
                                          <i class="fa fa-download"></i>(<?php echo $ext?>)
                                      </div>
                                    @else
                                  <div id="" class="uploaded-sender-img" href="{{$item->media_path}}" download><i class="fa fa-download"></i>(<?php $ext="image" ;?>)</div>
                                  @endif
                                  <div class="time">{{date('H:i A', $firstTenDigits);}}<i class="fa fa-check-double"></i></div>
                              </span>
                            </div>
                        </td>
                    </tr>
                            <?php
                        }
                    ?>
                <?php
                } else {  
                  $ext = pathinfo( $item->media_path, PATHINFO_EXTENSION);
                  $extensions = ["jpg", "jpeg", "png", "gif"];
                  $mp4ext = ["mp4"];
                      $audioext = ["m4a", "mp3", "ogg"];
                  if(in_array($ext, $extensions))
                        {
                    ?>
                    <tr>
                        <td style="margin: 5px; padding: 10px;">
                            <div class="received" style="padding: 5px; margin-bottom:25px; display:block;"><span class="received-text"  style="border: 1px solid ##EEE; float:left;display: inline-block;">
                              <div id="" class="uploaded-received-img" href="{{$item->media_path}}" download><i class="fa fa-download"></i>(<?php echo $ext='image'; ?>)</div>
                                <div class="time"><?php echo date('H:i A', $firstTenDigits); ?>
                                    <i class="fa fa-check-double"></i></div></span></div>
                        </td>
                    </tr>
                      <?php
                        }
                        elseif(in_array($ext, $mp4ext)){
                          ?>
                          <tr>
                            <td style="margin: 5px; padding: 10px;">
                                <div class="received" style="padding: 5px; margin-bottom:25px; display:block;"><span class="received-text"  style="border: 1px solid ##EEE; float:left;display: inline-block;">
                                  <div class="downloadanchor" href="{{$item->media_path}}" download>
                                    <i class="fa fa-download"></i> Download (<?php echo $ext?>)
                                </div>
                                </span></div>
                            </td>
                        </tr>
                          <?php
                        }
                        elseif(in_array($ext, $audioext)){
                          ?>
                          <tr>
                            <td style="margin: 5px; padding: 10px;">
                                <div class="received" style="padding: 5px; margin-bottom:25px; display:block;"><span class="received-text"  style="border: 1px solid ##EEE; float:left;display: inline-block;">
                                  <div class="downloadanchor" href="{{$item->media_path}}" download>
                                    <i class="fa fa-download"></i> Download (<?php echo $ext?>)
                                </div>
                                </span></div>
                            </td>
                        </tr>
                          <?php
                        }
                        else{
                        ?>
                              <tr>
                              <td style="margin: 5px; padding: 10px;">
                              <div class="received" style="padding: 5px; margin-bottom:25px; display:block;">
                                <span class="received-text"  style="border: 1px solid ##EEE; float:left;display: inline-block;">
                                  @if (in_array(pathinfo($item->media_path, PATHINFO_EXTENSION), ['xlsx', 'pdf', 'docx','xls','csv']))
                                        <div class="downloadanchor" href="{{$item->media_path}}" download>
                                            <i class="fa fa-download"></i> Download (<?php echo $ext?>)
                                        </div>
                                  @elseif(in_array(pathinfo($item->media_path, PATHINFO_EXTENSION), ["m4a", "mp3", "ogg"]))
                                  <div class="downloadanchor" href="{{$item->media_path}}" download>
                                    <i class="fa fa-download"></i>(<?php echo $ext="audio";?>)
                                    </div>
                                    @elseif(in_array(pathinfo($item->media_path, PATHINFO_EXTENSION), ["mp4"]))
                                  <div class="downloadanchor" href="{{$item->media_path}}" download>
                                    <i class="fa fa-download"></i>(<?php echo $ext="video";?>)
                                    </div>      
                                    @else
                                    <div id="" class="uploaded-received-img" href="{{$item->media_path}}"download><i class="fa fa-download"></i>(<?php echo $ext='image';?>)</div>
                                    @endif
                                  <div class="time"><?php echo date('H:i A', $firstTenDigits); ?><i class="fa fa-check-double"></i></div>
                                </span>
                            </div>
                            </td>
                            </tr>
                      <?php
                      }
                }
              }
              ?>
            </table>
    @endforeach
  </main>