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
          <h2><i class="glyphicon glyphicon-user"></i> Student Dashboard</h2>
        </div>
        <div class="box-content">
          <div class="row">
            <div class="col-md-2">
              @if($student->photo != '')
                <img class="img-responsive img-thumbnail" style="height:120px;width:120px;" src="{{ url('/images/'.$student->photo) }}" alt="Photo">
              @else
                <img class="img-responsive img-thumbnail" style="height:120px;width:120px;" src="{{ url('/images/icon/avatar-big-01.jpg') }}" alt="Photo">
              @endif
            </div>
            <div class="col-md-5">
              <h3>{{ $student->firstName }} {{ $student->middleName }} {{ $student->lastName }}</h3>
              <p>
                <strong>Registration No:</strong> {{ $student->regiNo }}<br>
                <strong>Roll No:</strong> {{ $student->rollNo }}<br>
                <strong>Class:</strong> @if($class) {{ $class->name }} @endif &nbsp; @if($section) {{ $section->name }} @endif<br>
                <strong>Phone:</strong> {{ $student->fatherCellNo }} {{ $student->motherCellNo }}
              </p>
            </div>
            <div class="col-md-5">
              <div class="row">
                <div class="col-md-6">
                  <a class="btn btn-primary btn-block" href="{{ url('/section/view-timetable/'.$student->section.'?term=1') }}"><i class="glyphicon glyphicon-time"></i> First Term Timetable</a>
                </div>
                <div class="col-md-6">
                  <a class="btn btn-success btn-block" href="{{ url('/section/view-timetable/'.$student->section.'?term=2') }}"><i class="glyphicon glyphicon-time"></i> Second Term Timetable</a>
                </div>
              </div>
              <br>
              <div class="row">
                <div class="col-md-6">
                  <div class="well top-block">
                    <i class="glyphicon glyphicon-calendar blue"></i>
                    <div>First Term</div>
                    <div>{{ $term1 }} sessions</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="well top-block">
                    <i class="glyphicon glyphicon-calendar green"></i>
                    <div>Second Term</div>
                    <div>{{ $term2 }} sessions</div>
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
@stop