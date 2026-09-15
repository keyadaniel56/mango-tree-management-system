<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{$institute->name}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- The styles -->
    <link id="bs-css" href="css/bootstrap-cerulean.min.css" rel="stylesheet">

    <link href="css/charisma-app.css" rel="stylesheet">


    <!-- jQuery -->
    <script src="bower_components/jquery/jquery.min.js"></script>

    <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <!-- The fav icon -->
    <link rel="icon" type="image/png" href="img/favicon.png">

</head>

<body>
<div class="ch-container">
    <div class="row">

        <div class="row">
            <div class="col-md-12 center login-header">
                <h2>Welcome to "{{$institute->name}}"</h2>
            </div>
            <!--/span-->
        </div><!--/row-->

        <div class="row">
            <div class="well col-md-5 center login-box">
                <img src="img/logo.png" style="height:120px;">
                <h4 class="text-center">Create the first Administrator account</h4>
                <p class="text-center"><small>This page is only shown while no administrator exists. A secret setup key is required so outsiders can never claim the admin role.</small></p>

                <form class="form-horizontal" action="setup" method="post">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <fieldset>
                        <div class="input-group input-group-lg">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-key red"></i></span>
                            <input type="text" class="form-control" name="setup_key" placeholder="Secret Setup Key">
                        </div>
                        <div class="clearfix"></div><br>

                        <div class="input-group input-group-lg">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user red"></i></span>
                            <input type="text" class="form-control" name="firstname" placeholder="First Name" value="{{ old('firstname') }}">
                        </div>
                        <div class="clearfix"></div><br>

                        <div class="input-group input-group-lg">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user red"></i></span>
                            <input type="text" class="form-control" name="lastname" placeholder="Last Name" value="{{ old('lastname') }}">
                        </div>
                        <div class="clearfix"></div><br>

                        <div class="input-group input-group-lg">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user red"></i></span>
                            <input type="text" class="form-control" name="login" placeholder="Choose a Username" value="{{ old('login') }}">
                        </div>
                        <div class="clearfix"></div><br>

                        <div class="input-group input-group-lg">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-envelope red"></i></span>
                            <input type="text" class="form-control" name="email" placeholder="Email" value="{{ old('email') }}">
                        </div>
                        <div class="clearfix"></div><br>

                        <div class="input-group input-group-lg">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock red"></i></span>
                            <input type="password" class="form-control" name="password" placeholder="Password">
                        </div>
                        <div class="clearfix"></div><br>

                        <div class="input-group input-group-lg">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock red"></i></span>
                            <input type="password" class="form-control" name="confirm" placeholder="Confirm Password">
                        </div>
                        <div class="clearfix"></div>

                        @if (count($errors) > 0)
                            @foreach ($errors->all() as $err)
                                <div class="alert alert-danger"><button data-dismiss="alert" class="close" type="button">×</button><strong>{{ $err }}.</strong></div>
                            @endforeach
                        @endif
                        @if (Session::get('error'))
                                <div class="alert alert-danger">
                                <button data-dismiss="alert" class="close" type="button">×</button>
                                <strong>{{ Session::get('error') }}.</strong>
                            </div>
                        @endif

                        <p class="center col-md-5">
                            <button type="submit" class="btn btn-primary">Create Admin</button>
                        </p>
                    </fieldset>
                </form>
            </div>
            <!--/span-->
        </div><!--/row-->
    </div><!--/fluid-row-->

</div><!--/.fluid-container-->

<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

<script src="js/charisma.js"></script>


</body>
</html>