
@extends('layout')
@section('content')
<?php
//echo "<pre>";print_r($client);exit;
    ?>
<main id="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>Client information</h1>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('client.create') }}"> <i class='bi bi-plus-lg'></i> Create Client</a>
            </div>
        </div>
    </div>
    <div class="card pt-4">
        <div class="content">

            <!--success / error-->
                @if(Session::get('success'))
                    <?php $message = Session::get('success') ?>
                    <?php echo '<script>swal.fire({text:"'. $message .'",icon:"success",timer:3000,showConfirmButton:false});</script>' ?>
                @endif
                
                @if(Session::get('error'))
                    <?php $message = Session::get('error') ?>
                    <?php echo '<script>swal.fire({text:"'. $message .'",icon:"error",timer:3000,showConfirmButton:false});</script>' ?>
                @endif
        </div>
        <div class="card-body">
            {{-- <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ApiName</th>
                            <th>VendorName</th>
                            <th>Company</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($module as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->apiname }}</td>
                            <td>{{ $item->vendorname}}</td>
                            <td>{{ $item->company }}</td>
                            <td>
                                <a href="{{  route('module.edit', $item->id) }}" title="Edit module"><button class="btn btn-primary btn-sm"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> Edit</button></a>
                                <form method="POST" action="{{ url('/module' . '/' . $item->id) }}" accept-charset="UTF-8" style="display:inline">
                                    {{ method_field('DELETE') }}
                                    {{ csrf_field() }}
                                    <a href="{{route('module.delete',['id' => $item->id])}}"><button type="submit" class="btn btn-danger btn-sm" title="Delete module" onclick="return confirm(&quot;Confirm delete?&quot;)"><i class="fa fa-trash-o" aria-hidden="true"></i> Delete</button></a>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div> --}}
            <table class="table table-striped" id="client_table">
                <thead>
                    <tr>
                        <th>Sr.No</th>
                        <th>Client name</th>
                        <th>Email Id.</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                </table>
        </div>
    </div>
</main>  

    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script>
            $(document).ready(function(){
                // Get the CSRF token value from the meta tag
                var csrfToken = $('meta[name="csrf-token"]').attr('content');

                $('#client_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('list.client') }}",
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
                        { data: 'email', name: 'email' },
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
                                var editUrl = "{{ url('client')}}/" + data + "/edit";
                                var deleteUrl = "{{ url('/client/delete')}}/" + data;
                                var editButton = "<a href='" + editUrl + "' class='' title='Edit'><i class='bi bi-pencil-square'></i></a>";
                                var deleteForm = "<a href='" + deleteUrl + "' class='text-danger' title='Delete' key-value='" + data + "' onclick='confirmDelete(event)'><i class='bi bi-trash3-fill'></i></a>";
                                return editButton + " " + deleteForm;
                            },
                            orderable: false,
                            searchable: false
                        }
                    ]
                });
    
            });
            
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
                        window.location.href = deleteUrl;
                    }
                });
                }
        </script>
@endsection





