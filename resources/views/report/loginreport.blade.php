@extends('layout')

@section('content')

<div id="main" class="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle">
                <h1>Login Activity Report</h1>
            </div>
        </div>
    </div>
    <div class="row"></div>
    <div class="row justify-content-center align-items-center" style="height: 77vh;">
        <div class="col-sm-4 card">
            <div class="row px-4 py-5">
                <div class="col-md-12 mb-4">
                    <label class="form-label" >From Date: </label>
                    <input type="text" id="date_from" class="form-control" placeholder="From Date" required autocomplete="off">
                </div>
                <div class="col-md-12 mb-4">
                        <label class="form-label" >To Date: </label>
                    <input type="text" id="date_to" class="form-control" placeholder="To Date" required autocomplete="off">

            </div>
            <div class="col-md-12 mb-4">
                    <label class="form-label" >User : </label>
                    <select id="user" class="form-control select2-multiple" name="user[]" multiple required>
                    </select>
            </div>
        <div class="col-md-12 text-center">
            <button id="csv_export_button" class="btn btn-outline-success"><i class="bi bi-download"></i> CSV</button>
        </div>
    </div>
</div>


<script>
$(document).ready(function(){

        $('.select2-multiple').select2({
        placeholder: "Select",
        allowClear: true
        });

        var today = new Date();
        var date = new Date();
		var currentMonth = date.getMonth();
		var currentDate = date.getDate();
		var currentYear = date.getFullYear();

        $('#date_from').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            minDate: new Date(currentYear, currentMonth-3, currentDate),
            maxDate: today,
            changeMonth: true,
            changeYear: false,
        });

        $('#date_to').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            minDate: new Date(currentYear, currentMonth-3, currentDate),
            maxDate: today,
            changeMonth: true,
            changeYear: false,
        });     

        $.ajax({
                url: "{{ route('data.userloginlist') }}",
                type: 'GET',
                dataType: 'json',
                success: function (data) {
                    console.log(data);
                    var options = '<option value="All">All</option>';
                    $.each(data, function (name, id) {
                        options += '<option value="' + id + '">' + name + '</option>';
                    });

                    $('#user').append(options);
                    //$('#user option[value="All"]').prop('selected', true); // Pre-select 'All'
                    
                    // Listen for changes in the select element
                    $('#user').on('change', function () {
                        if ($('#user option:selected').length > 1) {
                            // If more than one option is selected, deselect the 'All' option
                            $('#user option[value="All"]').prop('selected', false);
                        } else if ($('#user option:selected').length === 0) {
                            // If no options are selected, re-select the 'All' option
                            $('#user option[value="All"]').prop('selected', false);
                        }
                    });

                    // Initialize select2, assuming you're using select2
                    $('#user').select2();
                }
            });

        $('#user').change(function () {
            var selectedValue = $(this).val();

            if (selectedValue !== 'All') {
                $('#user option[value="All"]').removeAttr('selected');
            }
        });

        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        $('#csv_export_button').click(function(){
            
            var date_from = $('#date_from').val();
            var date_to = $('#date_to').val();
            var user = $('#user').val();

            if(date_from == ''){
                swal.fire('Please enter from date.');
                return false;
            }
            else if(date_to == '') {
                swal.fire('Please enter to date.');
                return false;
            }
            else if(user == '' || user == undefined){
                swal.fire('Please enter user.');
                return false;
            }
            else{

                console.log(date_from);
                console.log(user);
                console.log(date_to);

                $.ajax({
                    url: "{{ route('csv.loginreport') }}",
                    type: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken
                    },
                    data: {
                    date_from: date_from,
                    date_to: date_to,
                    user: user
                    },
                    success: function (response) {
                       
                        console.log(response);
                        if (response.download) {
                        // Create a temporary <a> element to trigger the file download
                        var link = document.createElement('a');
                        link.href = response.file_url;
                        link.download = response.file_name;
                        link.style.display = 'none';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        }
                    },
                    error: function (xhr, status, error) {
                        
                        console.log(xhr);
                    }
                });
                
            }

        });

});

document.addEventListener('DOMContentLoaded', function() {
            var fromTime = document.getElementById('date_from');
            var fromto = document.getElementById('date_to');

            // Disable paste
            fromTime.addEventListener('paste', function(event) {
                event.preventDefault();
            });
            fromto.addEventListener('paste', function(event) {
                event.preventDefault();
            });
        });
</script>



@endsection