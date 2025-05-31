
@extends('layout')
@section('content')

        @php

        // print_r($GD);
            $isEdit = isset($disposition);
            $route = $isEdit ? route('dispostion.update', $planid) : route('disposition.store');
            $method = $isEdit ? 'PUT' : 'POST';
            $title = $isEdit ? 'Edit Disposition' : 'Create Disposition';
            $button = $isEdit ? 'Save' : 'Save';
        @endphp
     <style>
         .dispositions-section .disposition-row.this{margin-top: 20px;}
         .hidden{
            display:none;
        }
.sub-icon {
    padding-left: 4%;
}
.sub-icon::after {
    display: block;
    content: '';
    width: 1.4%;
    position: absolute;
    left: 10.9%;
    border-bottom: 0;
    border-left: 0;
    bottom: 0;
    top: 17px;
    height: auto;
    /* border-top: 2px solid #e3e3e3; */
}
.sub-icon:last-child::after, .sub-icon-last::after, .disposition-row + .sub-icon:nth-last-child(4)::after{border-left: 2px solid #ffffff;}

.sub-icon::before {
    display: block;
    content: ' ';
    position: absolute;
    left: 10.9%;
    top: -28px;
    bottom: 0;
    display: block;
    width: 0;
    /* border-left: 2px solid #e3e3e3; */
}
.sub-disposition-row .sub-icon::after {
    left: 10.7%;
    width: 1.4%;
}
.sub-disposition-row .sub-icon::before {
    left: 10.7%;
}
.disposition-row hr{
    border: 1px solid #00000059;
    margin-bottom: 30px;
}
.add-disposition, .add-sub-disposition, .add-sub2-disposition, .remove-disposition {
    background-color : #0084FF; color:#FFF;
}
        </style>   

<main id="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>{{$title}}</h1>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('disposition.index') }}" title="back"><i class="bi bi-arrow-left"></i></a>
            </div>
        </div>
    </div>
    <div class="card pt-4">
        <div class="card-body">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $route }}"  enctype="multipart/form-data">
                @csrf
                @if($isEdit)
                        @method('PUT')
                @endif
                

                    <div class="row">
                            <div class="row justify-content-center align-items-center form-box mb-4">
                            <div class="col-lg-4">
                                <div class="form-group field-vaanidispositionplan-name required">
                                    <div class="row"><label class="form-label col-sm-4" for="vaanidispositionplan-name">Plan Name</label><div class="col-sm-8"><input type="text" id="vaanidispositionplan-name" class="form-control" name="VaaniDispositionPlan[name]" placeholder="Plan Name" 
                                        aria-required="true" required /></div></div>
                                </div>
                                </div> 
                            </div>
                    </div>
                    <?php           
                // if (!empty($data)) {
                //     foreach ($data as $key => $model) {

                        // echo "<pre>";print_r($model);
                        //         exit;
                        ?>

                <div class="row justify-content-center align-items-center form-box dispositions-section">
                <div class="col-lg-12 disposition-row">
                        <div class="row justify-content-center align-items-center form-box">
                            <div class="col-lg-3">
                                <div class="form-group row field-vaanicampaigndisposition-disposition">
                                    <label class="form-label col-sm-5" for="vaanicampaigndisposition-disposition">Disposition Name</label>
                                    <div class="col-sm-7">
                                        <input type="text" class="form-control parentInput" name="VaaniDispositions[disposition_name][]" id="vaanicampaigndisposition-disposition" required/> 
                                        <input type="hidden" class="form-control level" value ="1" name="VaaniDispositions[level][]" /> 
                                        <input type="hidden" name="VaaniDispositions[parent_id][]" >
                                                            <div class="help-block"></div>
                                    </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group row field-vaanicampaigndisposition-short_code">
                                <label class="form-label col-sm-5" for="vaanicampaigndisposition-short_code">Short Code</label>
                                <div class="col-sm-7">
                                    <input type="text"  class="form-control vaanicampaigndisposition-short_code_class" name="VaaniDispositions[short_code][]" required/>          
                                                <div class="help-block"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group row field-vaanicampaigndisposition-type">
                                <label class="form-label col-sm-5" for="vaanicampaigndisposition-type">Type</label>
                                <div class="col-sm-7">
                                    <select id="vaanicampaigndisposition-type" class="form-control" name="VaaniDispositions[type][]" required>
                                        <option value="1">Success</option>
                                        <option value="2">Failed</option>
                                        <option value="3">Other</option>
                                        </select>
                                                            <div class="help-block"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group disposition-actions">
                                <a class="btn btn-sm add-sub-disposition " title="Add Sub Disposition"><i class="fa fa-share-alt" style="transform: rotate(300deg);"></i></a>                    <a class="btn btn-sm remove-disposition " title="Delete Disposition" style="display: none;"><i class="fa fa-minus"></i></a>                    <a class="btn btn-sm add-disposition " title="Add Disposition"><i class="fa fa-plus"></i></a>                </div>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="text-center">
                    <button class="btn btn-primary submit-disposition" type="submit">{{ $button }}</button>
                </div> 
            </form>
    </div>
</div>
</main>
<script>
    //$( document ).ready(function() {



     
        ////////////////////////add_sub_dispo//////////////
    $(document).on("click", ".add-sub-disposition", function(){
        
        var dispositions = $('input[name="VaaniDispositions[disposition_name][]"]');
        
        var allFieldsFilled = true;

        dispositions.each(function(index, element) {
            var inputValue = $(element).val();
            if (!inputValue.trim()) {
                allFieldsFilled = false;
                return false; // Break the loop if any field is empty
            }
        });

        if (allFieldsFilled) {
            // alert("All fields are filled");
            // Perform your further actions here
        } else {
            swal.fire("Some input field is empty");
            return false;
        }

        var short = $('input[name="VaaniDispositions[short_code][]"]');

        var allFieldsFilled_short = true;

        short.each(function(index, element) {
            var inputValue1 = $(element).val();
            if (!inputValue1.trim()) {
                allFieldsFilled_short = false;
                return false; // Break the loop if any field is empty
            }
        });

        if (allFieldsFilled_short) {
            // alert("All fields are filled");
            // Perform your further actions here
        } else {
            swal.fire("Some input field is empty");
            return false;
        }
        
    
    var thisRow = $(this).parent().parent().parent();
    console.log(thisRow);

    let parent_id= '';
    if($(this).parent().parent().siblings().first().children().find('#vaanicampaigndisposition-disposition').val()){
        parent_id=$(this).parent().parent().siblings().first().children().find('#vaanicampaigndisposition-disposition').val();
    }

    var html = `<div class="col-lg-12 sub-disposition-row demo-sub-row sub-icon">
                    <div class="row justify-content-center align-items-center form-box">
                        <div class="col-lg-3">
                            <div class="form-group row field-vaanicampaigndisposition-disposition">
                                <label class="form-label col-sm-5" for="vaanicampaigndisposition-disposition">Disposition Name</label>
                                <div class="col-sm-7">
                            
                                    <input type="text" class="form-control" name="VaaniDispositions[disposition_name][]" id="vaanicampaignsubdisposition-disposition"  required/>   
                                    <input type="hidden" class="form-control level" value ="2" name="VaaniDispositions[level][]" /> 
                                    <input type="hidden" name="VaaniDispositions[parent_id][]" value='${parent_id}'>
                                    <div class="help-block"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group row field-vaanicampaigndisposition-short_code">
                                <label class="form-label col-sm-5" for="vaanicampaigndisposition-short_code">Short Code</label>
                                <div class="col-sm-7">
                                    <input type="text"  class="form-control vaanicampaigndisposition-short_code_class" name="VaaniDispositions[short_code][]"  required/>                    <div class="help-block"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group row field-vaanicampaigndisposition-type">
                                <label class="form-label col-sm-5" for="vaanicampaigndisposition-type">Type</label>
                                <div class="col-sm-7">
                                    <select id="vaanicampaigndisposition-type" class="form-control" name="VaaniDispositions[type][]" required>
                                        <option value="1">Success</option>
                                        <option value="2">Failed</option>
                                        <option value="3">Other</option>
                                        </select>           
                                                <div class="help-block"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group sub-disposition-actions">
                                <a class="btn btn-sm add-sub2-disposition " title="Add Sub Disposition"><i class="fa fa-share-alt"  style="transform: rotate(300deg);"></i></a>                <a class="btn btn-sm remove-disposition " title="Delete Disposition"><i class="fa fa-minus"></i></a>                            </div>
                        </div>
                    </div>
                </div>`;

                thisRow.append(html);
    // console.log(thisRow);
    // thisRow.find('#child').prop('value','1');


    // var subdispositionRow = $(".sub-disposition-row" ).eq(0);
    // var cloneRow = subdispositionRow.clone();
    // console.log(cloneRow);
    // thisRow.append(cloneRow);
    // cloneRow.insertAfter( thisRow ).find( "input" ).val( '' );
    // //.children().children()
    // cloneRow.removeClass("hidden");
    // cloneRow.removeClass("demo-sub-row");
    // cloneRow.find('.level').val(2);
    // cloneRow.find(".remove-disposition").show();
    // cloneRow.children().children(".sub2-disposition-row").remove();
    // cloneRow.children().children(".sub-disposition-row").children().remove();
    
    });
    
    /////////////////add_sub_2_dispo////////////////////
    $(document).on("click", ".add-sub2-disposition", function(){

        var dispositions = $('input[name="VaaniDispositions[disposition_name][]"]');
        
        var allFieldsFilled = true;

        dispositions.each(function(index, element) {
            var inputValue = $(element).val();
            if (!inputValue.trim()) {
                allFieldsFilled = false;
                return false; // Break the loop if any field is empty
            }
        });

        if (allFieldsFilled) {
            // alert("All fields are filled");
            // Perform your further actions here
        } else {
            swal.fire("Some input field is empty");
            return false;
        }
       
        var short = $('input[name="VaaniDispositions[short_code][]"]');

        var allFieldsFilled_short = true;

        short.each(function(index, element) {
            var inputValue1 = $(element).val();
            if (!inputValue1.trim()) {
                allFieldsFilled_short = false;
                return false; // Break the loop if any field is empty
            }
        });

        if (allFieldsFilled_short) {
            // alert("All fields are filled");
            // Perform your further actions here
        } else {
            swal.fire("Some input field is empty");
            return false;
        }

        

        // var inputValue = $('.parentInput').val();
        // if (inputValue !== undefined && inputValue !== '') {
        //     alert("inputValue:", inputValue);
        // } else {
        //     alert("input field is not empty ");
        //     return;
        // }
        var thisRow = $(this).parent().parent().parent();
        console.log(thisRow);
        // return;
        
        let parent_id= '';
    if($(this).parent().parent().siblings().first().children().find('#vaanicampaignsubdisposition-disposition').val()){
        parent_id=$(this).parent().parent().siblings().first().children().find('#vaanicampaignsubdisposition-disposition').val();
    }
    // var subdispositionRow = $(".sub2-disposition-row" ).eq(0);
    // var cloneRow = subdispositionRow.clone();

    var html = `<div class="col-lg-12 sub2-disposition-row  demo-sub2-row sub-icon">
                    <div class="row justify-content-center align-items-center form-box">
                            <div class="col-lg-3">
                                <div class="form-group row field-vaanicampaigndisposition-disposition">
                                    <label class="form-label col-sm-5" for="vaanicampaigndisposition-disposition">Disposition Name</label>
                                    <div class="col-sm-7">
                                        
                                        <input type="text" class="form-control" name="VaaniDispositions[disposition_name][]" />    
                                        <input type="hidden" class="form-control level" value ="3" name="VaaniDispositions[level][]" required/> 
                                        <input type="hidden" name="VaaniDispositions[parent_id][]" value='${parent_id}'>

                                                        <div class="help-block"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group row field-vaanicampaigndisposition-short_code">
                                    <label class="form-label col-sm-5" for="vaanicampaigndisposition-short_code">Short Code</label>
                                    <div class="col-sm-7">
                                        <input type="text" class="form-control vaanicampaigndisposition-short_code_class" name="VaaniDispositions[short_code][]" required/>
                                        <div class="help-block"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group row field-vaanicampaigndisposition-type">
                                    <label class="form-label col-sm-5" for="vaanicampaigndisposition-type">Type</label>
                                    <div class="col-sm-7">
                                        <select id="vaanicampaigndisposition-type" class="form-control" name="VaaniDispositions[type][]" required>
                                            <option value="1">Success</option>
                                            <option value="2">Failed</option>
                                            <option value="3">Other</option>
                                        </select>
                                        <div class="help-block"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-group sub-disposition-actions">
                                    <a class="btn btn-sm remove-disposition " title="Delete Disposition"><i class="fa fa-minus"></i></a> 
                                        </div>
                                </div>
                        </div>
                </div>`;
    
    thisRow.append(html);
    // cloneRow.insertAfter( thisRow ).find( "input" ).val( '' );
    // cloneRow.removeClass("hidden");
    // cloneRow.removeClass("demo-sub2-row");
    // cloneRow.find('.level').val(3);

    // cloneRow.find(".remove-disposition").show();
    // cloneRow.children().children(".sub2-disposition-row").remove();
    // cloneRow.children().children(".sub-disposition-row").children().remove();
    });

///////////add-disposition///////////////////////
    $(document).on("click", ".disposition-actions .add-disposition", function(){
        
        var dispositions = $('input[name="VaaniDispositions[disposition_name][]"]');
        
        var allFieldsFilled = true;

        dispositions.each(function(index, element) {
            var inputValue = $(element).val();
            if (!inputValue.trim()) {
                allFieldsFilled = false;
                return false; // Break the loop if any field is empty
            }
        });

        if (allFieldsFilled) {
            // alert("All fields are filled");
            // Perform your further actions here
        } else {
            swal.fire("Some input field is empty");
            return false;
        }
       
        var short = $('input[name="VaaniDispositions[short_code][]"]');

        var allFieldsFilled_short = true;

        short.each(function(index, element) {
            var inputValue1 = $(element).val();
            if (!inputValue1.trim()) {
                allFieldsFilled_short = false;
                return false; // Break the loop if any field is empty
            }
        });

        if (allFieldsFilled_short) {
            // alert("All fields are filled");
            // Perform your further actions here
        } else {
            swal.fire("Some input field is empty");
            return false;
        }
        var thisRow = $(this).closest(".disposition-row" );
        var cloneRow = thisRow.clone();
        cloneRow.addClass("this");
        console.log(cloneRow.children(".sub-disposition-row"));

        cloneRow.children().children('.sub-disposition-row').remove();
        cloneRow.children().children('.sub-disposition-row').children().remove();

        if(thisRow.next().hasClass("sub-disposition-row") && !thisRow.next().hasClass("demo-sub-row") && !thisRow.next().hasClass("demo-sub2-row")){
            cloneRow.insertAfter( thisRow.nextUntil(".disposition-row").not(".demo-sub-row, .demo-sub2-row").last() ).find( "input" ).val( '' );
        }else{
            cloneRow.insertAfter( thisRow ).find( "input" ).val( '' );
        }
        cloneRow.find('.level').prop('value','1');
        cloneRow.find(".disposition-actions .remove-disposition").show();
        cloneRow.find(".row-break").show();
    });

    $(".disposition-actions .remove-disposition:last").show();
    $(".disposition-actions .remove-disposition").first().hide();
    $(".remove-disposition").first().hide();

    // $(".submit-disposition").on("click", function(){
    //     //debugger;
    //     var array = []; //array for storing  entered values in text
    //     var chk = 0;
        
    //     $(".dispositions-section").find(".vaanicampaigndisposition-short_code_class").each(function(){ 

    //         var text = $(this).val();
    //         console.log(text);  //value entered in short_code
            
    //         if(array.includes(text)){
    //             //debugger;
    //                 // find the entered element in array
    //                 $(this).next(".help-block" ).html( "Short code cannot be same" );
    //                 $("#LoadingBox").hide();
    //                 chk = 1;
    //                 return false;
    //             }
    //             else{
    //                 if(text == "){
    //                 //check for text is null
    //                 $(this).next(".help-block" ).html( "Short code cannot be blank" );
    //                 $("#LoadingBox").hide();
    //                 chk = 1;
    //                 return false;
    //                 }
    //                 else{
    //                     $(this).next(".help-block" ).html(");
    //                 }
    //                 array.push(text);
    //         }
    //         //array.push(text);
    //     });
    //     if(chk == 1){
    //         return false;
    //     }
    // }); 

    $("#vaanicampaigndisposition-short_code,#vaanidispositionplan-name").on("focus", function(){
        $(".help-block" ).html('');
    }); 


    // $(".submit-disposition").on('keypress', '.parentInput', function() {
    //     inputValue = $(this).val();

    //     // Find the closest ancestor with the class 'sub-disposition-row'
    //     var subDispositionRow = $(this).closest('.sub-disposition-row');

    //     // Find all input fields with the name 'parent_id[]' within the found ancestor
    //     subDispositionRow.find('input[name="parent_id[]"]').each(function() {
    //         $(this).val(inputValue);
    //     });
    // });

    $(".submit-disposition").on('keypress', '.parentInput', function() {
        inputValue = $(this).val();
        // alert(inputValue);
        // Find the closest ancestor with the class 'sub-disposition-row'
        var subDispositionRow = $(this).closest('.sub-disposition-row');

        // Find all input fields with the name 'parent_id[]' within the found ancestor
        subDispositionRow.find('input[name="parent_id[]"]').each(function() {
            $(this).val(inputValue);
        });

        // Find all input fields with the name 'parent_id[]' within the next sibling with class 'sub2-disposition-row'
        subDispositionRow.next('.sub2-disposition-row').find('input[name="parent_id[]"]').each(function() {
            $(this).val(inputValue);
        });
    })

    $(document).on("click", ".remove-disposition", function(){
        var thisRow = $(this).parent().parent().parent().parent();
        console.log(thisRow);
        if(thisRow && confirm("Are you sure to delete the disposition - " + thisRow.find("#vaanicampaigndisposition-disposition").val() + " ?")){
            if(thisRow.prev().length && !thisRow.next().length){
                thisRow.prev().find(".add-disposition").show();
                thisRow.prev().find(".remove-disposition").show();
            }
            $(".remove-disposition").first().hide();
            
            // delete the disposition from table
            var id = thisRow.find("#vaanicampaigndisposition-disposition_id").val();
            if(id){
                // $.ajax({
                //     method: "POST",
                //     url: {{}},
                //     data: {id : id}
                // }).done(function(data){
                    //     if(data == "success"){
                        //         thisRow.remove();
                //         Swal.fire("disposition deleted successfully.", ", "success");
                //     }else{
                    //         Swal.fire(data, ", "error");
                //         return false;
                //     }
                // });
            }else{
                thisRow.remove();
            }
        }
    });
    
//});



$(document).ready(function() {
    // Initial code on page load
    $('.disposition-row').each(function() {
        var $parentRow = $(this);
        var parentValue = $parentRow.find('.parentInput').val();

        $parentRow.find('.sub-disposition-row').each(function() {
            var $childInput = $(this).find('input[name="VaaniDispositions[parent_id][]"]');
            $childInput.val(parentValue);
        });
    });

    $('.sub-disposition-row').each(function() {
        var $parentRow = $(this);
        var parentValue = $parentRow.find('input[name="VaaniDispositions[disposition_name][]"]').val();

        $parentRow.find('.sub2-disposition-row').each(function() {
            var $childInput = $(this).find('input[name="VaaniDispositions[parent_id][]"]');
            $childInput.val(parentValue);
        });
    });


});

    // Event listener for changes in parent input field
    $('.parentInput').on('input', function() {
        
        var parentValue = $(this).val();
        var $parentRow = $(this).closest('.disposition-row');
        var childvalue = $parentRow.find('.sub-disposition-row input[name="VaaniDispositions[disposition_name][]"]').val();

        // Update child input value
        if (!$parentRow.hasClass('sub2-disposition-row')) {
        $parentRow.find('.sub-disposition-row input[name="VaaniDispositions[parent_id][]"]').val(parentValue);
        $parentRow.find('.sub2-disposition-row input[name="VaaniDispositions[parent_id][]"]').val(childvalue);
        }
    });

    // document.addEventListener('DOMContentLoaded', function() {
    //         // Disable copy and paste for the entire page
    //         document.addEventListener('copy', function(event) {
    //             event.preventDefault();
    //         });

    //         document.addEventListener('cut', function(event) {
    //             event.preventDefault();
    //         });

    //         document.addEventListener('paste', function(event) {
    //             event.preventDefault();
    //         });
    //     }); 

    </script>
@endsection
