@extends('layouts.master')
@section('content')
@if (Session::get('success'))
<div class="alert alert-success">
  <button data-dismiss="alert" class="close" type="button">×</button>
  <strong>Process Success.</strong> {{ Session::get('success')}}<br>
</div>
@endif

<div class="row">
  <div class="col-md-12">
    <div class="box">
      <div class="box-inner homepage-box">
        <div class="box-header well">
          <h2><i class="glyphicon glyphicon-education"></i> Teacher Dashboard</h2>
        </div>
        <div class="box-content">
          <div class="row">
            <div class="col-md-2">
              @if($teacher->photo != '')
                <img class="img-responsive img-thumbnail" style="height:120px;width:120px;" src="{{ url('/images/teacher/'.$teacher->photo) }}" alt="Photo">
              @else
                <img class="img-responsive img-thumbnail" style="height:120px;width:120px;" src="{{ url('/images/icon/avatar-big-01.jpg') }}" alt="Photo">
              @endif
            </div>
            <div class="col-md-5">
              <h3>{{ $teacher->firstName }} {{ $teacher->lastName }}</h3>
              <p>
                <strong>Phone:</strong> {{ $teacher->phone }}<br>
                <strong>Email:</strong> {{ $teacher->email }}<br>
                <strong>Gender:</strong> {{ $teacher->gender }}
              </p>
            </div>
            <div class="col-md-5">
              <div class="row">
                <div class="col-md-6">
                  <a class="btn btn-primary btn-block" href="{{ url('/teacher/view-timetable/'.$teacher->id.'?term=1') }}"><i class="glyphicon glyphicon-time"></i> First Term Timetable</a>
                </div>
                <div class="col-md-6">
                  <a class="btn btn-success btn-block" href="{{ url('/teacher/view-timetable/'.$teacher->id.'?term=2') }}"><i class="glyphicon glyphicon-time"></i> Second Term Timetable</a>
                </div>
              </div>
              <br>
              <div class="row">
                <div class="col-md-4">
                  <div class="well top-block">
                    <i class="glyphicon glyphicon-calendar blue"></i>
                    <div>First Term</div>
                    <div>{{ $term1 }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="well top-block">
                    <i class="glyphicon glyphicon-calendar green"></i>
                    <div>Second Term</div>
                    <div>{{ $term2 }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="well top-block">
                    <i class="glyphicon glyphicon-th-large orange"></i>
                    <div>Sections</div>
                    <div>{{ $sections->count() }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="box">
      <div class="box-inner homepage-box">
        <div class="box-header well">
          <h2><i class="glyphicon glyphicon-th-large"></i> My Sections</h2>
        </div>
        <div class="box-content">
          @if($sections->count() > 0)
          <table class="table table-bordered">
            <thead>
              <tr><th>#</th><th>Class</th><th>Section</th></tr>
            </thead>
            <tbody>
              @foreach($sections as $i => $section)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $section->class_code }}</td>
                <td>{{ $section->name }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
          @else
          <p class="text-center">No sections assigned yet.</p>
          @endif
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="box">
      <div class="box-inner homepage-box">
        <div class="box-header well">
          <h2><i class="glyphicon glyphicon-book"></i> My Subjects</h2>
        </div>
        <div class="box-content">
          @if($subjects->count() > 0)
          <table class="table table-bordered">
            <thead>
              <tr><th>#</th><th>Subject</th><th>Class</th></tr>
            </thead>
            <tbody>
              @foreach($subjects as $i => $subject)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $subject->name }}</td>
                <td>{{ $subject->class }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
          @else
          <p class="text-center">No subjects assigned yet.</p>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@stop