
@extends('layout')
@section('content')

        @php

        //print_r($breaks[0]);
            $isEdit = isset($breaks);
            $route = $isEdit ? route('break.update', $campaign_id) : route('break.store');
            // route('client.store')
            $method = $isEdit ? 'PUT' : 'POST';
            $title = $isEdit ? 'Edit break' : 'Create break';
            $button = $isEdit ? 'Update' : 'Create';
        @endphp 
<?php 
?>
<main id="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>{{$title}}</h1>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('break.index') }}"><i class="bi bi-arrow-left"></i></a>
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
                
        
        <div class="col-lg-12 campaign-name">
            <div class="row justify-content-center align-items-center form-box">
                <div class="col-lg-4">
                    <label class="form-label" for="campaign">campaign Name</label>
            <div class="form-group campaignname">
                <select name="campaign" id="campaign" class="form-control" disabled>
                    @foreach ($campaignlist as $campaignId => $campaignName)
                        <option value="{{ $campaignId }}" {{ $campaign_id == $campaignId ? 'selected' : '' }}>{{ $campaignName }}</option>
                    @endforeach
                </select>
                </div>
            </div>
        </div>     
        <br>
        <?php
        if($breaks){
            foreach ($breaks as $key => $value) {
                            //  print_r($value->name);
          ?>  
        <div class="col-lg-12 break-row">
            <div class="row justify-content-center align-items-center form-box">
                <div class="col-lg-4">
            <div class="form-group field-vaanicampaignbreak-break">
                <label class="form-label" for="vaanicampaignbreak-break">Break Name</label>
                <input type="text" name="VaaniCampaignBreak[break][]" value="<?php echo isset($value->name) ? $value->name : ''; ?>" id="vaanicampaignbreak-break" class="form-control" required>
                @error('VaaniCampaignBreak[break][]')
                        {{-- <div class="help-block">{{ $message}}</div> --}}
                @enderror
            </div>
        </div>
        <div class="col-lg-2">
            <label class="form-label" for="vaanicampaignbreak-break">Status</label>
            <div class="form-group field-vaanicampaignbreak-status">
                <select name="VaaniCampaignBreak[status][]" id="vaanicampaignbreak-status" class="form-control">
                    <option value="1" <?php echo (isset($value->status) && $value->status == 1) ? 'selected' : ''; ?>>Active</option>
                    <option value="2" <?php echo (isset($value->status) && $value->status == 2) ? 'selected' : ''; ?>>Inactive</option>
                </select>
                @error('VaaniCampaignBreak[status][]')
                        {{-- <div class="help-block">{{ $message}}</div> --}}
                @enderror
            </div>
        </div>
        <div class="col-lg-2">
            <div class="form-group" style="margin-top: 30px;">
                <a href="#" class="btn btn-sm btn-success add-break">+</a>
                <a href="#" class="btn btn-sm btn-danger remove-break">-</a>
            </div>
        </div>
    </div>
</div>

<?php }
} else { ?>
    <div class="col-lg-12 break-row">
        <div class="row justify-content-center align-items-center form-box">
            <div class="col-lg-4">
                {{-- <input type="hidden" name="VaaniCampaignBreak[b_id][]" value="" id="vaanicampaignbreak-b_id"> --}}
                <div class="form-group field-vaanicampaignbreak-break">
                    <label class="form-label" for="vaanicampaignbreak-break">Break Name</label>
                    <input type="text" name="VaaniCampaignBreak[break][]" value="" id="vaanicampaignbreak-break" class="form-control" required>
                    @error('VaaniCampaignBreak[break][]')
                        {{-- <div class="help-block">{{ $message}}</div> --}}
                @enderror
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-group field-vaanicampaignbreak-status">
                    <label class="form-label" for="vaanicampaignbreak-break">Status</label>
                    <select name="VaaniCampaignBreak[status][]" id="vaanicampaignbreak-status" class="form-control">
                        <option value="1">Active</option>
                        <option value="2">Inactive</option>
                    </select>
                    @error('VaaniCampaignBreak[status][]')
                        {{-- <div class="help-block">{{ $message}}</div> --}}
                @enderror
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-group" style="margin-top: 30px;">
                    <a href="#" class="btn btn-sm btn-success add-break">+</a>
                    <a href="#" class="btn btn-sm btn-danger remove-break">-</a>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <div class="text-center">
        <button class="btn btn-primary mt-4" type="submit">{{ $button }}</button>
    </div> 

</form>
<script>
    // add/remove break row
    $('.remove-break').first().hide();
    $('.add-break').first().hide();

    $(document).on('click', '.add-break', function(){
        var thisRow = $(this).closest('.break-row' );
        var cloneRow = thisRow.clone();
        cloneRow.insertAfter( thisRow ).find( 'input' ).val( '' );
        thisRow.find('.add-break').hide();
        cloneRow.find('.remove-break').show();
    });
    $(document).on('click', '.remove-break', function(){
        var thisRow = $(this).closest('.break-row' );

        if(thisRow && confirm('Are you sure to delete the break - ' + thisRow.find('#vaanicampaignbreak-break').val() + ' ?')){
            if(thisRow.prev().length && !thisRow.next().length){
                thisRow.prev().find('.add-break').show();
                thisRow.prev().find('.remove-break').show();
            }
            $('.remove-break').first().hide();
            thisRow.remove();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
            // Disable copy and paste for the entire page
            document.addEventListener('copy', function(event) {
                event.preventDefault();
            });

            document.addEventListener('cut', function(event) {
                event.preventDefault();
            });

            document.addEventListener('paste', function(event) {
                event.preventDefault();
            });
        }); 

</script>
</main>
@endsection
