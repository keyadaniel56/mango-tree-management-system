<?php
namespace App\Http\Controllers;
use Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Http\Controllers\ictcoreController;
use App\Institute;
use App\User;
use App\VerifyCode;
use App\VerificationCode;
use App\RegistrationCode;
use App\Teacher;
use App\Student;
use Carbon\Carbon;

use Hash;
class UsersController extends BaseController {

  use AuthenticatesUsers;
  public function __construct() {
    /*$this->beforeFilter('csrf', array('on'=>'post'));
    $this->beforeFilter('auth', array('only'=>array('show','create','edit','update')));
    $this->beforeFilter('userAccess',array('only'=> array('show','create','edit','update','delete')));*/
    //5.5
    //$this->middleware('csrf', array('on'=>'post'));
    $this->middleware('auth', array('only'=>array('show','create','edit','update','showCodes','generateRegistrationCode','deleteCode')));
  }
  /**
  * Display a listing of the resource.
  *
  * @return Response
  */
  public function postSignin(request $request) {

     $otp_check  = \Config::get('app.otp');

     //echo "fcf".$otp_check ;
       $institute=Institute::select('name')->first();
     if($otp_check=="No"){
    if (\Auth::attempt(array('login'=>Input::get('login'), 'password'=>Input::get('password')))) {
          
          
          $login   = Auth::user()->group;
          /*if($login == "Admin"){
              $user_id = Auth::user()->id;
              $phone = Auth::user()->phone;
            \Auth::logout();
            $this->sendcode($user_id,$phone);
            return Redirect::to('/verify_code');

          }*/


      $name=Auth::user()->firstname.' '.Auth::user()->lastname;
      $login=Auth::user()->group;
      \Session::put('name', $name);
      \Session::put('userRole', $login);
    
      if(!$institute)
      {
        if (Auth::user()->group != "Admin")
        {
          return Redirect::to('/')
          ->withInput(Input::all())->with('error', 'Institute Information not setup yet!Please contact administrator.');
        }
        else {
          $institute=new Institute;
          $institute->name="IctVission";
          \Session::put('inName', $institute->name);
          return Redirect::to('/institute')->with('error','Please provide institute information!');

        }
      }
      else {
        \Session::put('inName', $institute->name);
        return Redirect::to($this->roleDashboard())->with('success','You are now logged in.');
      }

    } else {
      return Redirect::to('/')
      ->withInput(Input::all())->with('error', 'Your username/password combination was incorrect');

    }
  }else{

       // $this->validateLogin($request);

     if (\Auth::attempt(array('login'=>Input::get('login'), 'password'=>Input::get('password')))) {
        //if ($user = app('auth')->getProvider()->retrieveByCredentials($request->only('email', 'password'))) {
         $user_id = Auth::id();
         $token = VerificationCode::where('user_id',$user_id)->where('ip_address',$request->ip());
         if( $token->count()==0){


          $token_create =VerificationCode::create([

            'user_id'=>$user_id,
            'status'=>1,
            'ip_address'=>$request->ip()
          ]
          ) ;         //$code = $token->first()->generateCode;exit;

          $ict     = new ictcoreController();

          if (preg_match("~^0\d+$~", Auth()->user()->phone)) {
            $phone = preg_replace('/0/', '92',Auth()->user()->phone, 1);
         
      }
      else {
           $phone =Auth()->user()->phone;  
      }
          $message = 'Your verification code is '.$token_create->code;
            $data = array('numbers'=>$phone,'message'=>$message);
            $snd_msg  = $ict->biz_sms($data);
          //exit;
          \Auth::logout();
           return Redirect::to('/verification_code?id='.$token_create->id);

         }

        

       \Session::put('inName', $institute->name);
        return Redirect::to($this->roleDashboard())->with('success','You are now logged in.');
     }else{

       return Redirect::to('/')
      ->withInput(Input::all())->with('error', 'Your username/password combination was incorrect');
     }
  }

  }

  public function codeverify(Request $request)
  {
      $institute=Institute::select('name')->first();
    if(!$institute)
    {
      $institute=new Institute;
      $institute->name="ictvission";
    }
      $id = $request->get('id');
    return view('verificationcode',compact('institute','id'));
  }
  public function code_check(Request $request)
  {

    $check = VerificationCode::find($request->id);

    if($check->code == $request->code){

       if(Auth::loginUsingId($check->user_id)) {
        //request()->session()->flush();
        $name  = Auth::user()->firstname.' '.Auth::user()->lastname;
        $login = Auth::user()->group;
        \Session::put('name', $name);
        \Session::put('userRole', $login);
        return redirect($this->roleDashboard());
      }

    }

       return Redirect::to('/verification_code?id='.$request->id)
      ->withInput(Input::all())->withErrors('Your code was incorrect');

  }

  public function verify_code()
  {
    $error = \Session::get('error');
    $institute=Institute::select('name')->first();
    if(!$institute)
    {
      $institute=new Institute;
      $institute->name="IctVission";
    }
    return View('app.users.verify',compact('error','institute'));

  }

  public function sendcode($user_id,$phone)
  {

     $verified_code = hexdec(substr(uniqid(rand(), true), 5, 5));
                $verification_code = new VerifyCode;
                $verification_code->user_id=$user_id;
                $verification_code->code=$verified_code;
                $verification_code->save();
                
               /* $ict         = new ictcoreController();
                    $contact = array(
                      'firstname' => 'admin',
                      'lastname' =>'',
                      'phone'     =>$phone,
                      'email'     => '',
                      );
                $msg = "verification code is ". $verified_code;
                 $ict_stting = DB::table('ict_settings')->first();
                 if($ict_stting->type=='ictcore'){
                $ict->verification_number($contact,$msg);*/
                  $msg = "verification code is ". $verified_code;
                  $send_msg_ictcore = sendmesssageictcore('admin','',$phone,$msg,'verified code');
  }

  public function verified()
  {
    $verification_code = VerifyCode::first();

    if(!empty($verification_code) && $verification_code->code == Input::get('code')){

      $user_id = $verification_code->user_id;
        VerifyCode::truncate();
      if(Auth::loginUsingId($user_id)){

            $name = Auth::user()->firstname.' '.Auth::user()->lastname;
            $login = Auth::user()->group;
            \Session::put('name', $name);
            \Session::put('userRole', $login);
            $institute=Institute::select('name')->first();
          if(!$institute)
          {
            if (Auth::user()->group != "Admin")
            {
              return Redirect::to('/verify_code')
              ->withInput(Input::all())->with('error', 'Institute Information not setup yet!Please contact administrator.');
            }
            else {
              $institute=new Institute;
$institute->name="The Mango Tree Girls School";
              \Session::put('inName', $institute->name);
              return Redirect::to('/institute')->with('error','Please provide institute information!');

            }
          }
          else {
            \Session::put('inName', $institute->name);
            return Redirect::to($this->roleDashboard())->with('success','You are now logged in.');
          }
      }
    }else{
      return Redirect::to('/verify_code')
              ->withInput(Input::all())->with('error', 'Code Not Match please enter Correct Code');
    }
  }

  public function getLogout() {
    /*request()->session()->flush();
    \Auth::logout();*/

    if(request()->session()->pull('isAdmin', 0)){
      $id = request()->session()->pull('adminID', 0);
      //$url = request()->session()->pull('surl','');
      //$id = request()->session()->pull('adminID', 0);
      if(Auth::loginUsingId($id)) {
        //request()->session()->flush();
        $name  = Auth::user()->firstname.' '.Auth::user()->lastname;
        $login = Auth::user()->group;
        \Session::put('name', $name);
        \Session::put('userRole', $login);
        return redirect($this->roleDashboard());
      }
      return redirect($this->roleDashboard());
    }
    request()->session()->flush();
    \Auth::logout();
    return redirect('/')->with('message', 'Your are now logged out!');
  } 

  protected function roleDashboard() {
    $role = strtolower(Auth::user()->group);
    switch ($role) {
      case 'teacher':
        return '/teacher/dashboard';
      case 'student':
        return '/student/dashboard';
      case 'accountant':
        return '/accountant/dashboard';
      default:
        return '/dashboard';
    }
  }
  public function dologin($id,$usr_id) {
    $user = User::find($id);
    request()->session()->forget('isAdmin');
    request()->session()->forget('adminID');
    request()->session()->forget('surl');
    request()->session()->put('isAdmin', 1);
    request()->session()->put('adminID', $usr_id);
    
   // echo request()->root();
    //echo "<pre>rr".request()->session()->get('adminID')."tt";print_r($user);
    if (Auth::loginUsingId($id)) {
        
        $name  = Auth::user()->firstname.' '.Auth::user()->lastname;
        $login = Auth::user()->group;
        \Session::put('name', $name);
        \Session::put('userRole', $login);
        //echo "adeel";
        return redirect('/dashboard');
    }
  }

  public  function show()
  {
    //User::create(array('firstname'=>'Mr.','lastname'=>'kashif','login'=>'ictkashif','email' => 'kashif@ictinnovations.com','group'=>'Admin','desc'=>'admin Deatils Here',"password"=> Hash::make("123456")));
    $users= User::all();
    $user=array();
    //return View::Make('app.users',compact('users','user'));
    return View('app.users',compact('users','user'));
  }
  public  function create()
  {
    $rules=[
      'firstname' => 'required',
      'lastname' => 'required',
      'email' => 'required|email',
      'group' => 'required',
      'desc' => 'required',
      'login' => 'required',
      'password' => 'required'

    ];
    $validator = \Validator::make(Input::all(), $rules);
    if ($validator->fails())
    {
      return Redirect::to('/users')->withInput(Input::all())->withErrors($validator);
    }
    else {

      $uexits = User::select('*')->where('email','=',Input::get('email'))->where('login','=',Input::get('login'))->get();
    //  dd($uexits );
     //echo "<pre>";print_r($uexits);exit;
      if(count($uexits)>0)
      {
        $errorMessages = new \Illuminate\Support\MessageBag;
        $errorMessages->add('deplicate', 'User all ready exists with this email or login');
        return Redirect::to('/users')->withInput(Input::all())->withErrors($errorMessages);

      }
      {
        $user = new User;
        $user->firstname = Input::get('firstname');
        $user->lastname = Input::get('lastname');
        $user->login = Input::get('login');
        $user->desc = Input::get('desc');
        $user->email = Input::get('email');
        $user->group = Input::get('group');
        $user->password = Hash::make(Input::get('password'));
        $user->save();
        
        return Redirect::to('/users')->with("success","User Created Succesfully.");
      }


    }
  }
  public function edit($id)
  {
    $user = User::find($id);
    $users= User::all();
    //return View::Make('app.users',compact('users','user'));
     return View('app.users',compact('users','user'));
  }
  public  function update()
  {
    $rules=[
      'firstname' => 'required',
      'lastname'  => 'required',
      'email'     => 'required|email',
      'group'     => 'required',
      'desc'      => 'required',
      'login'     => 'required',
      'password'  => 'required'

    ];
    $validator = \Validator::make(Input::all(), $rules);
    if ($validator->fails())
    {
      return Redirect::to('/usersedit/'.Input::get('id'))->withErrors($validator);
    }
    else {

      $uexits = User::select('*')->orwhere('email','=',Input::get('email'))->first();
      if($uexits->count()>0) {

        if ($uexits->id != Input::get('id')) {
          $errorMessages = new \Illuminate\Support\MessageBag;
          $errorMessages->add('deplicate', 'User all ready exists with this email');
          return Redirect::to('/users')->withInput(Input::all())->withErrors($errorMessages);
        } else {
          $user            = User::find(Input::get('id'));
          $user->firstname = Input::get('firstname');
          $user->lastname  = Input::get('lastname');
          $user->login     = Input::get('login');
          $user->desc      = Input::get('desc');
          $user->email     = Input::get('email');
          $user->group     = Input::get('group');
          $user->password  = Hash::make(Input::get('password'));
          $user->save();
          return Redirect::to('/users')->with("success", "User Updated Succesfully.");
        }
      }
      else
      {
        $user = User::find(Input::get('id'));
        $user->firstname = Input::get('firstname');
        $user->lastname = Input::get('lastname');
        $user->login = Input::get('login');
        $user->desc = Input::get('desc');
        $user->email = Input::get('email');
        $user->group = Input::get('group');
        $user->password = Hash::make(Input::get('password'));
        $user->save();
        return Redirect::to('/users')->with("success", "User Updated Succesfully.");
      }

    }
  }

  public function delete($id)
  {
    $user= User::find($id);
    $user->delete();
    return Redirect::to('/users')->with("success","User Deleted Succesfully.");

  }

   public function generateCode($codeLength = 4)
   {
        $min = pow(10, $codeLength);
        $max = $min * 10 - 1;
        $code = mt_rand($min, $max);

        return $code;
    }

   /**
   * Signup page for teachers/students using a secret registration code.
   *
   * @return Response
   */
   public function showSignup()
   {
     $institute = Institute::select('name')->first();
     if(!$institute) {
       $institute = new Institute;
       $institute->name = "The Mango Tree Girls School";
     }
     return view('auth.signup', compact('institute'));
   }

   public function processSignup(request $request)
   {
     $rules = [
       'code'     => 'required|max:32',
       'login'    => 'required|max:20|unique:users,login',
       'email'    => 'nullable|email|max:100',
       'password' => 'required|min:6|max:64',
       'confirm'  => 'required|same:password',
     ];
     $validator = \Validator::make(Input::all(), $rules);
     if ($validator->fails()) {
       return Redirect::to('/signup')->withInput(Input::all())->withErrors($validator);
     }

     $rCode = RegistrationCode::where('code', Input::get('code'))->first();
     if (!$rCode || $rCode->status != 'unused' || ($rCode->expires_at && $rCode->expires_at < Carbon::now())) {
       return Redirect::to('/signup')->withInput(Input::all())->with('error', 'The registration code is invalid, already used or expired.');
     }

     $role = $rCode->role;
     $person = $role == 'Teacher' ? Teacher::find($rCode->group_id) : Student::find($rCode->group_id);
     if (!$person) {
       return Redirect::to('/signup')->withInput(Input::all())->with('error', 'The account linked to this code no longer exists.');
     }

     $existing = User::where('group', $role)->where('group_id', $rCode->group_id)->count();
     if ($existing > 0) {
       return Redirect::to('/signup')->withInput(Input::all())->with('error', 'An account already exists for this person.');
     }

     $user = new User;
     $user->firstname = $person->firstName;
     $user->lastname  = $person->lastName;
     $user->desc      = '';
     $user->login     = Input::get('login');
     $user->email     = Input::get('email') ?: NULL;
     $user->group     = $role;
     $user->group_id  = $rCode->group_id;
     $user->access    = 1;
     $user->password  = Hash::make(Input::get('password'));
     $user->save();

     $rCode->status   = 'used';
     $rCode->used_by  = $user->id;
     $rCode->used_at  = Carbon::now();
     $rCode->save();

     if (\Auth::loginUsingId($user->id)) {
       $name = $user->firstname.' '.$user->lastname;
       \Session::put('name', $name);
       \Session::put('userRole', $user->group);
       $institute = Institute::select('name')->first();
       \Session::put('inName', $institute ? $institute->name : '');
       return Redirect::to($this->roleDashboard())->with('success', 'Signup complete. You are now logged in.');
     }

     return Redirect::to('/')->with('success', 'Account created successfully. You can now login.');
   }

   /**
   * First-run admin bootstrap page (only available when no admin exists).
   *
   * @return Response
   */
   public function showSetup()
   {
     if (User::where('group', 'Admin')->count() > 0) {
       return Redirect::to('/');
     }
     $institute = Institute::select('name')->first();
     if(!$institute) {
       $institute = new Institute;
       $institute->name = "The Mango Tree Girls School";
     }
     return view('auth.setup', compact('institute'));
   }

   public function processSetup(request $request)
   {
     if (User::where('group', 'Admin')->count() > 0) {
       return Redirect::to('/')->with('error', 'An administrator account already exists.');
     }

     if (Input::get('setup_key') != \Config::get('app.admin_setup_key')) {
       return Redirect::to('/setup')->withInput(Input::all())->with('error', 'The setup key is incorrect.');
     }

     $rules = [
       'firstname' => 'required|max:20',
       'lastname'  => 'required|max:20',
       'login'     => 'required|max:20|unique:users,login',
       'email'     => 'required|email|max:100',
       'password'  => 'required|min:6|max:64',
       'confirm'   => 'required|same:password',
     ];
     $validator = \Validator::make(Input::all(), $rules);
     if ($validator->fails()) {
       return Redirect::to('/setup')->withInput(Input::all())->withErrors($validator);
     }

     $user = new User;
     $user->firstname = Input::get('firstname');
     $user->lastname  = Input::get('lastname');
     $user->desc      = '';
     $user->login     = Input::get('login');
     $user->email     = Input::get('email');
     $user->group     = 'Admin';
     $user->access    = 1;
     $user->password  = Hash::make(Input::get('password'));
     $user->save();

     \Session::put('name', $user->firstname.' '.$user->lastname);
     \Session::put('userRole', 'Admin');
     $institute = Institute::select('name')->first();
     \Session::put('inName', $institute ? $institute->name : 'The Mango Tree Girls School');

     if (\Auth::loginUsingId($user->id)) {
       return Redirect::to('/dashboard')->with('success', 'Administrator account created. Welcome!');
     }
     return Redirect::to('/')->with('success', 'Administrator account created. You can now login.');
   }

   /**
   * Admin page listing all registration codes and code generation.
   *
   * @return Response
   */
   public function showCodes()
   {
     if (\Auth::user()->group != 'Admin') {
       return Redirect::to('/');
     }
     $codes    = RegistrationCode::orderBy('id', 'desc')->get();
     $teachers = Teacher::all(['id','firstName','lastName']);
     $students = Student::all(['id','firstName','lastName']);
     return View('app.codes', compact('codes', 'teachers', 'students'));
   }

   public function generateRegistrationCode(request $request)
   {
     if (\Auth::user()->group != 'Admin') {
       return Redirect::to('/');
     }
     $role     = Input::get('role');
     $group_id = (int) Input::get('group_id');
     if (!in_array($role, ['Teacher', 'Student']) || $group_id <= 0) {
       return Redirect::to('/users/codes')->with('error', 'Please select a role and a person.');
     }

     $person = $role == 'Teacher' ? Teacher::find($group_id) : Student::find($group_id);
     if (!$person) {
       return Redirect::to('/users/codes')->with('error', 'The selected record no longer exists.');
     }

     RegistrationCode::where('role', $role)->where('group_id', $group_id)->where('status', 'unused')->delete();

     $block = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
     $code = substr(str_shuffle($block), 0, 4).'-'.substr(str_shuffle($block), 0, 4);

     $rCode = new RegistrationCode;
     $rCode->code        = $code;
     $rCode->role        = $role;
     $rCode->group_id    = $group_id;
     $rCode->status      = 'unused';
     $rCode->created_by  = \Auth::id();
     $rCode->expires_at  = Carbon::now()->addDays(14);
     $rCode->save();

     $label = $role.' - '.$person->firstName.' '.$person->lastName;
     return Redirect::to('/users/codes')->with('newCode', 'Registration code for '.$label.': '.$code);
   }

   public function deleteCode($id)
   {
     if (\Auth::user()->group != 'Admin') {
       return Redirect::to('/');
     }
     $code = RegistrationCode::find($id);
     if ($code) {
       $code->status = 'cancelled';
       $code->save();
     }
     return Redirect::to('/users/codes')->with('success', 'Registration code cancelled.');
   }

}
