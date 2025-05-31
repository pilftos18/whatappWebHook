
@extends('layout')
@section('content')

<main id="main">
    <div class="row">
        <div class="col-lg-12">
            <div class="box-new-design">
                <div class="row align-items-end">
                    <div class="col-lg-12 margin-tb text-start">
                        <div class="pagetitle">
                            <h1>User Assign</h1>
                        </div>
                    </div>
                    <div class="col-md-3 ">
                        <label class="form-label" >Campaign: </label>
                        <select id="campaign" class="form-control" name="campaign" required>
                        </select>
                    </div>
                    <div class="col-md-2 ">
                        <label class="form-label" >From Date: </label>
                        <input type="text" id="date_from" class="form-control" placeholder="From Date" autocomplete="off" required>
                    </div>
                    <div class="col-md-2 ">
                        <label class="form-label" >To Date: </label>
                        <input type="text" id="date_to" class="form-control" placeholder="To Date" autocomplete="off" required>
                    </div>
                    <div class="col-md-2 ">
                        <label class="form-label"></label>
                        <select id="assigning" class="form-control" style="margin-top: 6%;" required>
                            <option value="all" selected>All</option>
                            <option value="unassign">Un Assign</option>
                            <option value="assign" >Assign</option>
                        </select>
                    </div>
                    <div class="col-lg-3" >
                        <button id="assignall" class="btn yellow-bg" style="border-radius: 5px;" disabled><i class="fa fa-check"></i>All</button>
                        <button id="getdata" class="btn btn-success" style="border-radius: 5px;">Get data</button>
                        <button id="assignto" class="btn blue-bg" style="border-radius: 5px;" disabled>Assign</button>
                    </div>
                    <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="userModalLabel">Select User to mapped</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <!-- Client Dropdown -->
                                    <select id="userDropdown" class="form-control">
                                    </select>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary" id="saveassignuser"  data-bs-dismiss="modal">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <hr>
    <table class="table table-striped" id="userassign_table">
        <thead>
            <tr>    
                <th>#</th>
                <th>Campaign</th>
                <th>Cust_id</th>
                <th>Customer Name</th>
                <th>Assign By</th>
                <th>Open at</th>
                <th>Assign to</th>
                <th>Assign at</th>
                <th>Status</th>
            </tr>
        </thead>
    </table>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script> 
<script>
    $(document).ready(function(){
        
        // $('.select2-multiple').select2({
        //     placeholder: "Select",
        //     allowClear: true
        // });

        var today = new Date();
        // today.setDate(today.getDate() - 1);
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
            changeYear: true,
        });

        $('#date_to').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            minDate: new Date(currentYear, currentMonth-3, currentDate),
            maxDate: today,
            changeMonth: true,
            changeYear: true,
        });

        $.ajax({
            url: "{{ route('assign.campaign_list') }}",
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                var options = '<option value="" selected>Select...</option>';
                $.each(data, function(key, value) {
                    options += '<option value="' + key + '">' + value + '</option>';
                });
                $('#campaign').html(options);
            }
        });     

        
        $('#assignto').click( function () {

            var date_from = $("#date_from").val();
            var date_to = $("#date_to").val();
            var campaign = $("#campaign").val();
            var assigning = $("#assigning").val();

        if (date_from == '') {
            swal.fire('Please select from date');
            //loader.style.display = 'none';
            return false;
        } else if (date_to == '') {
            swal.fire('Please select to date');
            //loader.style.display = 'none';
            return false;
        } else if (campaign == '') {
            swal.fire('Please select campaign');
            //loader.style.display = 'none';
            return false;
        } else if (assigning == '') {
            swal.fire('Please select assign');
            //loader.style.display = 'none';
            return false;
        }else {

                $.ajax({
                url: "{{ route('assign.user_list') }}",
                type: 'GET',
                data: {
                            campaign: campaign,
                        },
                dataType: 'json',
                success: function(data) {
                    console.log(data);
                    var options = '<option value="">Select...</option>';

                    $.each(data, function(name, id) {
                        options += '<option value="' + id + '">' + name + '</option>';
                    });

                    $('#userDropdown').html(options);
                    $('#userModal').modal('show');
                }
                }); 

        }

        });


        $('#assignall').click(function () {
        // Select all checkboxes inside the userassign_table
        var date_from = $("#date_from").val();
            var date_to = $("#date_to").val();
            var campaign = $("#campaign").val();
            var assigning = $("#assigning").val();

        if (date_from == '') {
            swal.fire('Please select from date');
            $('#assignto').prop('disabled', true);
            //loader.style.display = 'none';
            return false;
        } else if (date_to == '') {
            swal.fire('Please select to date');
            $('#assignto').prop('disabled', true);
            //loader.style.display = 'none';
            return false;
        } else if (campaign == '') {
            swal.fire('Please select campaign');
            $('#assignto').prop('disabled', true);
            //loader.style.display = 'none';
            return false;
        } else if (assigning == '') {
            swal.fire('Please select assign');
            $('#assignto').prop('disabled', true);
            //loader.style.display = 'none';
            return false;
        }else {


            var firstCheckbox = $('#userassign_table input[type="checkbox"]:first');
            var areAllChecked = firstCheckbox.is(':checked');

            var table = $('#userassign_table').DataTable();
            var $checkboxes = table.rows().nodes().to$().find('input[type="checkbox"]');
            
            if (areAllChecked) {
                // Uncheck all checkboxes
                $checkboxes.prop('checked', false);
            } else {
                // Check all checkboxes
                $checkboxes.prop('checked', true);
            }

            // Check if at least one checkbox is checked and enable/disable the button accordingly
            var atLeastOneChecked = $checkboxes.is(':checked');
            $('#assignto').prop('disabled', !atLeastOneChecked);
        }

        }); 

        var table = $('#userassign_table').DataTable();
        var $assignto = $('#assignto');
        $(document).on('change', '#userassign_table .checkbox', function() {
            var $checkboxes = $('#userassign_table .checkbox:checked');
            if ($checkboxes.length > 0) {
            $assignto.prop('disabled', false);
            } else {
            $assignto.prop('disabled', true);
            }
        });


        var csrfToken = $('meta[name="csrf-token"]').attr('content');
            
        $('#getdata').click(function(){

            var date_from = $("#date_from").val();
            var date_to = $("#date_to").val();
            var campaign = $("#campaign").val();
            var assigning = $("#assigning").val();

            if (date_from == '') {
                swal.fire('Please select from date');
                $('#assignall').prop('disabled', true);
                //loader.style.display = 'none';
                return false;
            } else if (date_to == '') {
                swal.fire('Please select to date');
                $('#assignall').prop('disabled', true);
                //loader.style.display = 'none';
                return false;
            } else if (campaign == '') {
                swal.fire('Please select campaign');
                $('#assignall').prop('disabled', true);
                //loader.style.display = 'none';
                return false;
            } else if (assigning == '') {
                swal.fire('Please select assign');
                $('#assignall').prop('disabled', true);
                //loader.style.display = 'none';
                return false;
            }else {
            $('#assignall').prop('disabled', false);
            console.log(date_from);
            console.log(date_to);
            console.log(campaign);
            console.log(assigning);
                $.ajax({
                        url: "{{ route('list.userlistassign') }}",
                        type: "GET",
                        data: {
                            date_from: date_from,
                            date_to: date_to,
                            campaign: campaign,
                            assigning : assigning,
                        },
                        headers: {
                            "X-CSRF-TOKEN": csrfToken // Include the CSRF token in the headers
                        },
                        success: function (data) {

                            if ($.fn.DataTable.isDataTable('#userassign_table')) {
                                    $('#userassign_table').DataTable().clear().destroy();
                                }

                                var table = $('#userassign_table').DataTable({
                                    data: data.data, 
                                    columns: [
                                        {
                                            data: 'id',
                                            orderable: false,
                                            className: 'select-checkbox', 
                                            render: function (data, type, row, meta) {
                                                // Generate a unique ID for each checkbox based on the row index
                                                var checkboxId = 'checkbox_' + meta.row;
                                                return '<input type="checkbox" class="checkbox" value = "chat_id['+data+']" id="' + checkboxId + '">';
                                            }
                                        },
                                        { data: 'campaignname', name: 'campaignname' },
                                        // { data: 'cust_unique_id', name: 'cust_unique_id' },
                                        {
                                        "data": "cust_unique_id",
                                        "name": "cust_unique_id",
                                        "render": function (data, type, row) {
                                            if (type === 'display') {
                                                // Remove the first leading "91" from the cust_unique_id
                                                return data.startsWith('91') ? data.substring(2) : data;
                                            }
                                            return data; // For other types or when data is not displayed
                                        }
                                        },
                                        { data: 'customer_name', name: 'customer_name' },
                                        // {data: 'assigned_by',name: 'assigned_by'},
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
                                        { data: 'created_at', name: 'created_at' },
                                        { data: 'assigned_to', name: 'assigned_to' },
                                        { data: 'assigned_at', name: 'assigned_at' },
                                        { 
                                                data: 'status',
                                                name: 'status',
                                                render: function(data, type, row) {
                                                    if (data == 1) {
                                                        return "Active";
                                                    } else if (data == 2) {
                                                        return "Inactive";
                                                    } else {
                                                        return "";
                                                    }
                                                }
                                        }
                                        
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

        $('#saveassignuser').click(function () {
            var table = $('#userassign_table').DataTable();
            var selectedIds = [];
            $('#userassign_table .checkbox').each(function() {
                var $checkbox = $(this);
                if ($checkbox.is(':checked')) {
                    var rowData = table.row($checkbox.closest('tr')).data();
                    var id = rowData.id; 
                    selectedIds.push(id);
                }
            });
            var userid = $('#userDropdown').val();
            if(userid == ''|| userid == null || userid == undefined){
                swal.fire('Make sure user is ready for chat assign.');
                return false;
            }else{
                console.log('Selected IDs:', selectedIds);
                    $.ajax({
                        url: "{{ route('assign.assigned_users') }}",
                        type: 'GET',
                        data: {
                            userid: userid,
                            selectedIds : selectedIds
                                },
                        headers: {
                                "X-CSRF-TOKEN": csrfToken // Include the CSRF token in the headers
                            },
                        success: function(data) {
                            if ($.fn.DataTable.isDataTable('#userassign_table')) {
                                        $('#userassign_table').DataTable().clear().destroy();
                                    }
                            swal.fire(data);
                            $('#assignall').prop('disabled', true);
                            $('#assignto').prop('disabled', true);

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
{{--          table
  .rows((idx, data, node) => {
    return $('input[type=checkbox]').is(':checked');
  }).data(); --}}
</main>
@endsection