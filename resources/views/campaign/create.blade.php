
@extends('layout')
@section('content')
<style>
    .eye-icon {
        position: absolute;
        right: 25px;
        top: 68%;
        transform: translateY(-50%);
        cursor: pointer;
        width: 25px;
        height: 25px;
        background-image: url("{{asset('assets/img/eye-password.png')}}"); /* Replace with your eye icon image */
        background-size: cover;
    }
    </style>
{{-- <link rel="stylesheet" href="{{asset('assets/css/plugins/select2.min.css')}}">
<script src="{{asset('assets/js/plugins/select2.min.js')}}"></script> --}}
        @php

        //echo "<pre>";print_r($users);exit;
            // $isEdit = isset($campaign);
            // $route = $isEdit ? route('campaign.update', $campaign->id) : route('client.store');
            // // route('client.store')
            // $method = $isEdit ? 'PUT' : 'POST';
            // $title = $isEdit ? 'Edit campaign' : 'Create campaign';
            // $button = $isEdit ? 'Update' : 'Create';
        @endphp
        

<main id="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>Create Campaign</h1>
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
            <form method="POST" action="{{ route('campaign.store') }}"  enctype="multipart/form-data">
                    @csrf
                    {{-- @if($isEdit)
                            @method('PUT')
                    @endif --}}
                    
                <div class="row">
                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="campaign">Campaign Name :</label>
                    <input type="text" class="form-control" name="campaign" id="campaign" value="{{old('campaign')}}" required>
                    @error('campaign')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="mobileno">Mobile No.</label>
                    <input type="text" class="form-control" minlength="10" maxlength="10" name="mobileno" id="mobileno" placeholder="Mobile" value="{{ old('mobileno') }}" required onkeypress="return isNumberKey(event)" onpaste="return false">

                    {{-- {{$client->mobileno}} --}}
                    @error('mobileno')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="distrubution">Distrubution Type :</label>
                    <select id="distrubution" class="form-control" name="distrubution" required>
                        <option value="">Select......</option>
                    <option value="auto">Auto</option>
                    <option value="manual">Manual</option>
                    </select>
                    @error('distrubution')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6 mb-4 disttpye">
                    <label class="form-label" for="dismethod">Distrubution Method :</label>
                    <select id="dismethod" class="form-control" name="dismethod">
                        <option value="">Select......</option>
                    <option value="1">Linear</option>
                    <option value="2">Round Robin</option>
                    </select>
                    @error('dismethod')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-lg-6">
                            <label class="form-label" for="interperagent">Interaction per Agent :</label>
                            <input type="number" class="form-control"  name="interperagent" id="interperagent" value="{{old('interperagent')}}"  min="0" required>
                            @error('interperagent')
                                <span>{{ $message }}</span>
                            @enderror
                        </div>
        
                        <div class="col-lg-6">
                            <label class="form-label" for="sla">SLA in Minutes :</label>
                            <input type="number" class="form-control"  name="sla" id="sla" value="{{old('sla')}}"  min="0" required>
                            @error('sla')
                                <span>{{ $message }}</span>
                            @enderror
                        </div>
                    </div> 
                </div>


                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="disposition">Disposition :</label>
                    <select id="disposition" class="form-control" name="disposition" required>
                        <option value="">Select......</option>
                    @foreach ($plandispo as $plandispoid => $plandisponame)
                            <option value="{{ $plandispoid }}">{{ $plandisponame }}</option>
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
                            <input type="text" id="holidayname" name="holidayname" class="form-control" placeholder="name" autocomplete="off">
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
                            <option value="{{ $daycode }}">{{ $dayname }}</option>
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
                            <label class="form-label" for="fromTime">Call window From :</label>
                            <input type="text" id="fromTime" name="fromTime" class="form-control timepicker" value="{{old('fromTime')}}" required>
                            @error('fromTime')
                            <span>{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="form-label" for="toTime">Call window To :</label>
                            <input type="text" id="toTime" name="toTime" class="form-control timepicker" value="{{old('toTime')}}" required>
                            @error('toTime')
                            <span>{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="mappedqueue">Queue mapping :</label>
                    <div class="input-group">
                    <select id="mappedqueue" class="form-control select2-multiple" name="mappedqueue[]" multiple required>
                            @foreach ($queue as $queueid => $queuename)
                            <option value="{{ $queueid }}">{{ $queuename }}</option>
                        @endforeach
                        </select>
                    </div>
                    @error('mappedqueue[]')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="Autoreply">Auto Reply :</label>
                    <select id="Autoreply" class="form-control" name="Autoreply" >
                        <option value="">Select</option>
                    @foreach ($templetelist as $templeteid => $templetename)
                            <option value="{{ $templeteid }}">{{ $templetename }}</option>
                        @endforeach
                    </select>
                    @error('Autoreply')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="status">Status :</label>
                    <select id="status" class="form-control" name="status" required>
                    <option value="1">Active</option>
                    <option value="2">Inactive</option>
                    </select>
                    @error('status')
                        <span>{{ $message }}</span>
                    @enderror
                </div>


                <div class="col-lg-12 mb-4">
                    <hr>
                    <div class="social-media-container">
                        <h5 class="mt-2">WhatsApp</h5>
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="hsmusername">HSM Username :</label>
                                    <input type="text" id="hsmusername" name="hsmusername" class="form-control" value="{{old('hsmusername')}}" required>
                                    @error('hsmusername')
                                    <span>{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6 mb-4">
                                <div class="form-group">
                                    <label class="form-label" for="hsmpassword">HSM Password :</label>
                                    <input type="password" id="hsmpassword" name="hsmpassword" class="form-control" value="{{old('hsmpassword')}}" required><span id="toggle-password" class="eye-icon"></span>
                                    @error('hsmpassword')
                                    <span>{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label" for="hsmtwousername">HSM Two ways Username :</label>
                                    <input type="text" id="hsmtwousername" name="hsmtwousername" class="form-control" value="{{old('hsmtwousername')}}" required>
                                    @error('hsmtwousername')
                                    <span>{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label" for="hsmtwopassword">HSM Two ways Password :</label>
                                    <input type="password" id="hsmtwopassword" name="hsmtwopassword" class="form-control" value="{{old('hsmtwopassword')}}" required><span id="toggletwo-password" class="eye-icon"></span>
                                    @error('hsmtwopassword')
                                    <span>{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button class="btn btn-primary submit_button" type="submit" disabled>Save</button>
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
                    defaultTime: '08:00',
                    startTime: '12:00',
                    dynamic: false,
                    dropdown: true,
                    scrollbar: true
        });

        $('#toggle-password').click(function() {
            var passwordInput = $('#hsmpassword');
            var passwordFieldType = passwordInput.attr('type');
            if (passwordFieldType === 'password') {
                passwordInput.attr('type', 'text');
                //  $('#toggle-password').css('background-image', url("{{asset('assets/img/eye-o.png')}}"));
            } else {
                // $('#toggle-password').css('background-image', url("{{asset('assets/img/eye-password.png')}}"));
                passwordInput.attr('type', 'password');
            }
        });

        $('#toggletwo-password').click(function() {
            var passwordInput = $('#hsmtwopassword');
            var passwordFieldType = passwordInput.attr('type');
            if (passwordFieldType === 'password') {
                passwordInput.attr('type', 'text');
                //  $('#toggle-password').css('background-image', url("{{asset('assets/img/eye-o.png')}}"));
            } else {
                // $('#toggle-password').css('background-image', url("{{asset('assets/img/eye-password.png')}}"));
                passwordInput.attr('type', 'password');
            }
        });

        function appendDateElements() {
        $('#holidaydate').css('display', 'block');
        $('#holidaydate').html(`
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">From Date:</label>
                    <input type="text" id="date_from" name="date_from" class="form-control" placeholder="From Date" autocomplete="off" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">To Date:</label>
                    <input type="text" id="date_to" name="date_to" class="form-control" placeholder="To Date" autocomplete="off" required>
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

        $('#campaign').blur(function() {
            var campaign = $(this).val();
            var campaignlist = @json($campaignlist);
            //console.log(campaignlist);
            var campainfound = campaignlist.includes(campaign);
            if (campainfound === false) {
                $('.submit_button').prop('disabled', false);
            } else {
                swal.fire('Campaign is already exist.');
                $('.submit_button').prop('disabled', true);
            }
            console.log(campainfound);
        });

        $('#hsmusername').blur(function() {
            var hsmusername = $(this).val();
            var hsmUsernames = @json($hsmUsernames ?? '');
            //console.log(campaignlist);
            var hsmusernamefound = hsmUsernames.includes(hsmusername);
            if (hsmusernamefound === false) {
                $('.submit_button').prop('disabled', false);
            } else {
                swal.fire('hsmUsername is already exist.');
                $('.submit_button').prop('disabled', true);
            }
        });

        $('#hsmtwousername').blur(function() {
            var hsmtwousername = $(this).val();
            var hsmtwoUsernames = @json($hsmtwoUsernames ?? '');
            var hsmtwousernamefound = hsmtwoUsernames.includes(hsmtwousername);
            if (hsmtwousernamefound === false) {
                $('.submit_button').prop('disabled', false);
            } else {
                swal.fire('hsmtwousername is already exist.');
                $('.submit_button').prop('disabled', true);
            }
        });


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

function isNumberKey(evt) {
        var charCode = (evt.which) ? evt.which : event.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
        }
        return true;
    }

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

        //$('#mappedqueue option[value="All"]').prop('selected', true); // Pre-select 'All'
                    
    // Listen for changes in the select element
    //$('#mappedqueue').on('change', function () {
        //if ($('#mappedqueue option:selected').length > 1) {
           // // If more than one option is selected, deselect the 'All' option
           // $('#mappedqueue option[value="All"]').prop('selected', false);
       // } else if ($('#mappedqueue option:selected').length === 0) {
//// If no options are selected, re-select the 'All' option
           // $('#mappedqueue option[value="All"]').prop('selected', false);
       // }
   // });
    //$('#mappedqueue').change(function () {
       //     var selectedValue = $(this).val();

       // if (selectedValue != 'All') {
        //    $('#mappedqueue option[value="All"]').removeAttr('selected');
      //  }else{
       //     $('#mappedqueue option[value="All"]').removeAttr('selected');
       // }
    //});

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