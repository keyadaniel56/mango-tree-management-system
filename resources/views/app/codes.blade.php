@extends('layouts.master')
@section('content')
@if (Session::get('success'))
<div class="alert alert-success">
  <button data-dismiss="alert" class="close" type="button">×</button>
  <strong>Process Success.</strong> {{ Session::get('success')}}<br>
</div>
@endif
@if (Session::get('error'))
<div class="alert alert-danger">
  <button data-dismiss="alert" class="close" type="button">×</button>
  <strong>Error.</strong> {{ Session::get('error')}}<br>
</div>
@endif
@if (Session::get('newCode'))
<div class="alert alert-info">
  <button data-dismiss="alert" class="close" type="button">×</button>
  <strong>New Code Generated:</strong> {{ Session::get('newCode')}}<br>
</div>
@endif

<div class="row">
  <div class="col-md-12">
    <div class="box">
      <div class="box-inner homepage-box">
        <div class="box-header well">
          <h2><i class="glyphicon glyphicon-qrcode"></i> Registration Codes</h2>
        </div>
        <div class="box-content">
          <div class="row" style="margin-bottom:20px;">
            <div class="col-md-6 col-md-offset-3">
              <form class="form-inline" method="post" action="{{ url('/users/codes/generate') }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div class="form-group">
                  <label for="role">Role</label>
                  <select class="form-control" name="role" required>
                    <option value="">Select role</option>
                    <option value="Teacher">Teacher</option>
                    <option value="Student">Student</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="group_id">Person</label>
                  <select class="form-control" name="group_id" required>
                    <option value="">Select a teacher or student</option>
                    @foreach($teachers as $t)
                    <option value="{{ $t->id }}" data-role="Teacher">{{ $t->firstName }} {{ $t->lastName }} (Teacher)</option>
                    @endforeach
                    @foreach($students as $s)
                    <option value="{{ $s->id }}" data-role="Student">{{ $s->firstName }} {{ $s->lastName }} (Student)</option>
                    @endforeach
                  </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-plus"></i> Generate Code</button>
              </form>
            </div>
          </div>

          @if($codes->count() > 0)
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>#</th>
                <th>Code</th>
                <th>Role</th>
                <th>Person</th>
                <th>Status</th>
                <th>Expires At</th>
                <th>Used At</th>
                <th>Created</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($codes as $i => $code)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $code->code }}</strong></td>
                <td>{{ $code->role }}</td>
                <td>
                  @if($code->role == 'Teacher')
                    {{ $teachers->where('id', $code->group_id)->first() ? $teachers->where('id', $code->group_id)->first()->firstName.' '.$teachers->where('id', $code->group_id)->first()->lastName : 'Unknown ('.$code->group_id.')' }}
                  @else
                    {{ $students->where('id', $code->group_id)->first() ? $students->where('id', $code->group_id)->first()->firstName.' '.$students->where('id', $code->group_id)->first()->lastName : 'Unknown ('.$code->group_id.')' }}
                  @endif
                </td>
                <td>
                  @if($code->status == 'unused')
                    <span class="label label-info">Unused</span>
                  @elseif($code->status == 'used')
                    <span class="label label-success">Used</span>
                  @else
                    <span class="label label-default">Cancelled</span>
                  @endif
                </td>
                <td>{{ $code->expires_at ? $code->expires_at : 'Never' }}</td>
                <td>{{ $code->used_at ?: '—' }}</td>
                <td>{{ $code->created_at }}</td>
                <td>
                  @if($code->status == 'unused')
                  <a class="btn btn-danger btn-xs" href="{{ url('/users/codes/delete/'.$code->id) }}" onclick="return confirm('Cancel this registration code?')"><i class="glyphicon glyphicon-remove"></i></a>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
          @else
          <p class="text-center">No registration codes yet. Generate one above.</p>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@stop
@section('script')
<script>
$(function(){
  $('select[name=role]').on('change', function(){
    var role = $(this).val();
    var $person = $('select[name=group_id]');
    $person.find('option[data-role]').hide();
    if (role) {
      $person.find('option[data-role="'+role+'"]').show();
    } else {
      $person.find('option[data-role]').show();
    }
    $person.val('');
  });
});
</script>
@stop