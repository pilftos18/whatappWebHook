@extends('layout')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>User List</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-success" href="{{ route('users.create') }}">Create New User</a>
            </div>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <table class="table table-bordered" id="user_table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Mobile</th>
            <th>Username</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
        <!-- @foreach ($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->mobile }}</td>
            <td>{{ $user->username }}</td>
            <td>{{ $user->status }}</td>
            <td>
                <form action="{{ route('users.destroy', $user->id) }}" method="POST">
                    <a class="btn btn-info" href="{{ route('users.show', $user->id) }}">Show</a>
                    <a class="btn btn-primary" href="{{ route('users.edit', $user->id) }}">Edit</a>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach -->
    </table>

    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    
    <script>
        $(document).ready(function(){
            // Get the CSRF token value from the meta tag
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            $('#user_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    //"{{ route('users.list') }}"
                    url: "{{ route('users.list') }}",
                    type: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken // Include the CSRF token in the headers
                    }
                },
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'email', name: 'email' },
                    { data: 'mobile', name: 'mobile' },
                    { data: 'username', name: 'username' },
                    { data: 'status', name: 'status' },
                    {
                        data: 'id',
                        render: function(data, type, row) {
                            var editUrl = "{{ url('users') }}/" + data + "/edit";
                            var deleteUrl = "{{ url('users.destroy') }}/" + data;

                            var editButton = "<a href='" + editUrl + "' class='btn btn-sm btn-info'>Edit</a>";
                            var deleteForm = "<form action='" + deleteUrl + "' method='POST' style='display:inline'>" +
                                "@csrf" +
                                "@method('DELETE')" +
                                "<button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this user?\")'>Delete</button>" +
                                "</form>";

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