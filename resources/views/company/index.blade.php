
@extends('layout')
@section('content')
<main id="main">
    <div class="pagetitle justify-content-between d-flex">
        <h1>Organization List</h1>
        <a class="btn btn-outline-primary btn-sm" href="{{ url('/company/create') }}"><i class='bi bi-plus-lg'></i> Create Organization</a>
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
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>email</th>
                            <th>website</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($company as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->email }}</td>
                            <td>{{ $item->website }}</td>
                            <td>
                                <a href="{{ url('/company/' . $item->id . '/edit') }}" title="Edit company"><button class="btn btn-primary btn-sm"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> Edit</button></a>
                                <form method="POST" action="{{ url('/company' . '/' . $item->id) }}" accept-charset="UTF-8" style="display:inline">
                                    {{ method_field('DELETE') }}
                                    {{ csrf_field() }}
                                    <a href="{{route('company.delete',['id' => $item->id])}}"><button type="submit" class="btn btn-danger btn-sm" title="Delete company" onclick="return confirm(&quot;Confirm delete?&quot;)"><i class="fa fa-trash-o" aria-hidden="true"></i> Delete</button></a>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div> --}}
            <table class="table table-striped" id="user_table">
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Available Credits</th>
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
    
                $('#user_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('company.list') }}",
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
                        { data: 'max_count', name: 'max_count' },
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
                                var editUrl = "{{ url('company')}}/" + data + "/edit";
                                var deleteUrl = "{{ url('/company/delete')}}/" + data;
                                var editButton = "<a href='" + editUrl + "' class=''><i class='bi bi-pencil-square'></i></a>";
                                var deleteForm = "<a href='" + deleteUrl + "' class='text-danger' key-value='" + data + "'><i class='bi bi-trash3-fill'></i></a>";
                                return editButton + " " + deleteForm;
                            },
                            orderable: false,
                            searchable: false
                        }
                    ]
                });
    
            });
        </script>
@endsection


