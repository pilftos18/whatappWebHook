
@extends('layout')
@section('content')
        @php

        //echo "<pre>";print_r($campaign->wp_crud);exit;
        $wpCrud = json_decode($campaign->wp_crud, true);
            // $isEdit = isset($campaign);
            // $route = $isEdit ? : route('campaign.store');
            // // route('client.store')
            // $method = $isEdit ? 'PUT' : 'POST';
            // $title = $isEdit ? 'Edit campaign' : 'Create campaign';
            // $button = $isEdit ? 'Update' : 'Create';
        @endphp
        

<main id="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>Edit Campaign</h1>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('campaign.index') }}" title="back"><i class="bi bi-arrow-left"></i></a>
            </div>
        </div>
    </div>
    <div class="card p-4">
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
            <!-- create.blade.php -->
            <form method="POST" action="{{  route('campaign.update', $campaign->id) }}"  enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                <div class="row">
                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="campaign">Campaign Name</label>
                    <input type="text" class="form-control" name="campaign" id="campaign" value="{{$campaign->name ?? ''}}" required readonly>
                    @error('campaign')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="mobileno">Mobile No.</label>
                    {{-- <input type="number" class="form-control" minlength="10" maxlength="10" name="mobileno" id="mobileno" value="{{ isset($campaign->wp_number) ? str_replace('91', '', $campaign->wp_number) : '' }}" required> --}}

                    <input type="text" class="form-control" minlength="10" maxlength="10" name="mobileno" id="mobileno" placeholder="Mobile" value="{{ isset($campaign->wp_number) ? str_replace('91', '', $campaign->wp_number) : '' }}" required onkeypress="return isNumberKey(event)" onpaste="return false">

                    {{-- {{$client->mobileno}} --}}
                    @error('mobileno')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="distrubution">Distrubution Type</label>
                    <select id="distrubution" class="form-control" name="distrubution" required>
                        <option value="">Select......</option>
                    <option value="auto" {{ old('distrubution', isset($campaign) && $campaign->dist_type === 'auto' ? 'selected' : '' )}}>Auto</option>
                    <option value="manual" {{ old('distrubution', isset($campaign) && $campaign->dist_type === 'manual' ? 'selected' : '' )}}>Manual</option>
                    </select>
                    @error('distrubution')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6 mb-4 disttpye">
                    <label class="form-label" for="dismethod">Distrubution Method</label>
                    <select id="dismethod" class="form-control" name="dismethod" required>
                        <option value="">Select......</option>
                    <option value="1" {{ old('dismethod', isset($campaign) && $campaign->dist_method == '1' ? 'selected' : '' )}}>linear</option>
                    <option value="2" {{ old('dismethod', isset($campaign) && $campaign->dist_method == '2' ? 'selected' : '' )}}>round robins</option>
                    </select>
                    @error('dismethod')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6">
                    <div class="row">
                    <div class="col-lg-6">
                        <label class="form-label" for="interperagent">Interaction per Agent</label>
                        <input type="number" class="form-control"  name="interperagent" id="interperagent" value="{{$campaign->interaction_per_user ?? '' }}"  min="0" required>
                        {{-- {{$campaign->->interaction_per_user}} --}}
                        @error('interperagent')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label" for="sla">SLA</label>
                        <input type="number" class="form-control"  name="sla" id="sla" value="{{$campaign->sla ?? '' }}"  min="0" required>
                        {{-- {{$campaign->sla}} --}}
                        @error('sla')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>
                </div> 
            </div>

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="disposition">Disposition</label>
                    <select id="disposition" class="form-control" name="disposition" required>
                        <option value="">Select......</option>
                    @foreach ($plandispo as $plandispoid => $plandisponame)
                            <option value="{{ $plandispoid }}"  {{ old('disposition', isset($campaign) && $campaign->disposition_id == $plandispoid ? 'selected' : '' )}}>{{ $plandisponame }}</option>
                        @endforeach
                    </select>
                    @error('disposition')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="row">
                        <div class="col">
                            <label class="form-label" >Holiday Name: </label>
                            <input type="text" id="holidayname" name="holidayname" class="form-control" value="{{$campaign->holiday_name ?? '' }}" placeholder="name" autocomplete="off">
                        </div>
                        <div class="col" style="display: none;" id="holidaydate"></div>
                    </div>
                </div>


                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="workingdays">Working days :</label>
                    <div class="input-group">
                    <select id="workingdays" class="form-control select2-multiple" name="workingdays[]" multiple required>
                            <option value="All">All</option>
                            @foreach ($workingdays as $daycode => $dayname)
                            <option value="{{ $daycode }}"  @if(in_array($daycode, explode(',', $campaign->working_days))) selected @endif>{{ $dayname }}</option>
                        @endforeach
                        </select>
                    </div>
                    @error('workingdays[]')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6">
                    <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="form-label" for="fromTime">Call window From</label>
                            <input type="text" id="fromTime" name="fromTime" value="{{$campaign->call_window_from ?? '' }}" class="form-control timepicker" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="form-label" for="toTime">Call window To</label>
                            <input type="text" id="toTime" name="toTime" value="{{$campaign->call_window_to ?? '' }}" class="form-control timepicker" />
                        </div>
                    </div>
                    </div>
                </div>


                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="mappedqueue">Queue mapping :</label>
                    <div class="input-group">
                    <select id="mappedqueue" class="form-control select2-multiple" name="mappedqueue[]" multiple required>
                        @foreach ($queue as $queueid => $queuename)
                        <option value="{{ $queueid }}" @if(in_array($queueid, explode(',', $campaign->queue))) selected @endif>{{ $queuename }}</option>
                    @endforeach
                        </select>
                    </div>
                    @error('mappedqueue[]')
                        <span>{{ $message }}</span>
                    @enderror
                </div>
                

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="Autoreply">Auto Reply :</label>
                    <select id="Autoreply" class="form-control" name="Autoreply">
                        <option value="">Select......</option>
                    @foreach ($templetelist as $templeteid => $templetename)
                            <option value="{{ $templeteid }}" {{ old('Autoreply', isset($campaign) && $campaign->auto_reply_id == $templeteid ? 'selected' : '' )}}>{{ $templetename }}</option>
                        @endforeach
                    </select>
                    @error('Autoreply')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" class="form-control" name="status" required>
                    <option value="1" {{ old('status', isset($campaign) && $campaign->status == 1 ? 'selected' : '' )}}>Active</option>
                    <option value="2" {{ old('status', isset($campaign) && $campaign->status == 2 ? 'selected' : '' )}}>Inactive</option>
                    </select>
                    @error('status')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-12 mb-4">
                    <hr>
                    <div class="social-media-container">
                        <h5 class="mt-2">WhatsApp</h5>
                        <div class="row mb-3">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label" for="hsmusername">HSM Username :</label>
                                    <input type="text" id="hsmusername" name="hsmusername" class="form-control" value="{{ $wpCrud['hsm_userid'] ?? ''}}" readonly>
                                    @error('hsmusername')
                                    <span>{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label" for="hsmtwousername">HSM Two ways Username :</label>
                                    <input type="text" id="hsmtwousername" name="hsmtwousername" class="form-control" value="{{ $wpCrud['twoway_userid'] ?? ''}}" readonly>
                                    @error('hsmtwousername')
                                    <span>{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button class="btn btn-primary" type="submit">Save</button>
                </div> 
                </div>


            </form>

        </div>
    </div>
	<script>
    $(document).ready(function() {
        
        $('.select2-multiple').select2({
            placeholder: "Select",
            allowClear: true
        });

        $('.timepicker').timepicker({
                    timeFormat: 'HH:mm', // Use 'HH' for 24-hour format
                    interval: 30,
                    minTime: '00:00', // Start at midnight
                    maxTime: '23:30', // End at 11:30 PM
                    startTime: '12:00',
                    dynamic: false,
                    dropdown: true,
                    scrollbar: true
        });

        function appendDateElements() {
        $('#holidaydate').css('display', 'block');
        $('#holidaydate').html(`
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">From Date:</label>
                    <input type="text" id="date_from" name="date_from" class="form-control" value="{{date('m/d/Y', strtotime($campaign->holiday_start ?? '')) }}" placeholder="From Date" autocomplete="off" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">To Date:</label>
                    <input type="text" id="date_to" name="date_to" class="form-control" value="{{date('m/d/Y', strtotime($campaign->holiday_end ?? '')) }}" placeholder="To Date" autocomplete="off" required>
                </div>
            </div>
        `);

        initDatepickers();
    }

    // Function to initialize Datepicker
    function initDatepickers() {
        var today = new Date();
        var date = new Date();
        var currentYear = date.getFullYear();

        $('#date_from').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            minDate: today,
            maxDate: new Date(currentYear + 1, 11, 31),
            changeMonth: true,
            changeYear: true,
        });

        $('#date_to').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            minDate: today,
            maxDate: new Date(currentYear + 1, 11, 31),
            changeMonth: true,
            changeYear: true,
        });
    }

    // Event handler for the 'holidayname' input
    $('#holidayname').on('blur', function() {
        var holidaydate = $(this).val();

        if (holidaydate.trim() !== '') {
            appendDateElements();
        } else {
            $('#holidaydate').css('display', 'none');
            $('#holidaydate').html(''); // Clear the content
        }
    });

    $(document).ready(function() {
        var initialHolidayName = $('#holidayname').val();
        if (initialHolidayName.trim() !== '') {
            appendDateElements();
        }
    });

            // Get the initial value of the input field
            var initialValue = $('#fromTime').val();

                // Check the length of the initial value and format accordingly
                if (initialValue.length === 3) {
                    $('#fromTime').val(initialValue.slice(0, 1) + ':' + initialValue.slice(1));
                } else if (initialValue.length === 4) {
                    $('#fromTime').val(initialValue.slice(0, 2) + ':' + initialValue.slice(2));
                }

                // Add event listener to the input field
                $('#fromTime').on('input', function() {
                    // Get the current value of the input field
                    var inputValue = $(this).val();

                    // Remove any non-digit characters
                    inputValue = inputValue.replace(/\D/g, '');

                    // Check the length of the input value and format accordingly
                    if (inputValue.length === 3) {
                        inputValue = inputValue.slice(0, 1) + ':' + inputValue.slice(1);
                    } else if (inputValue.length === 4) {
                        inputValue = inputValue.slice(0, 2) + ':' + inputValue.slice(2);
                    }

                    // Update the input field with the formatted value
                    $(this).val(inputValue);
                });

           // Get the initial value of the input field
                var initialValue = $('#toTime').val();

                // Check the length of the initial value and format accordingly
                if (initialValue.length === 3) {
                    $('#toTime').val(initialValue.slice(0, 1) + ':' + initialValue.slice(1));
                } else if (initialValue.length === 4) {
                    $('#toTime').val(initialValue.slice(0, 2) + ':' + initialValue.slice(2));
                }

                // Add event listener to the input field
                $('#toTime').on('input', function() {
                    // Get the current value of the input field
                    var inputValue = $(this).val();

                    // Remove any non-digit characters
                    inputValue = inputValue.replace(/\D/g, '');

                    // Check the length of the input value and format accordingly
                    if (inputValue.length === 3) {
                        inputValue = inputValue.slice(0, 1) + ':' + inputValue.slice(1);
                    } else if (inputValue.length === 4) {
                        inputValue = inputValue.slice(0, 2) + ':' + inputValue.slice(2);
                    }

                    // Update the input field with the formatted value
                    $(this).val(inputValue);
                });

        var selecteddist_type = $('#distrubution').val();
            if (selecteddist_type == 'auto' ) {
                $('.disttpye').show();
                $('#dismethod').prop('required',true);
            } else {
                $('.disttpye').hide();
                $('#dismethod').prop('required',false);
            } 

        $('#distrubution').change(function() {
            var selecteddist_type = $(this).val();
            if (selecteddist_type == 'auto' ) {
                $('.disttpye').show();
                $('#dismethod').prop('required',true);
            } else {
                $('.disttpye').hide();
                $('#dismethod').prop('required',false);
            } 
        }); 

        $(document).on('keypress','#mobileno',function(e){
            if($(e.target).prop('value').length>=10){  
            if(e.keyCode!=32)
            {return false} 
            }
            var inputValue = e.key;
            if (inputValue === '-' || inputValue === '+' || inputValue === 'E' || inputValue === 'e' ) {
                e.preventDefault(); // Prevent the "-" character from being entered
            }
        });

        $('#interperagent,#sla').keypress(function(event) {
            var inputValue = event.key;
            if (inputValue === '-' || inputValue === '+' || inputValue === 'E' || inputValue === 'e' ) {
                event.preventDefault();
            }
            if($(event.target).prop('value').length>=3){
            if(event.keyCode!=32)
            {return false} 
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
            var fromTimeInput = document.getElementById('fromTime');
            var fromToInput = document.getElementById('toTime');

            // Disable keypress
            fromTimeInput.addEventListener('keypress', function(event) {
                event.preventDefault();
            });
            fromToInput.addEventListener('keypress', function(event) {
                event.preventDefault();
            });
        });
        
        function isNumberKey(evt) {
            var charCode = (evt.which) ? evt.which : event.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
            }
            return true;
        }
    // Listen for changes in the select element
    // $('#mappedqueue').on('change', function () {
    //     if ($('#mappedqueue option:selected').length > 1) {
    //         // If more than one option is selected, deselect the 'All' option
    //         $('#mappedqueue option[value="All"]').prop('selected', false);
    //     } else if ($('#mappedqueue option:selected').length === 0) {
    //         // If no options are selected, re-select the 'All' option
    //         $('#mappedqueue option[value="All"]').prop('selected', false);
    //     }
    // });
    // $('#mappedqueue').change(function () {
    //         var selectedValue = $(this).val();

    //         if (selectedValue != 'All') {
    //             $('#mappedqueue option[value="All"]').removeAttr('selected');
    //         }else{
    //             $('#mappedqueue option[value="All"]').removeAttr('selected');
    //         }
    //     });


        $('#workingdays').on('change', function () {
        if ($('#workingdays option:selected').length > 1) {
            // If more than one option is selected, deselect the 'All' option
            $('#workingdays option[value="All"]').prop('selected', false);
        } else if ($('#workingdays option:selected').length === 0) {
            // If no options are selected, re-select the 'All' option
            $('#workingdays option[value="All"]').prop('selected', false);
        }
    });
    $('#workingdays').change(function () {
            var selectedValue = $(this).val();

            if (selectedValue != 'All') {
                $('#workingdays option[value="All"]').removeAttr('selected');
            }else{
                $('#workingdays option[value="All"]').removeAttr('selected');
            }
        });
        
</script>
</main>
@endsection