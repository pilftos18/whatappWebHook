 //   $sessionData = session('data');
        // $client_id = $sessionData['Client_id'];

        // $request->validate([
        //     'VaaniDispositionPlan.name' => 'required',
        //     'VaaniDispositions.disposition_name.*.*.*' => 'nullable|string',
        //     'VaaniDispositions.short_code.*.*.*' => 'nullable|string',
        //     'VaaniDispositions.type.*.*.*' => 'nullable|integer',
        // ]);


        ///Author:Pooja Bachchu
$(document).on('click', '.disposition-actions .add-disposition', function(){
    var thisRow = $(this).closest('.disposition-row' );
    // console.log(thisRow);
    var cloneRow = thisRow.clone();
 
    // $('#level').attr('val',1);
    // let level=cloneRow.find('#level').prop('value',1);
    // level.val(1);
    cloneRow.addClass('this');
    // console.log(cloneRow);
    cloneRow.children().children().children('.sub-disposition-row').remove();
    cloneRow.children().children('.sub-disposition-row').children().remove();
    if(thisRow.next().hasClass('sub-disposition-row')){
        cloneRow.insertAfter( thisRow.nextUntil('.disposition-row').not('.demo-sub-row, .demo-sub2-row').last() ).find( 'input' ).val( '' );
    }else{
        cloneRow.insertAfter( thisRow ).find( 'input' ).val( '' );
    }
    cloneRow.find('#level').prop('value','1');
    cloneRow.find('#first_occurance').prop('value','1');
    cloneRow.find('#child').prop('value','0');
    cloneRow.removeAttr('id');
    cloneRow.find('.disposition-actions .remove-disposition').show();
    cloneRow.find('.row-break').show();
});

///////////remove_disposition//////////////////////
$(document).on('click', '.remove-disposition', function(e){
    e.preventDefault();
    var thisRow = $(this).parent().parent().parent().parent().parent();
    // console.log(thisRow);
    // console.log(thisRow.hasClass('sub-disposition-row'));
    // console.log(thisRow.siblings('.sub-disposition-row').length);
    if(thisRow.siblings('.sub-disposition-row').length == 1){
        thisRow.siblings('.sub-disposition-row').find('.remove-disposition').addClass('disabled');
    }
    if(thisRow.siblings('.sub-disposition-row').length == 0 && thisRow.hasClass('sub-disposition-row')){
        // console.log(thisRow.parent());
     thisRow.parent().find('#child').prop('value','0');
    //  console.log(thisRow.parent().children());
    }

    var modal = document.getElementById("row_disp_delete");
    modal.style.display = "flex";
    $("#modalOk").click(function(){
      $('#row_disp_delete').hide();
    //   if(thisRow && confirm('Are you sure to delete the disposition - ' + thisRow.find('#vaanicampaigndisposition-disposition').val() + ' ?')){
      if(thisRow){
        if(thisRow.prev().length && !thisRow.next().length){
            thisRow.prev().find('.add-disposition').show();
            thisRow.prev().find('.remove-disposition').show();
        }
        $('.remove-disposition').first().hide();

        // delete the disposition from table
        var id = thisRow[0].id;

        if(id){
            $.ajax({
                method: 'POST',
                url: 'components/admin/campaigns/submit_dispositions.php?del_current_disposition=del_current_disposition',
                data: {id : id}
            }).done(function(data){
                // console.log(data);
                if(data == 'Success'){
                    thisRow.remove();
                    noticfication("#tmsg", "Disposition deleted successfully", "success");
                }else{
                    noticfication("#tmsg", "Disposition not deleted", "error");
                    return false;
                }
            });
        }
        else{
            thisRow.remove();
            noticfication("#tmsg", "Disposition deleted successfully", "success");
        }
    }
    });
    $('.modalcancel').click(function(){
        $("#row_disp_delete").hide();
    });
});

////////////////////////add_sub_dispo//////////////
$(document).on('click', '.add-sub-disposition', function(){

    $('.sub-icon').removeClass('sub-icon-last');
    $('.sub-icon:nth-last-child(4)').addClass('sub-icon-last');
    var thisRow = $(this).parent().parent().parent();
    thisRow.find('#child').prop('value','1');
    let parent_id= '';
    
    // console.log($(this).parent().parent().siblings().first().children().find('#vaanicampaigndisposition-disposition').val());
    if($(this).parent().parent().siblings().first().children().find('#vaanicampaigndisposition-disposition').val()){
        parent_id=$(this).parent().parent().siblings().first().children().find('#vaanicampaigndisposition-disposition').val();
    }

    var html =`<div class="disp-f justify-content form-box sub-disposition-row branching">
    <div class="col-12 disposition-disp-f ">
        <div class="disp-f  justify-content">
            <div class="col-6">
                <input type="hidden" id="vaanicampaigndisposition-disposition_id" name="VaaniDispositions[disposition_id][]">
                <div class="form-group disp-f  field-vaanicampaigndisposition-disposition">
                    <label class="form-label col-5" for="vaanicampaigndisposition-disposition">Sub Disposition</label>
                    <div class="col-7">
                        <input type="text" id="vaanicampaigndisposition-disposition" name="VaaniDispositions[disposition_name][]" required="required">       
                        <input type="hidden" name="level[]" value=2 id='level'>
                        <input type="hidden" name="disposition_id[]"/>
                        <input type="hidden" name="parent_id[]" value='${parent_id}'>
                        <input type="hidden" name="first_occurance[]" id="first_occurance" value='1'/>
                        <input type="hidden" name="child[]" id="child" value='0'/>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="form-group disp-f  field-vaanicampaigndisposition-type">
                    <label class="form-label col-3" for="vaanicampaigndisposition-type">Type</label>
                    <div class="col-9">
                        <select id="vaanicampaigndisposition-type" name="VaaniDispositions[type][]">
                                <option value="Success">Success</option>
                                <option value="Failed">Failed</option>
                                <option value="Callback">Callback</option>
                                <option value="Other">Other</option>
                        </select>                    
                        
                    </div>
                </div>
            </div>
            <div class="col-2">
            <div class="form-group sub-disposition-actions">
                <a class="btn btn-sm remove-disposition p-5 pr-10 pl-10 ml-10" title="Delete Disposition"><i class="fas fa-minus"></i></a>
            </div>
            </div>
        </div>
    </div>
</div>`;
    thisRow.append(html);
    if(thisRow.find('.sub-disposition-row').length > 1){
        thisRow.find('.sub-disposition-row').find('.remove-disposition').removeClass('disabled');
    }
    else {
        thisRow.find('.sub-disposition-row').find('.remove-disposition').addClass('disabled');
    }
});

/////////////////add_sub_2_dispo////////////////////

// $(document).on('click', '.add-sub2-disposition', function(){
//     var thisRow = $(this).parent().parent();
//     var subdispositionRow = $('.sub2-disposition-row' ).eq(0);
//     var cloneRow = subdispositionRow.clone();
//     cloneRow.insertAfter( thisRow ).find( 'input' ).val( '' );
//     cloneRow.removeClass('hidden');
//     cloneRow.removeClass('demo-sub2-row');
//     cloneRow.find('.remove-disposition').show();
// });

////////////////////////////////////////////// fetch plan dispositions
    // function getdispositions(plan_id = null)
    // {
    //     $.ajax({
    //         method: 'GET',
    //         url: '". $urlManager->baseUrl . '/index.php/disposition/get-dispositions' ."',//function write to controller
    //         data: {plan_id : plan_id}
    //     }).done(function(data){
    //         $('.dispositions-section').html(data);
    //         $('.disposition-actions .remove-disposition:last').show();
    //         $('.disposition-actions .remove-disposition').first().hide();
    //     });
    // }

    // var plan_id = $('#vaanidispositionplan-plan_id').val();
    // getdispositions(plan_id);

    // VALIDATION
    // $('#submitForm').on('submit', function(){
    //   if($('#vaanidispositionplan-name').val().length == 0) {
    // $('#LoadingBox').hide(); 
    // }
    // else{
    //     $('#LoadingBox').show();
    // }
    //     });

function setloadfunc(){
    setTimeout(function () {
        location.reload(true);
        }, 8000);
}

$('.submit-disposition').on('click', function(e){
    e.preventDefault();

    let flag=0;
    let datastring=$('#submitForm').serialize();
    $.ajax({
        type: "POST",
        url: "components/admin/campaigns/submit_dispositions.php?add_dispostions=add_dispostions",
        data: datastring,   
        success: function(resultData){
            // console.log(resultData);
            if(resultData=='Success'){
                noticfication("#tmsg", "Data inserted succesfully", "success");
                setloadfunc();
            }
            else{
                noticfication("#tmsg", resultData, "error");
            }
            
        },
        error: function (jqXHR, textStatus, errorThrown) {
            if (jqXHR.status == 500) {
                alert('Internal error: ' + jqXHR.responseText);
            } else {
                alert('Unexpected error.');
            }
        }
  });
});

function deleteDisposition(id,e){
   e.preventDefault();
   var modal = document.getElementById("disp_delete");
    modal.style.display = "flex";
    $("#modalOk").click(function(){
        
        $('#disp_delete').hide();
        $.ajax({
                type: "POST",
                url: "components/admin/campaigns/submit_dispositions.php?delete_dispositions=delete_dispositions",
                data: {
                    disposition_id:id
                },   
                success: function(resultData){
                    
                    if(resultData=='Success'){
                        noticfication("#tmsg", "Data deleted succesfully", "success");
                    }
                    else{
                        noticfication("#tmsg", "Data not deleted", "error");
                    }
                    setloadfunc();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 500) {
                        alert('Internal error: ' + jqXHR.responseText);
                    } else {
                        alert('Unexpected error.');
                    }
                }
        });
    });
    $('.modalcancel').click(function(){
      $('#disp_delete').hide();
    });
}

// function modifyDisposition(id,e){
//     e.preventDefault();
//     $.ajax({
//         type: "POST",
//         url: "components/admin/campaigns/update_dispositions.php?modify_dispositions=modify_dispositions",
//         data: {
//             disposition_id:id
//         },   
//         success: function(resultData){
//             console.log(resultData);
//             // location.reload();
//             // if(resultData=='Success'){
//             //     noticfication("#tmsg", "Data deleted succesfully", "success");
//             // }
//             // else{
//             //     noticfication("#tmsg", "Data not deleted", "error");
//             // }
//         }
//   });
// }

$('.update_dispositions').on('click', function(e){
    // alert(1);
    e.preventDefault();

    let flag=0;
    // if($('#submitForm input:required').each(function() {
    //     if ($(this).val() === ''){
    //         noticfication("#tmsg", "Please Fill All The dispositions", "error");
    //         flag=1;
    //         return false;
    //     }
    //    }
    // ));

    if(flag==0){
    let datastring=$('#updateForm').serialize();
    $.ajax({
        type: "POST",
        url: "components/admin/campaigns/submit_dispositions.php?update_dispostions=update_dispostions",
        data: datastring,   
        success: function(resultData){
            // console.log(resultData);
            if(resultData=='Success'){
                noticfication("#tmsg", "Data updated succesfully", "success");
            }
            else{
                noticfication("#tmsg", resultData, "error");
            }
            setloadfunc();
        },
        error: function (jqXHR, textStatus, errorThrown) {
            if (jqXHR.status == 500) {
                alert('Internal error: ' + jqXHR.responseText);
            } else {
                alert('Unexpected error.');
            }
        }
        
  });
}
});

function excelImport(e){
    e.preventDefault();
    var file_name=$('#import_file').val();
    // console.log(file_name);
    var fd = new FormData();
  
    var files = $('#import_file')[0].files;
    // console.log(files);
     // Check file selected or not
     if(files.length > 0 ){
        fd.append('file',files[0]);
        // console.log(fd);
        $.ajax({
            url: 'components/admin/campaigns/upload_dispositions.php',
            type: 'post',
            data: fd,
            contentType: false,
            processData: false,
            dataType:'json',
            success: function(resultData){
                // console.log(resultData);
            if(resultData=='Success'){
                noticfication("#tmsg", "Data imported succesfully", "success");
            }
            else{
                noticfication("#tmsg", resultData, "error");
            }
            setloadfunc();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (jqXHR.status == 500) {
                    alert('Internal error: ' + jqXHR.responseText);
                } else {
                    alert('Unexpected error.');
                }
            }
        });
      }
}
$("#updateForm").on('blur','.parentInput', function() {
    // alert();
    inputValue = $(this).val();
    // console.log(inputValue);
    $(this).parent().parent().parent().siblings('.sub-disposition-row').find('input[name="parent_id[]"]').each(function(){
        $(this).val(inputValue);
    });
});

$("#submitForm").on('blur','.parentInput', function() {
    // alert();
    inputValue = $(this).val();
    // console.log(inputValue);
    $(this).parent().parent().parent().siblings('.sub-disposition-row').find('input[name="parent_id[]"]').each(function(){
        $(this).val(inputValue);
    });
});

// Get the modal
var modal = document.getElementById("myModal");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close_modal")[0];

// When the user clicks on the button, open the modal
$('.myBtn').click(function(){
    let plan_id=$(this).find('.plan_id').val();
    modal.style.display = "flex";
    let html='';
    let parent_name='';
    $.ajax({
        type: "GET",
        url: "components/admin/campaigns/submit_dispositions.php?show_dispositions=show_dispositions",
        data: {
            plan_id:plan_id
        },   
        dataType:"json",
        success: function(resultData){
            $('#show_disp').text('Disposition Planame - '+resultData[0].plan_name);
            html+='<ul>';
            for(let i=0;i<resultData.length;i++){
                    html+='<li class="arrow right">'+resultData[i]['disposition'];
                    if(resultData[i]['sub_dispostion'] != null){
                    for(j=0; j<resultData[i]['sub_dispostion'].length;j++){
                        html+='<p>'+resultData[i]['sub_dispostion'][j];
                    }
                }
            }

            html+='</li></ul>';
            $('#show_dispositions').html(html);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            if (jqXHR.status == 500) {
                alert('Internal error: ' + jqXHR.responseText);
            } else {
                alert('Unexpected error.');
            }
        }
        
  });
  
    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }
  
    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
    }
  
});

// Show selected filename 
$('#import_file').change(function(){
 $('.show_filename').html($(this).val());
});


Regards,

Pooja Bachchu
Php Developer 
Eureka Digitisation and Automation Services
 
E:  pooja.bachchu@edas.tech
W:  www.edas.tech
										
High Street Corporate Centre | Kapurbavadi Junction | Thane (W) – 400607 | MH – India
India | UK | USA | Australia
      

From: pooja.bachchu@edas.tech [mailto:pooja.bachchu@edas.tech] 
Sent: 18 October 2023 19:13
To: 'gaurav.deshmukh@edas.tech' <gaurav.deshmukh@edas.tech>
Subject: FW: Dispositions



Regards,

Pooja Bachchu
Php Developer 
Eureka Digitisation and Automation Services
 
E:  pooja.bachchu@edas.tech
W:  www.edas.tech
										
High Street Corporate Centre | Kapurbavadi Junction | Thane (W) – 400607 | MH – India
India | UK | USA | Australia
      

From: pooja.bachchu@edas.tech [mailto:pooja.bachchu@edas.tech] 
Sent: 18 October 2023 19:12
To: 'gaurav.deshmukh@edas.tech' <gaurav.deshmukh@edas.tech>
Subject: RE: Dispositions



Regards,

Pooja Bachchu
Php Developer 
Eureka Digitisation and Automation Services
 
E:  pooja.bachchu@edas.tech
W:  www.edas.tech
										
High Street Corporate Centre | Kapurbavadi Junction | Thane (W) – 400607 | MH – India
India | UK | USA | Australia
      

From: pooja.bachchu@edas.tech [mailto:pooja.bachchu@edas.tech] 
Sent: 18 October 2023 18:08
To: 'gaurav.deshmukh@edas.tech' <gaurav.deshmukh@edas.tech>
Subject: Dispositions



Regards,

Pooja Bachchu
Php Developer 
Eureka Digitisation and Automation Services
 
E:  pooja.bachchu@edas.tech
W:  www.edas.tech
										
High Street Corporate Centre | Kapurbavadi Junction | Thane (W) – 400607 | MH – India
India | UK | USA | Australia
      

