
@extends('layout')
@section('content')

        @php

        // print_r($GD);
            $isEdit = isset($disposition);
            $route = $isEdit ? route('dispostion.update', $disposition->id) : route('disposition.store');
            // route('disposition.store')
            $method = $isEdit ? 'PUT' : 'POST';
            $title = $isEdit ? 'Edit Disposition' : 'Create Disposition';
            $button = $isEdit ? 'Update' : 'Create';
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
                <a class="btn btn-outline-primary btn-sm" href="{{ route('disposition.index') }}"><i class="bi bi-arrow-left"></i></a>
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
                    <div class="row"><label class="form-label col-sm-4" for="vaanidispositionplan-name">Plan Name</label><div class="col-sm-8"><input type="text" id="vaanidispositionplan-name" class="form-control" name="VaaniDispositionPlan[name]" value="" placeholder="Plan Name" 
                        aria-required="true" /></div></div>
                        {{-- {{$planname[0] ?? ''}} --}}
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

        <div class="col-lg-12 disposition-row">
            <hr class="row-break <?php //echo ($key != 0) ? '' : 'hidden'; ?>">
            <div class="row justify-content-center align-items-center form-box">
                <div class="col-lg-4">
                    {{-- <input type="hidden" name="VaaniDispositions[disposition_id][]" value="<?php //echo $model->; ?>" id="vaanicampaigndisposition-disposition_id"> --}}
                    <div class="form-group field-vaanicampaigndisposition-disposition">
                        <input type="text" name="VaaniDispositions[disposition_name][]" value="<?php //echo $model->disponame; ?>" id="vaanicampaigndisposition-disposition" class="form-control" required="required">
                        <label class="form-label" for="vaanicampaigndisposition-disposition">Disposition Name</label>
                        <div class="help-block"></div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group field-vaanicampaigndisposition-short_code">
                        <input type="text" name="VaaniDispositions[short_code][]" value="<?php //echo $model->dispocode; ?>" id="vaanicampaigndisposition-short_code" class="form-control vaanicampaigndisposition-short_code_class" required="required">
                        <label class="form-label" for="vaanicampaigndisposition-short_code">Short Code</label>
                        <div class="help-block"></div>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group field-vaanicampaigndisposition-type">
                        <select name="VaaniDispositions[type][]" id="vaanicampaigndisposition-type" class="form-control">
                            <option value="success" >success</option>
                            <option value="failed" >failed</option>
                            <!-- Add more options as needed -->
                            {{-- {{ old('dispotype', isset($queue) && $queue->status == 1 ? 'selected' : '' )}}
                            {{ old('dispotype', isset($queue) && $queue->status == 1 ? 'selected' : '' )}} --}}
                        </select>
                        <label class="form-label" for="vaanicampaigndisposition-type">Type</label>
                        <div class="help-block"></div>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group disposition-actions">
                        <a href="#" class="btn btn-sm add-sub-disposition " title="Add Sub Disposition"><i class="fa fa-snowflake-o"></i></a>
                        <a href="#" class="btn btn-sm remove-disposition " title="Delete Disposition"><i class="fa fa-minus"></i></a>
                        <a href="#" class="btn btn-sm add-disposition " title="Add Disposition"><i class="fa fa-plus"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // if (!empty($model['subDispositions'])) {
        //     foreach ($model['subDispositions'] as $k => $sub_disposition) {
            ?>
                <div class="col-lg-12 sub-disposition-row sub-icon">
                <div class="row justify-content-center align-items-center form-box">
                <div class="col-lg-4">
                {{-- <input type="hidden" name="VaaniDispositions[disposition_id][][]" value="" /> --}}
                <div class="form-group">
                <input type="text" name="VaaniDispositions[disposition_name][][]" value="" class="form-control" required />
                <label class="form-label" for="vaanicampaigndisposition-disposition">Disposition Name</label>
                <div class="help-block"></div>
                </div>
                </div>
                <div class="col-lg-2">
                <div class="form-group">
                <input type="text" name="VaaniDispositions[short_code][][]" value="" class="form-control vaanicampaigndisposition-short_code_class" required />
                <label class="form-label" for="vaanicampaigndisposition-short_code">Short Code</label>
                <div class="help-block"></div>
                </div>
                </div>
                <div class="col-lg-2">
                <div class="form-group">
                <select name="VaaniDispositions[type][][]" class="form-control">
                <option value="success">success</option>
                <option value="failed">failed</option>
                </select>
                <label class="form-label" for="vaanicampaigndisposition-type">Type</label>
                <div class="help-block"></div>
                </div>
                </div>
                <div class="col-lg-2">
                <div class="form-group sub-disposition-actions">
                <a href="#" class="btn btn-sm add-sub2-disposition " title="Add Sub Disposition"><i class="fa fa-snowflake-o"></i></a>
                <a href="#" class="btn btn-sm remove-disposition " title="Delete Disposition"><i class="fa fa-minus"></i></a>
                </div>
                </div>
                </div>
                </div>
        <?php
                // if (!empty($sub_disposition['subDispositions'])) {
                //     foreach ($sub_disposition['subDispositions'] as $sub_k => $sub2_disposition) {
                        ?>
                        <div class="col-lg-12 sub2-disposition-row">
                        <div class="row justify-content-center align-items-center form-box">
                        <div class="col-lg-1 m-0 p-0"></div>
                        <div class="col-lg-3 sub-icon">
                        {{-- <input type="hidden" name="VaaniDispositions[disposition_id][][][]" value="" /> --}}
                        <div class="form-group">
                        <input type="text" name="VaaniDispositions[disposition_name][][][]" value="" class="form-control" required />
                        <label class="form-label" for="vaanicampaigndisposition-disposition">Disposition Name</label>
                        <div class="help-block"></div>
                        </div>
                        </div>
                        <div class="col-lg-2">
                        <div class="form-group">
                        <input type="text" name="VaaniDispositions[short_code][][][]" value="" class="form-control vaanicampaigndisposition-short_code_class" required />
                        <label class="form-label" for="vaanicampaigndisposition-short_code">Short Code</label>
                        <div class="help-block"></div>
                        </div>
                        </div>
                        <div class="col-lg-2">
                        <div class="form-group">
                        <select name="VaaniDispositions[type][][][]" class="form-control">
                        <option value="success">success</option>
                        <option value="failed">failed</option>
                        </select>
                        <label class="form-label" for="vaanicampaigndisposition-type">Type</label>
                        <div class="help-block"></div>
                        </div>
                        </div>
                        <div class="col-lg-2">
                        <div class="form-group sub-disposition-actions">
                        <a href="#" class="btn btn-sm remove-disposition " title="Delete Disposition"><i class="fa fa-minus"></i></a>
                        </div>
                        </div>
                        </div>
                        </div>

                        <?php
        //             }
        //         }
        //     }
        // } else {
            ?>
            <div class="col-lg-12 disposition-row">
            <div class="row justify-content-center align-items-center form-box">
            <div class="col-lg-3">
            {{-- <input type="hidden" name="VaaniDispositions[disposition_id][]" value="" /> --}}
            <div class="form-group row field-vaanicampaigndisposition-disposition">
            <label class="form-label col-sm-5" for="vaanicampaigndisposition-disposition">Disposition Name</label>
            <div class="col-sm-7">
            <input type="text" name="VaaniDispositions[disposition_name][]" value="" class="form-control" required />
            <div class="help-block"></div>
            </div>
            </div>
            </div>
            <div class="col-lg-3">
            <div class="form-group row field-vaanicampaigndisposition-short_code">
            <label class="form-label col-sm-5" for="vaanicampaigndisposition-short_code">Short Code</label>
            <div class="col-sm-7">
            <input type="text" name="VaaniDispositions[short_code][]" value="" class="form-control vaanicampaigndisposition-short_code_class" required />
            <div class="help-block"></div>
            </div>
            </div>
            </div>
            <div class="col-lg-2">
            <div class="form-group row field-vaanicampaigndisposition-type">
            <label class="form-label col-sm-5" for="vaanicampaigndisposition-type">Type</label>
            <div class="col-sm-7">
            <select name="VaaniDispositions[type][]" class="form-control">
            <option value="success">success</option>
            <option value="failed">failed</option>
            </select>
            <div class="help-block"></div>
            </div>
            </div>
            </div>
            <div class="col-lg-2">
            <div class="form-group disposition-actions">
            <a href="#" class="btn btn-sm add-sub-disposition " title="Add Sub Disposition"><i class="fa fa-snowflake-o"></i></a>
            <a href="#" class="btn btn-sm remove-disposition " title="Delete Disposition"><i class="fa fa-minus"></i></a>
            <a href="#" class="btn btn-sm add-disposition " title="Add Disposition"><i class="fa fa-plus"></i></a>
            </div>
            </div>
            </div>
            </div>
            <?php
    //     }
     // }
        ?>

<div class="col-lg-12 sub-disposition-row hidden demo-sub-row sub-icon">
    <div class="row justify-content-center align-items-center form-box">
        <div class="col-lg-3">
            {{-- <input type="hidden" name="VaaniDispositions[disposition_id][][]" value="" id="vaanicampaigndisposition-disposition_id"> --}}
            <div class="form-group row field-vaanicampaigndisposition-disposition">
                <label class="form-label col-sm-5" for="vaanicampaigndisposition-disposition">Disposition Name</label>
                <div class="col-sm-7">
                    <input type="text" name="VaaniDispositions[disposition_name][][]" value="" id="vaanicampaigndisposition-disposition" class="form-control" required>
                    <div class="help-block"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="form-group row field-vaanicampaigndisposition-short_code">
                <label class="form-label col-sm-5" for="vaanicampaigndisposition-short_code">Short Code</label>
                <div class="col-sm-7">
                    <input type="text" name="VaaniDispositions[short_code][][]" value="" id="vaanicampaigndisposition-short_code" class="form-control vaanicampaigndisposition-short_code_class" required>
                    <div class="help-block"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="form-group row field-vaanicampaigndisposition-type">
                <label class="form-label col-sm-5" for="vaanicampaigndisposition-type">Type</label>
                <div class="col-sm-7">
                    <select name="VaaniDispositions[type][][]" id="vaanicampaigndisposition-type" class="form-control">
                        <option value="success">success</option>
                        <option value="failed">failed</option>
                    </select>
                    <div class="help-block"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="form-group sub-disposition-actions">
                <a href="#" class="btn btn-sm add-sub2-disposition " title="Add Sub Disposition"><i class="fa fa-snowflake-o"></i></a>
                <a href="#" class="btn btn-sm remove-disposition " title="Delete Disposition"><i class="fa fa-minus"></i></a>
                <!-- You can add more buttons or actions here if needed -->
            </div>
        </div>
    </div>
</div>  

<div class="col-lg-12 sub2-disposition-row hidden demo-sub2-row sub-icon">
    <div class="row justify-content-center align-items-center form-box">
        <!-- <div class="col-lg-1 m-0 p-0"></div> -->
        <div class="col-lg-3">
            {{-- <input type="hidden" name="VaaniDispositions[disposition_id][][][]" value="" id="vaanicampaigndisposition-disposition_id"> --}}
            <div class="form-group row field-vaanicampaigndisposition-disposition">
                <label class="form-label col-sm-5" for="vaanicampaigndisposition-disposition">Disposition Name</label>
                <div class="col-sm-7">
                    <input type="text" name="VaaniDispositions[disposition_name][][][]" value="" id="vaanicampaigndisposition-disposition" class="form-control" required>
                    <div class="help-block"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="form-group row field-vaanicampaigndisposition-short_code">
                <label class="form-label col-sm-5" for="vaanicampaigndisposition-short_code">Short Code</label>
                <div class="col-sm-7">
                    <input type="text" name="VaaniDispositions[short_code][][][]" value="" id="vaanicampaigndisposition-short_code" class="form-control vaanicampaigndisposition-short_code_class" required>
                    <div class="help-block"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="form-group row field-vaanicampaigndisposition-type">
                <label class="form-label col-sm-5" for="vaanicampaigndisposition-type">Type</label>
                <div class="col-sm-7">
                    <select name="VaaniDispositions[type][][][]" id="vaanicampaigndisposition-type" class="form-control">
                        <option value="success">success</option>
                        <option value="failed">failed</option>
                    </select>
                    <div class="help-block"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="form-group sub-disposition-actions">
                <a href="#" class="btn btn-sm remove-disposition " title="Delete Disposition"><i class="fa fa-minus"></i></a>
                <!-- You can add more buttons or actions here if needed -->
            </div>
        </div>
    </div>
</div>
            <div class="text-center">
                <button class="btn btn-primary" type="submit">{{ $button }}</button>
            </div> 
</form>
</div>
</main>
<script>
    $( document ).ready(function() {

        ////////////////////////add_sub_dispo//////////////
    $(document).on('click', '.add-sub-disposition', function(){
    
    var thisRow = $(this).parent().parent().parent();
    var subdispositionRow = $('.sub-disposition-row' ).eq(0);
    var cloneRow = subdispositionRow.clone();
    thisRow.append(cloneRow);
    cloneRow.insertAfter( thisRow ).find( 'input' ).val( '' );
    //.children().children()
    cloneRow.removeClass('hidden');
    cloneRow.removeClass('demo-sub-row');
    cloneRow.find('.remove-disposition').show();
    cloneRow.children().children('.sub2-disposition-row').remove();
    cloneRow.children().children('.sub-disposition-row').children().remove();
    
    });
    
    /////////////////add_sub_2_dispo////////////////////
    $(document).on('click', '.add-sub2-disposition', function(){
        var thisRow = $(this).parent().parent();
    var subdispositionRow = $('.sub2-disposition-row' ).eq(0);
    var cloneRow = subdispositionRow.clone();
    cloneRow.insertAfter( thisRow ).find( 'input' ).val( '' );
    cloneRow.removeClass('hidden');
    cloneRow.removeClass('demo-sub2-row');
    cloneRow.find('.remove-disposition').show();
    cloneRow.children().children('.sub2-disposition-row').remove();
    cloneRow.children().children('.sub-disposition-row').children().remove();
    });

///////////add-disposition///////////////////////
    $(document).on('click', '.disposition-actions .add-disposition', function(){
        var thisRow = $(this).closest('.disposition-row' );
        var cloneRow = thisRow.clone();
        cloneRow.addClass('this');
        cloneRow.children('.sub-disposition-row').remove();
        cloneRow.children('.sub-disposition-row').children().remove();
        if(thisRow.next().hasClass('sub-disposition-row') && !thisRow.next().hasClass('demo-sub-row') && !thisRow.next().hasClass('demo-sub2-row')){
            cloneRow.insertAfter( thisRow.nextUntil('.disposition-row').not('.demo-sub-row, .demo-sub2-row').last() ).find( 'input' ).val( '' );
        }else{
            cloneRow.insertAfter( thisRow ).find( 'input' ).val( '' );
        }
        cloneRow.find('.disposition-actions .remove-disposition').show();
        cloneRow.find('.row-break').show();
    });

    $('.disposition-actions .remove-disposition:last').show();
    $('.disposition-actions .remove-disposition').first().hide();
    $('.remove-disposition').first().hide();


    $(document).on('click', '.remove-disposition', function(){
        var thisRow = $(this).parent().parent().parent().parent();
        console.log(thisRow);
        if(thisRow && confirm('Are you sure to delete the disposition - ' + thisRow.find('#vaanicampaigndisposition-disposition').val() + ' ?')){
            if(thisRow.prev().length && !thisRow.next().length){
                thisRow.prev().find('.add-disposition').show();
                thisRow.prev().find('.remove-disposition').show();
            }
            $('.remove-disposition').first().hide();
            
            // delete the disposition from table
            var id = thisRow.find('#vaanicampaigndisposition-disposition_id').val();
            if(id){
                // $.ajax({
                //     method: 'POST',
                //     url: {{}},
                //     data: {id : id}
                // }).done(function(data){
                    //     if(data == 'success'){
                        //         thisRow.remove();
                //         Swal.fire('disposition deleted successfully.', '', 'success');
                //     }else{
                    //         Swal.fire(data, '', 'error');
                //         return false;
                //     }
                // });
            }else{
                thisRow.remove();
            }
        }
    });
    
});
    
    </script>
@endsection
