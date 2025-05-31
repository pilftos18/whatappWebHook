<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Chat Details</title>
  <link rel='icon' type='image/x-icon' href='img/favicon.ico'>
  <link rel="stylesheet" href="{{asset('assets/socialmedia/css/bootstrap.min.css')}}">
  <link href="{{asset('assets/socialmedia/css/bootstrap-icons.css')}}" rel="stylesheet">
  <link rel="stylesheet" href="{{asset('assets/socialmedia/emoji/css/style.css')}}">
  <link rel="stylesheet" href="{{asset('assets/socialmedia/emoji/emoji-stylesheet.css')}}">
  <link rel="stylesheet" href="{{asset('assets/socialmedia/css/layout.css')}}">
  <link rel="stylesheet" href="{{asset('assets/socialmedia/css/work-sans-fonts.css')}}">
  <link rel="stylesheet" href="{{asset('assets/socialmedia/css/style.css')}}">
  <link rel="stylesheet" href="{{asset('assets/socialmedia/css/social-media.css')}}">
  <style>
.downloadanchor{
    text-decoration: none;
}
.downloadPDF{color: #b30b00; font-size: 20px;position: relative;top: 10px; text-decoration: none;}
.downloadPDF:hover{color: #b30b00;}
.downloadCSV{color: #64a569; font-size: 20px;position: relative;top: 10px; text-decoration: none;}
.downloadCSV:hover{color: #64a569;}
/* added by krunal sir on 16/11/23 11.21 */
main{width: 80%; margin: auto;}
.main-content, .social-chat-for-all{width: 100%; height: auto;}
.chat-body{padding-bottom: 0;}
.sender:last-child, .received:last-child {
    margin-bottom: 5px;
}

.nav-center{
   position: sticky;
    width: 100%;
    background: #ffff;
    z-index: 2;
    top: 0;
    padding: 0 15px;
}

@media print{
    .chatting-box .nav.chatting li .received-text,.chatting-box .nav.chatting li .sender-text{
        display: block;float:none;
    }
    .nav-center .downloadPDF{display:none !important;}
    /* .chatting-box .nav.chatting li span a{} */
    .chatting-box .nav.chatting li .sender-text{word-break: break-all; word-wrap: break-word;}
}

  </style>
  
</head>

<body>
        <?php //echo "<pre>";print_r($chat);?>

  <main class="chatdetails">
    @php
    $currentDate = null; // Initialize a variable to keep track of the current date
    @endphp

    <div class="d-flex nav-center justify-content-between">
			{{-- <a href="#" class="downloadPDF pb-3" id="downloadPDF"> Download<i class="bi bi-file-pdf-fill"></i></a> --}}
      <a href="#" class="downloadCSV pb-3" id="downloadCSV"> Download<i class="bi bi-filetype-csv"></i></a>
		</div>

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
    
    <div class="social-chat-for-all d-flex">
      <div class="mr-auto main-content whatsapp-chat">
        <div class="chat-body">
          <div class="chatting-box">
            <ul class="nav chatting">
              <input type="hidden" name="chat_id" value="{{$item->chat_id}}" id="chat_id">
              <input type="hidden" name="mobile" value="{{isset($mobile) ? $mobile : ''}}" id="mobile">
              @if ($messageDate)
              <li class="conversation-block">
                  <div class="conversation-date">
                      @if ($messageDate == $today)
                          Today
                      @else
                          {{$messageDate}}
                      @endif
                  </div>
              </li>
          @endif
              <?php
              if($item->message !='' && $item->message !=null){
                if ($item->in_out == 2) {
                  ?>
                  
                   <li class="sender"> <span class="sender-text"> {{$item->message}} <div class="time">
                    <?php echo date('H:i A', $firstTenDigits); ?> <i class="fas fa-check-double"></i>
                  </div></span></li>
                  <?php
                } else {
                  ?>  
                  <li class="received"><span class="received-text">{{$item->message}}<div class="time"><?php echo date('H:i A', $firstTenDigits); ?></div>
                  </span></li>
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
                    <li class="sender">
                      <span class="sender-text"><img id="" class="uploaded-img" src="{{$item->media_path}}" width="200px" height="200px"><div class="time"><?php echo date('H:i A', $firstTenDigits); ?><i class="fa fa-check-double"></i></div></span></li>
                    <?php
                        }
                        elseif(in_array($ext, $mp4ext)){
                          ?>
                          <li class="sender"><span class="sender-text"><video width="250" height="240" controls><source src="{{$item->media_path}}" type="video/mp4"> Your browser does not support the video tag.</video><div class="time"><?php echo date('H:i A', $firstTenDigits); ?><i class="bi bi-check-all chat-read"></i></div></span></li>
                          <?php
                        }
                        elseif(in_array($ext, $audioext)){
                          ?>
                          <li class="sender"><span class="sender-text"><audio width="320" height="240" controls><source src="{{$item->media_path}}" type="audio/ogg">Your browser does not support the audio element.</audio><div class="time"><?php echo date('H:i A', $firstTenDigits); ?><i class="bi bi-check-all chat-read"></i></div></span></li>
                          <?php
                        }
                        else{
                          ?>                          
                            <li class="sender">
                              <span class="sender-text">
                                  @if (in_array(pathinfo($item->media_path, PATHINFO_EXTENSION), ['xlsx', 'pdf', 'docx','csv','xls']))
                                      <a class="downloadanchor" href="{{$item->media_path}}" download>
                                          <i class="fa fa-download"></i>Download (<?php echo $ext?>)
                                      </a>
                                  @else
                                      <img id="" class="uploaded-img" src="{{$item->media_path}}" width="200px" height="200px">
                                  @endif
                                  <div class="time">{{date('H:i A', $firstTenDigits);}}<i class="fa fa-check-double"></i></div>
                              </span>
                          </li>
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
                    <li class="received"><span class="received-text"><img id="" class="uploaded-img" src="{{$item->media_path}}" width="200px" height="200px"><div class="time"><?php echo date('H:i A', $firstTenDigits); ?><i class="fa fa-check-double"></i></div></span></li>
                      <?php
                        }
                        elseif(in_array($ext, $mp4ext)){
                          ?>
                          <li class="received"><span class="received-text"><video width="250" height="240" controls><source src="{{$item->media_path}}" type="video/mp4"> Your browser does not support the video tag.</video><div class="time"><?php echo date('H:i A', $firstTenDigits); ?><i class="bi bi-check-all chat-read"></i></div></span></li>
                        <?php
                        }
                        elseif(in_array($ext, $audioext)){
                          ?>
                          <li class="received"><span class="received-text"><audio width="320" height="240" controls><source src="{{$item->media_path}}" type="audio/ogg">Your browser does not support the audio element.</audio><div class="time"><?php echo date('H:i A', $firstTenDigits); ?><i class="bi bi-check-all chat-read"></i></div></span></li>
                          <?php
                        }
                        else{
                        ?>
                              <li class="received">
                                <span class="received-text">
                                  @if (in_array(pathinfo($item->media_path, PATHINFO_EXTENSION), ['xlsx', 'pdf', 'docx','csv','xls']))
                                        <a class="downloadanchor" href="{{$item->media_path}}" download>
                                            <i class="fa fa-download"></i> Download (<?php echo $ext?>)
                                        </a>
                                    @else
                                    <img id="" class="uploaded-img" src="{{$item->media_path}}" width="200px" height="200px">
                                    @endif
                                  <div class="time"><?php echo date('H:i A', $firstTenDigits); ?><i class="fa fa-check-double"></i></div>
                                </span>
                                </li>
                      <?php
                      }
                }
              }
              ?>
              
            </ul>
          </div>
        </div>
      </div>
    </div>
    @endforeach
  </main>
  
  <script src="{{asset('assets/socialmedia/emoji/js/jquery-3.2.1.min.js')}}"></script>
  <script src="{{asset('assets/socialmedia/js/bootstrap.bundle.min.js')}}"></script>
  <script src="{{asset('assets/socialmedia/js/FileSaver.min.js')}}"></script>
  <script src="{{asset('assets/socialmedia/js/html2canvas.min.js')}}"></script>
  <script src="{{asset('assets/socialmedia/js/jspdf.min.js')}}"></script>
  <script src="{{asset('assets/socialmedia/js/jspdf.umd.min.js')}}"></script>
  <script src="{{asset('assets/socialmedia/emoji/js/jquery.emojiarea.js')}}"></script>
  <script src="{{asset('assets/socialmedia/js/html2pdf.js')}}"></script>
  <script>
//////////////////////////////////////////////////////////////
$(document).ready(function () {
  let pdfGenerationInProgress = false;

  $('#downloadPDF').click(function () {
    $(this).prop('disabled', true);
    $('#downloadPDF').hide();
    $('#downloadCSV').hide();
    pdfGenerationInProgress = true;

    // Clone the content for PDF generation
    var clonedContent = cloneContentForPDF();

    // Replace video and audio elements with their names in the cloned content
    replaceMediaElementsWithNames(clonedContent);

    // Rest of your code for PDF generation
    var pdf = new html2pdf(clonedContent, {
      margin: 10,
      filename: 'chat_details.pdf',
      image: { type: 'jpeg', quality: 1.0, allowHTTP: false },
      html2canvas: { scale: 3, logging: true, allowTaint: false, useCORS: false },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    });

    pdf.outputPdf().then(function (pdfBlob) {
      pdfGenerationInProgress = false;

      // Rest of your code after PDF generation
      $('#downloadPDF').show();
      $('#downloadCSV').show();
      var pdfUrl = URL.createObjectURL(pdfBlob);
      var downloadLink = document.createElement('a');
      downloadLink.href = pdfUrl;
      downloadLink.download = 'chat_details.pdf';
      downloadLink.click();
    });
  });

  function cloneContentForPDF() {
    var contentClone = document.body.cloneNode(true);
    // Additional cloning adjustments can be made if needed
    return contentClone;
  }

  function replaceMediaElementsWithNames(content) {
    replaceElementsWithNames(content, 'audio');
    replaceElementsWithNames(content, 'video');
    replaceImagesWithActualSize(content);
  }

  function replaceElementsWithNames(content, elementSelector) {
    var elements = content.querySelectorAll(elementSelector);
    elements.forEach(function (element) {
      var textNode = document.createTextNode('[' + element.tagName + ']');
      element.parentNode.replaceChild(textNode, element);
    });
  }

  function replaceImagesWithActualSize(content) {
    var images = content.querySelectorAll('img');
    images.forEach(function (image) {
      var imageObject = new Image();
      imageObject.src = image.src;

      imageObject.onload = function () {
        var maxWidth = 300; // Set the maximum width for the image on the page

        // Check if the image fits on the current page
        if (imageObject.width > maxWidth) {
          // If not, move it to the next page
          var nextPage = document.createElement('div');
          nextPage.style.pageBreakBefore = 'always';
          content.appendChild(nextPage);

          // Clone the image and add it to the next page
          var clonedImage = image.cloneNode(true);
          nextPage.appendChild(clonedImage);

          // Remove the original image from the current page
          image.parentNode.removeChild(image);
        }
      };
    });
  }
});

$('#downloadCSV').click(function(){

var chat_id  = $('#chat_id').val();
var mobile  = $('#mobile').val();
var csrfToken = $('meta[name="csrf-token"]').attr('content');

      $.ajax({  
              url:'{{ route("csv.chatdetailscsv") }}',
              type: 'GET',
              data: {
                chat_id: chat_id,
                mobile: mobile,
                      },
              headers: {
                  "X-CSRF-TOKEN": csrfToken // Include the CSRF token in the headers
              },
              success: function (response) {
                     
                     console.log(response);
                     if (response.download) {
                     var link = document.createElement('a');
                     link.href = response.file_url;
                     link.download = response.file_name;
                     link.style.display = 'none';
                     document.body.appendChild(link);
                     link.click();
                     document.body.removeChild(link);
                     }
                 },
            }); 
});
//////////////////////////////////////////////////////////////


// $(document).ready(function () {
//   let pdfGenerationInProgress = false;

//   $('#downloadPDF').click(function () {
//     $(this).prop('disabled', true);
//     $('#downloadPDF').hide();
//     pdfGenerationInProgress = true;

//     // Replace video and audio elements with their names
//     replaceMediaElementsWithNames();

//     // Rest of your code for PDF generation
//     var pdf = new html2pdf(document.body, {
//       margin: 10,
//       filename: 'chat_details.pdf',
//       image: { type: 'jpeg', quality: 1.0, allowHTTP: false },
//       html2canvas: { scale: 3, logging: true, allowTaint: false, useCORS: false }, // Adjust these options
//       jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
//     });

//     pdf.from(document.querySelector(".chatdetails")).outputPdf().then(function (pdfBlob) {
//       pdfGenerationInProgress = false;

//       // Restore the original media elements
//       restoreMediaElements();

//       // Rest of your code after PDF generation
//       $('#downloadPDF').show();
//       var pdfUrl = URL.createObjectURL(pdfBlob);
//       var downloadLink = document.createElement('a');
//       downloadLink.href = pdfUrl;
//       downloadLink.download = 'chat_details.pdf';
//       downloadLink.click();
//     });
//   });

//   function replaceMediaElementsWithNames() {
//     replaceElementsWithNames('audio');
//     replaceElementsWithNames('video');
//   }

//   function replaceElementsWithNames(elementSelector) {
//     var elements = document.querySelectorAll(elementSelector);
//     elements.forEach(function (element) {
//       var textNode = document.createTextNode('[' + element.tagName + ']');
//       element.parentNode.replaceChild(textNode, element);
//     });
//   }

//   function restoreMediaElements() {
//     restoreElements('audio');
//     restoreElements('video');
//   }

//   function restoreElements(elementSelector) {
//     var elementsWithNames = document.querySelectorAll(elementSelector + ':not([data-original-element])');
//     elementsWithNames.forEach(function (element) {
//       var originalElement = document.createElement(elementSelector);
//       element.parentNode.replaceChild(originalElement, element);
//     });
//   }
// });

// $(document).ready(function () {
//         let pdfGenerationInProgress = false;

//         $('#downloadPDF').click(function () {
//             $(this).prop('disabled', true);
//             $('#downloadPDF').hide();
//             pdfGenerationInProgress = true;
//             $('.chatting-box .nav.chatting li .sender-text').css({
//                 'word-break': 'break-all',
//                 'word-wrap': 'break-word'
//             });

//             $('.downloadanchor').each(function () {
//                 $(this).data('original-href', $(this).attr('href'));
//                 $(this).data('original-download', $(this).attr('download'));

//                 // Remove href and download attributes
//                 $(this).removeAttr('href').removeAttr('download').on('click.disableDownload', function (event) {
//                     if (pdfGenerationInProgress) {
//                         event.preventDefault();
//                     }
//                 });
//             });

//             // Specify the target element for html2canvas
//             var targetElement = document.querySelector(".chatdetails");

//             // Use html2canvas to capture the content as an image
//             html2canvas(targetElement, {
//                 scale: 2,
//                 logging: true,
//                 allowTaint: false,
//                 useCORS: false
//             }).then(function (canvas) {
//                 // Convert the canvas to a data URL
//                 var imageData = canvas.toDataURL("image/jpeg", 1.0);

//                 // Create a new jsPDF instance
//                 var pdf = new jsPDF({
//                     unit: 'mm',
//                     format: 'a4',
//                     orientation: 'portrait'
//                 });

//                 var imgWidth = 180; // Adjust the image width as needed
//                 var imgHeight = (canvas.height * imgWidth) / canvas.width;

//                 // Check if the image height exceeds the remaining space on the page
//                 if (pdf.internal.pageSize.height - 10 < imgHeight) {
//                     pdf.addPage();
//                 }

//                 pdf.addImage(imageData, 'JPEG', 10, 10, imgWidth, imgHeight);

//                 var audioElements = document.querySelectorAll('audio');
//                 audioElements.forEach(function (audioElement) {
//                     var audioPositionY = pdf.internal.pageSize.height - 30; // Adjust the Y position
//                     pdf.text('[Audio]', 10, audioPositionY);
//                 });

//                 var videoElements = document.querySelectorAll('video');
//                 videoElements.forEach(function (videoElement) {
//                     var videoPositionY = pdf.internal.pageSize.height - 40; // Adjust the Y position
//                     pdf.text('[Video]', 10, videoPositionY);
//                 });
//                 // Save or download the PDF
//                 pdf.save('chat_details.pdf');

//                 pdfGenerationInProgress = false;
//                 $('.downloadanchor').each(function () {
//                     // Restore the original href and download attributes
//                     $(this).attr('href', $(this).data('original-href'));
//                     $(this).attr('download', $(this).data('original-download'));
//                     $(this).off('click.disableDownload');
//                 });

//                         audioElements.forEach(function (textNode) {
//         var audioElement = document.createElement('audio');
//         textNode.parentNode.replaceChild(audioElement, textNode);
//     });

//     videoElements.forEach(function (textNode) {
//         var videoElement = document.createElement('video');
//         textNode.parentNode.replaceChild(videoElement, textNode);
//     });

//                 $('#downloadPDF').show();
//             });
//         });
//     });

    
// $(document).ready(function() {

//   let pdfGenerationInProgress = false;
//   $('#downloadPDF').click(function() {
//     $(this).prop('disabled', true);
//     $('#downloadPDF').hide();
//     pdfGenerationInProgress = true;
//     $('.chatting-box .nav.chatting li .sender-text').css({
//     'word-break': 'break-all',
//     'word-wrap': 'break-word'
//     });

//     $('.downloadanchor').each(function () {
//       $(this).data('original-href', $(this).attr('href'));
//         $(this).data('original-download', $(this).attr('download'));

//         // Remove href and download attributes
//         $(this).removeAttr('href').removeAttr('download').on('click.disableDownload', function (event) {
//             if (pdfGenerationInProgress) {
//                 event.preventDefault();
//             }
//         });
//     });

//     var pdf = new html2pdf(document.body, {
//         margin: 10,
//         filename: 'chat_details.pdf',
//         image: { type: 'jpeg', quality: 1.0, allowHTTP: false },
//         html2canvas: { scale: 3, logging: true, allowTaint: false, useCORS: false }, // Adjust these options
//         jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
//     });     
//           var audioElements = document.querySelectorAll('audio');
//           audioElements.forEach(function (audioElement) {
//               var textNode = document.createTextNode('[Audio]');
//               audioElement.parentNode.replaceChild(textNode, audioElement);
//           });

//           var videoElements = document.querySelectorAll('video');
//           videoElements.forEach(function (videoElement) {
//               var textNode = document.createTextNode('[Video]');
//               videoElement.parentNode.replaceChild(textNode, videoElement);
//           });
    
//     pdf.from(document.querySelector(".chatdetails")).outputPdf().then(function(pdfBlob) {
//         pdfGenerationInProgress = false;
//         $('.downloadanchor').each(function () {
//             // Restore the original href and download attributes
//             $(this).attr('href', $(this).data('original-href'));
//             $(this).attr('download', $(this).data('original-download'));
//             $(this).off('click.disableDownload');
//         });

//       $('#downloadPDF').show();
//           audioElements.forEach(function (textNode) {
//         var audioElement = document.createElement('audio');
//         textNode.parentNode.replaceChild(audioElement, textNode);
//     });

//     videoElements.forEach(function (textNode) {
//         var videoElement = document.createElement('video');
//         textNode.parentNode.replaceChild(videoElement, textNode);
//     });
//       var pdfUrl = URL.createObjectURL(pdfBlob);
//       var downloadLink = document.createElement('a');
//       downloadLink.href = pdfUrl;
//       downloadLink.download = 'chat_details.pdf';
//       downloadLink.click();
//     });

//   });

// });

//pdf.chatdetailspdf



</script>
