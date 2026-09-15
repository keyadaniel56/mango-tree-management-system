@extends('layouts.master')
@section('style')
    <link href="{{ url('/css/bootstrap-datepicker.css') }}" rel="stylesheet">
    <link href="/css/timetable.css" rel="stylesheet">
    <style>
        .ttable-grid td, .ttable-grid th { text-align: center; vertical-align: top; }
        .slot { display: block; padding: 6px 4px; margin: 4px 2px; border-radius: 4px; color: #fff; font-size: 12px; }
        .slot small { display: block; color: rgba(255,255,255,.9); }
    </style>
@stop
@section('content')
    @if (Session::get('success'))
        <div class="alert alert-success">
            <button data-dismiss="alert" class="close" type="button">×</button>
            <strong>Process Success.</strong> {{ Session::get('success') }}
        </div>
    @endif
    @if ($errors->count() > 0)
        <div class="alert alert-danger">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="box col-md-12">
            <div class="box-inner">
                <div data-original-title="" class="box-header well">
                    <h2><i class="glyphicon glyphicon-calendar"></i> Timetable Management</h2>
                </div>
                <div class="box-content">
                    <form role="form" action="{{ url('/timetable/list') }}" method="get">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Term</label>
                                    <select name="term" class="form-control">
                                        @foreach($terms as $termValue => $termLabel)
                                            <option value="{{ $termValue }}" @if($termFilter == $termValue) selected @endif>{{ $termLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Class</label>
                                    <select name="class" id="class" class="form-control">
                                        <option value="">All Classes</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->code }}" @if($classFilter == $class->code) selected @endif>{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Section</label>
                                    <select name="section" id="section" class="form-control">
                                        <option value="">All Sections</option>
                                        @foreach($sections as $section)
                                            <option value="{{ $section->id }}" @if($sectionFilter == $section->id) selected @endif>{{ $section->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Teacher</label>
                                    <select name="teacher" class="form-control">
                                        <option value="">All Teachers</option>
                                        @foreach($teachers as $teacher)
                                            <option value="{{ $teacher->id }}" @if($teacherFilter == $teacher->id) selected @endif>{{ $teacher->firstName }} {{ $teacher->lastName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <button class="btn btn-primary" type="submit"><i class="glyphicon glyphicon-filter"></i> Filter</button>
                                    <a class="btn btn-success" href="{{ url('/teacher/create-timetable') }}"><i class="glyphicon glyphicon-plus"></i> Add Timetable</a>
                                </div>
                            </div>
                        </div>
                    </form>

                    @php
                        $week = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                        $dayName = [
                            'monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday',
                            'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 'sunday' => 'Sunday',
                        ];
                        $byDay = $timetables->groupBy('day');
                        $times = $timetables->pluck('stattime')->unique()->values();
                    @endphp

                    @if($timetables->count() > 0)
                        <h4 class="text-info">Weekly Timetable - {{ termname($termFilter) }}</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped ttable-grid">
                                <thead>
                                    <tr>
                                        <th style="width:10%">Day / Time</th>
                                        @foreach($week as $day)
                                            <th><span class="label label-default">{{ $dayName[$day] }}</span></th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $slots = $times->map(function ($t) use ($timetables) {
                                            return ['time' => $t, 'entries' => $timetables->where('stattime', $t)];
                                        })->sortBy(function ($slot) {
                                            return strtotime($slot['time']);
                                        })->values();
                                    @endphp
                                    @foreach($slots as $slot)
                                        <tr>
                                            <td><b>{{ $slot['time'] }}</b></td>
                                            @foreach($week as $day)
                                                @php
                                                    $entries = $slot['entries']->where('day', $day)->values();
                                                @endphp
                                                <td>
                                                    @forelse($entries as $entry)
                                                        <span class="slot" style="background: {{ $entry->color ?: '#999' }}">
                                                            {{ $entry->subname }}
                                                            <small>{{ $entry->firstName }} {{ $entry->lastName }}</small>
                                                            <small>{{ $entry->classname }} -- {{ $entry->section_name }}</small>
                                                        </span>
                                                    @empty
                                                        <span class="text-muted">-</span>
                                                    @endforelse
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <h4 class="text-info">Timetable List</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Term</th>
                                        <th>Teacher</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Subject</th>
                                        <th>Day</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($timetables as $i => $row)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ termname($row->term) }}</td>
                                            <td>{{ $row->firstName }} {{ $row->lastName }}</td>
                                            <td>{{ $row->classname }}</td>
                                            <td>{{ $row->section_name }}</td>
                                            <td>{{ $row->subname }}</td>
                                            <td>{{ $dayName[$row->day] ?? $row->day }}</td>
                                            <td>{{ $row->stattime }}</td>
                                            <td>{{ $row->endtime }}</td>
                                            <td>
                                                <a title="Edit" class="btn btn-info btn-xs" href="{{ url('/timetable/edit') }}/{{ $row->id }}"><i class="glyphicon glyphicon-edit icon-white"></i></a>
                                                <a title="Delete" class="btn btn-danger btn-xs" href="#" onclick="confirmed('{{ $row->id }}'); return false;"><i class="glyphicon glyphicon-trash icon-white"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <strong>No timetable entries found.</strong> Try changing the filters or add a new timetable entry.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
@section('script')
    <script>
        $(document).ready(function() {
            $('#class').on('change', function() {
                var aclass = $(this).val();
                $('#section').empty().append('<option value="">All Sections</option>');
                $.ajax({
                    url: "{{ url('/section/getList') }}" + '/' + aclass,
                    dataType: 'json',
                    success: function(data) {
                        if (Array.isArray(data)) {
                            $.each(data, function(i, section) {
                                $('#section').append('<option value="' + section.id + '">' + section.name + '</option>');
                            });
                        }
                    }
                });
            });
        });

        function confirmed(id) {
            var x = confirm('Are you sure you want to delete this timetable entry?');
            if (x) {
                window.location = "{{ url('/timetable/delete') }}"+"/"+id;
                return true;
            } else {
                return false;
            }
        }
    </script>
@stop