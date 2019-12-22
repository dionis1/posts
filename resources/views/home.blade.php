@extends('layouts.app')

@section('css')
<link  href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css" rel="stylesheet">
@endsection

@section('content')
<div class="container">
               <h2>Posts List</h2>
            <table class="table table-bordered posts_datatable" id="posts_datatable">
               <thead>
                  <tr>
                     <th>Id</th>
                     <th>Title</th>
                     <th>Author</th>
                     <th>Created at</th>
                     <th>Updated at</th>
                     <th>Action</th>
                  </tr>
               </thead>
            </table>
</div>
@endsection
@section('js')
<script src="https://code.jquery.com/jquery-3.3.1.js"></script>

<script src="//cdn.datatables.net/plug-ins/1.10.11/sorting/date-eu.js" type="text/javascript"></script>

<script type="text/javascript"  src=" https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript"  src=" https://cdn.datatables.net/buttons/1.2.4/js/dataTables.buttons.min.js"></script>
<script>
   $(document).ready( function () {
    $('.posts_datatable').DataTable({
           processing: true,
           serverSide: true,
           order: [[ 3, "desc" ]],
           columnDefs : [{"targets":3, "type":"date-eu"}],
           ajax: "{{ route('allPostsDataTable') }}",
           columns: [
                    { data: 'id', name: 'id' },
                    { data: 'title', name: 'title' },
                    { data: 'name', name: 'name' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'updated_at', name: 'updated_at' },
                    {
					    data: 'action',
					    render: function( data, type, full, meta) {
					        return '<a href="/post/show/'+full.id+'" class="btn btn-success">Show</a>';
					    }
					}
                 ]
        });
     });
  </script>
@endsection