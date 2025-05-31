@extends('layout')

@section('content')
<style>
    div.dataTables_wrapper{
        overflow: hidden;
    overflow-x: auto;
    }
    table.dataTable thead .sorting{white-space: nowrap;}
    .btn-primary {
    background-color: #ef933d;
    border-color: #ef933d;
}
</style>

<div id="main" class="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle">
                <h1>Whatsapp CRM Report</h1>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="content">
            <div class="card-body">
                <div class="row mb-4 align-items-end inline-form">
                    <div class="col">
                        <label class="form-label" >From Date: </label>
                        <input type="text" id="date_from" class="form-control" placeholder="From Date" required autocomplete="off">
                    </div>
                    <div class="col">
                        <label class="form-label" >To Date: </label>
                        <input type="text" id="date_to" class="form-control" placeholder="To Date" required autocomplete="off">
                    </div>

                        <div class="col">
                            <div class="input-group">
                                <label class="form-label" >User: </label>
                                <select id="user" class="form-control select2-multiple" name="user[]" multiple required>
                                </select>
                            </div>
                        </div>
                    <div class="col-auto">
                        <button id="filter" class="btn btn-primary">filter</button>
                        <button id="csv_export_button" class="btn btn-outline-success"><i class="bi bi-download"></i> CSV</button>
                    </div>
                </div>

                <table class="table table-striped" id="chats_table">
                    <thead>
                        <tr>  
                            <th>#</th>
                            <th>ID</th>
                            <th>Is_closed</th>
                            {{-- <th>Client</th>
                            <th>Campaign</th> --}}
                            <th>Cust No.</th>
                            <th>Cust Name</th>
                            <th>Assign to</th>
                            <th>Assign at</th>
                            <th>Assign by</th>
                            <th>Open at</th>
                            <th>Dispo</th>
                            <th>Sub_dispo</th>
                            <th>Remark</th>
                            <th>Closed At</th>
                        </tr>
                    </thead>
                </table>
            </div>
        {{-- <div class="col-md-3" style="margin-top: 25px;">
            <button id="csv_export_button" class="btn btn-primary float-right">Export CSV</button>
            <button id="filter" class="btn btn-success">filter</button>
        </div> --}}
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

        // $('#date_from').datepicker({
        //     format: 'yyyy-mm-dd',
        //     autoclose: true,
        //     minDate: new Date(currentYear, currentMonth-1, currentDate),
        //     maxDate: today,
        //     changeMonth: true,
        //     changeYear: true,
        // });

        // $('#date_to').datepicker({
        //     format: 'yyyy-mm-dd',
        //     autoclose: true,
        //     minDate: new Date(currentYear, currentMonth-1, currentDate),
        //     maxDate: today,
        //     changeMonth: true,
        //     changeYear: true,
        // });     

        $(function () {
            var today = new Date();

            // Initialize the first datepicker
            $('#date_from').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true,
                minDate: new Date(2024, 0, 1), 
                changeMonth: true,
                changeYear: true,
                maxDate: today,
            });

            // Initialize the second datepicker
            $('#date_to').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true,
                changeMonth: true,
                changeYear: true,
                maxDate: today,
            });

            // Handle the change event of the first datepicker
            $('#date_from').on('change', function () {
                var fromDate = $(this).datepicker('getDate'); // Get the selected date
                
                if (fromDate) {
                    var maxToDate = new Date(fromDate);
                    maxToDate.setDate(fromDate.getDate() + 29); // Add 30 days

                    // Ensure the maxToDate doesn't exceed today's date
                    if (maxToDate > today) {
                        maxToDate = today; // Set the maxToDate to today if it exceeds today
                    }

                    // Update the 'to' datepicker's range
                    $('#date_to').datepicker('option', 'minDate', fromDate);
                    $('#date_to').datepicker('option', 'maxDate', maxToDate);


                    // Reset the 'to' datepicker value
                    $('#date_to').datepicker('setDate', fromDate);
                }
            });
        });

        $.ajax({
                url: "{{ route('data.userloginlist') }}",
                type: 'GET',
                dataType: 'json',
                success: function (data) {
                    // console.log(data);
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

                // console.log(date_from);
                // console.log(user);
                // console.log(date_to);

                $.ajax({
                    url: "{{ route('csv.crmreport') }}",
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

        $('#filter').click(function() {
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
                // console.log(date_from);
                // console.log(user);
                // console.log(date_to);

                $.ajax({
                        url: "{{ route('list.chatslist') }}",
                        type: "GET",
                        data: {
                            date_from: date_from,
                            date_to: date_to,
                            user: user,
                        },
                        headers: {
                            "X-CSRF-TOKEN": csrfToken // Include the CSRF token in the headers
                        },
                        success: function (data) {

                            if ($.fn.DataTable.isDataTable('#chats_table')) {
                                    $('#chats_table').DataTable().clear().destroy();
                                }

                                var table = $('#chats_table').DataTable({
                                    data: data.data, 
                                    columns: [
                                        { 
                                            data: 'chat_id',
                                            render: function (data, type, row, meta) {
                                                // Calculate the serial number using the row index
                                                var srNo = meta.row + 1;
                    
                                                return srNo;
                                            },
                                            orderable: false,
                                            searchable: false
                                        },
                                        {
                                            data: 'chat_id',
                                            render: function(data, type, row) {
                                                // var viewUrl = "{{ url('chats')}}/" + data + "/show";
                                                // return "<a href='" + viewUrl + "' target='_blank'>" + data + "</a>";
                                                return data;
                                            },
                                            orderable: false,
                                            searchable: false
                                        },
                                        { 
                                            data: 'is_closed', 
                                            name: 'is_closed',
                                            render: function (data, type, row) {
                                                if (data == 1) {
                                                    return 'No';
                                                }
                                                else{
                                                    return 'Yes';
                                                }
                                            }
                                        },
                                        // { data: 'campaign_name', name: 'campaign_name' },
                                        {
                                            "data": "cust_unique_id",
                                            "name": "cust_unique_id",
                                            "render": function (data, type, row) {
                                            if (type === 'display') {
                                                    var viewUrl = "{{ url('allchats')}}/" + data + "/showAll";
                                                    return "<a href='" + viewUrl + "' target='_blank'>" + data + "</a>";
                                                    // Remove the first leading "91" from the cust_unique_id
                                                    // return data.startsWith('91') ? data.substring(2) : data;
                                                }
                                                return data; // For other types or when data is not displayed
                                            }
                                        },
                                        { data: 'cust_name', name: 'cust_name' },
                                        { data: 'user_name', name: 'user_name' },
                                        { data: 'assigned_at', name: 'assigned_at' },
                                        { 
                                            data: 'assigned_by', 
                                            name: 'assigned_by',
                                            render: function(data, type, row) {
                                                if (row.assignby == '0' && row.assignto != null) {
                                                    return 'System';
                                                }else if(data !== '0' || data !== null && row.assigned_to != null){
                                                    return data;
                                                }

                                            }
                                        },
                                        // { data: 'assigned_by', name: 'assigned_by' },
                                        { data: 'created_at', name: 'created_at' },
                                        { data: 'dispo', name: 'dispo' },
                                        { data: 'sub_dispo', name: 'sub_dispo' },
                                        { data: 'remark', name: 'remark' },
                                        { data: 'closed_at', name: 'closed_at' },
                                        
                                    ]
                                });
                        }
                });

            }
        });
        
        function maskMobileNumber(mobileNumber) {
        // Implement your masking logic here
        // For example, you can replace all but the last 4 digits with asterisks
        return '********' + mobileNumber.substr(-4);
        }


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

        // if (type === 'display') {
        //                                     // Replace the mobile number with a masked version
        //                                     return maskMobileNumber(data);
        //                                     }
</script>



@endsection