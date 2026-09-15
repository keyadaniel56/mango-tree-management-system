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
          <h2><i class="glyphicon glyphicon-usd"></i> Accountant Dashboard</h2>
        </div>
        <div class="box-content">
          <div class="row">
            <div class="col-md-4">
              <div class="well top-block">
                <i class="glyphicon glyphicon-arrow-up blue"></i>
                <div>Total Income</div>
                <div>{{ number_format($income, 2) }}</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="well top-block">
                <i class="glyphicon glyphicon-arrow-down red"></i>
                <div>Total Expense</div>
                <div>{{ number_format($expence, 2) }}</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="well top-block">
                <i class="glyphicon glyphicon-th-large orange"></i>
                <div>Account Sectors</div>
                <div>{{ $sectors }}</div>
              </div>
            </div>
          </div>
          <div class="row" style="margin-top:20px;">
            <div class="col-md-3">
              <a class="btn btn-primary btn-block" href="{{ url('/accounting') }}"><i class="glyphicon glyphicon-cog"></i> Accounting Home</a>
            </div>
            <div class="col-md-3">
              <a class="btn btn-success btn-block" href="{{ url('/accounting/income') }}"><i class="glyphicon glyphicon-arrow-up"></i> Income</a>
            </div>
            <div class="col-md-3">
              <a class="btn btn-danger btn-block" href="{{ url('/accounting/expence') }}"><i class="glyphicon glyphicon-arrow-down"></i> Expense</a>
            </div>
            <div class="col-md-3">
              <a class="btn btn-warning btn-block" href="{{ url('/accounting/report') }}"><i class="glyphicon glyphicon-file"></i> Reports</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@stop