@extends('layout')

@section('content')
<main id="main" > 
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>User List</h1>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('users.create') }}"><i class='bi bi-plus-lg'></i> Create User</a>
            </div>
        </div>
    </div>

    {{-- @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif --}}

    @if(session('data.userRole') === 'super_admin')
        <div class="card">
            <div class="card-body">
                <table class="table table-striped" id="user_table">
                    <thead>
                        <tr>
                            <th>Sr.</th>
                            <th>Name</th>
                            <th>Queue</th>
                            <th>Client</th>
                            <th>Role</th>
                            <th>Status</th>
                            {{-- <th>Created</th> --}}
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
       @endif 
       @if(session('data.userRole') === 'manager')
        <div class="card pt-4">
            <div class="card-body">
                <table class="table table-striped" id="usermanager_table">
                    <thead>
                        <tr>
                            <th>Sr.</th>
                            <th>Name</th>
                            <th>Queue</th>
                            <th>Client</th>
                            <th>Role</th>
                            <th>Status</th>
                            {{-- <th>Created</th> --}}
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
       @endif 
</main>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script>
        $(document).ready(function(){
            // Get the CSRF token value from the meta tag
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            $('#user_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('users.list') }}",
                    type: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken // Include the CSRF token in the headers
                    }
                },
                columns: [
                    { 
                        data: 'id',
                        render: function (data, type, row, meta) {
                            // Calculate the serial number using the row index
                            var srNo = meta.row + 1;

                            return srNo;
                        },
                        orderable: false,
                        searchable: false
                    },
                    { data: 'name', name: 'name' },
                    {
                            data: 'queue_names',
                            name: 'queue_names',
                            render: function (data, type, row) {
                                if (data) {
                                    // Split the names into an array using a delimiter (e.g., comma)
                                    var names = data.split(','); // Replace ',' with the actual delimiter

                                    // Create an unordered list (bulleted list)
                                    var list = '<ul>';
                                    names.forEach(function (name) {
                                        // Add each name as a list item
                                        list += '<li>' + name.trim() + '</li>';
                                    });
                                    list += '</ul>';
                                    return list;
                                } else {
                                    // Handle the case where data is empty or not in the expected format
                                    return data;
                                }
                            }
                        },
                    { data: 'client', name: 'client' },
                    { data: 'role', name: 'role' },
                    { 
                        data: 'status', 
                        name: 'status',
                        render: function (data, type, row) {
                            if (data == 1 || data == 0) {
                                return '<b class="text-success">Active</b>';
                            } else{
                                return '<b class="text-danger">Inactive</b>';
                            }
                        }
                    },
                    {
                        data: 'id',
                        render: function(data, type, row) {
                            var editUrl = "{{ url('users') }}/" + data + "/edit";
                            var resetUrl = "{{ url('password/req') }}/" + data;
                            var editButton = "<a href='" + editUrl + "' class='' title='Edit' ><i class='bi bi-pencil-square'></i></a>";
                            // var resetLink = "<a href='" + resetUrl + "' title='Reset password for the user' class='text-warning status-select' style='cursor: pointer;' key-value = "+data+" ><i class='bi bi-key-fill'></i></a>";
                            var deleteForm = "<a class='text-danger status-select' title='Delete'  style='cursor: pointer;' key-value = "+data+" onclick='confirmDelete(event)'><i class='bi bi-trash3-fill'></i></a>";

                            return editButton + " " + " " + deleteForm ;
                        },
                        orderable: false,
                        searchable: false
                    }
                ]
            }); 


        $('#usermanager_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('users.manageruserlist') }}",
                    type: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken // Include the CSRF token in the headers
                    }
                },
                columns: [
                    { 
                        data: 'id',
                        render: function (data, type, row, meta) {
                            // Calculate the serial number using the row index
                            var srNo = meta.row + 1;

                            return srNo;
                        },
                        orderable: false,
                        searchable: false
                    },
                    { data: 'name', name: 'name' },
                    {
                            data: 'queue_names',
                            name: 'queue_names',
                            render: function (data, type, row) {
                                if (data) {
                                    // Split the names into an array using a delimiter (e.g., comma)
                                    var names = data.split(','); // Replace ',' with the actual delimiter

                                    // Create an unordered list (bulleted list)
                                    var list = '<ul>';
                                    names.forEach(function (name) {
                                        // Add each name as a list item
                                        list += '<li>' + name.trim() + '</li>';
                                    });
                                    list += '</ul>';
                                    return list;
                                } else {
                                    // Handle the case where data is empty or not in the expected format
                                    return data;
                                }
                            }
                        },
                    { data: 'client', name: 'client' },
                    { data: 'role', name: 'role' },
                    { 
                        data: 'status', 
                        name: 'status',
                        render: function (data, type, row) {
                            if (data == 1 || data == 0) {
                                return '<b class="text-success">Active</b>';
                            } else{
                                return '<b class="text-danger">Inactive</b>';
                            }
                        }
                    },
                    {
                        data: 'id',
                        render: function(data, type, row) {
                            var editUrl = "{{ url('users') }}/" + data + "/edit";
                            var resetUrl = "{{ url('password/req') }}/" + data;
                            var editButton = "<a href='" + editUrl + "' class='' title='Edit' ><i class='bi bi-pencil-square'></i></a>";
                            // var resetLink = "<a href='" + resetUrl + "' title='Reset password for the user' class='text-warning status-select' style='cursor: pointer;' key-value = "+data+" ><i class='bi bi-key-fill'></i></a>";
                            var deleteForm = "<a class='text-danger status-select' title='Delete'  style='cursor: pointer;' key-value = "+data+" onclick='confirmDelete(event)'><i class='bi bi-trash3-fill'></i></a>";

                            return editButton + " " + " " + deleteForm ;
                        },
                        orderable: false,
                        searchable: false
                    }
                ]
            }); 

        

    });

    function confirmDeleteformanager(event) {
                event.preventDefault();
                var deleteUrl = event.currentTarget.getAttribute('href');
                var keyValue = event.currentTarget.getAttribute('key-value');

                Swal.fire({
                    title: "Are you sure?",
                    text: "You are about to delete the record with ID: " + keyValue,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Delete",
                }).then((result) => {
                    if (result.isConfirmed) {
                       // var userId = $(this).attr('key-value');
                        $.ajax({
                            url: "{{ url('users') }}/" + keyValue + "/status",
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: {
                                req_type: 'delete', status: '2'
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Reload the datatable or perform any necessary updates
                                    $('#user_table').DataTable().ajax.reload();
                                }
                            }
                        });
                    }
                });
        }

    function confirmDelete(event) {
                event.preventDefault();
                var deleteUrl = event.currentTarget.getAttribute('href');
                var keyValue = event.currentTarget.getAttribute('key-value');

                Swal.fire({
                    title: "Are you sure?",
                    text: "You are about to delete the record with ID: " + keyValue,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Delete",
                }).then((result) => {
                    if (result.isConfirmed) {
                       // var userId = $(this).attr('key-value');
                        $.ajax({
                            url: "{{ url('users') }}/" + keyValue + "/status",
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: {
                                req_type: 'delete', status: '2'
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Reload the datatable or perform any necessary updates
                                    $('#user_table').DataTable().ajax.reload();
                                }
                            }
                        });
                    }
                });
        }

    
    </script>

@endsection