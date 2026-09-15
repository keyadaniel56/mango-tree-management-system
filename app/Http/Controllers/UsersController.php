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
    $this->middleware('auth', array('only'=>array('show','create','edit','update','delete','showCodes','generateRegistrationCode','deleteCode')));
  }

  /**
   * Ensure the currently authenticated user is an administrator.
   *
   * @return bool
   */
  protected function ensureAdmin() {
    if (!Auth::check() || Auth::user()->group != 'Admin') {
      return false;
    }
    return true;
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

  public function show()
  {
    if (!$this->ensureAdmin()) {
      return Redirect::to('/dashboard')->with('error', 'Access denied. Only administrators can manage users and assign roles.');
    }
    $users = User::orderBy('id', 'desc')->get();
    $user = null;
    $teachers = Teacher::select('id', 'firstName', 'lastName', 'phone', 'email')->orderBy('firstName')->get();
    $students = Student::select('id', 'regiNo', 'firstName', 'lastName')->orderBy('regiNo')->get();
    return View('app.users', compact('users', 'user', 'teachers', 'students'));
  }

  public function create()
  {
    if (!$this->ensureAdmin()) {
      return Redirect::to('/dashboard')->with('error', 'Access denied. Only administrators can manage users and assign roles.');
    }
    $rules = [
      'firstname' => 'required',
      'lastname'  => 'nullable',
      'email'     => 'nullable|email',
      'group'     => 'required|in:Admin,Teacher,Student,Accountant,Director,Staff',
      'desc'      => 'nullable',
      'login'     => 'required|unique:users,login',
      'password'  => 'required|min:4'
    ];
    $validator = \Validator::make(Input::all(), $rules);
    if ($validator->fails()) {
      return Redirect::to('/users')->withInput(Input::all())->withErrors($validator);
    }

    $email = Input::get('email');
    if (!empty($email)) {
      if (User::where('email', $email)->exists()) {
        return Redirect::to('/users')->withInput(Input::all())->withErrors(['email' => 'User already exists with this email address.']);
      }
    }

    $user = new User;
    $user->firstname = Input::get('firstname');
    $user->lastname  = Input::get('lastname') ?: '';
    $user->login     = Input::get('login');
    $user->desc      = Input::get('desc') ?: '';
    $user->email     = $email ?: null;
    $user->group     = Input::get('group');
    $user->access    = 1;
    $user->password  = Hash::make(Input::get('password'));

    if ($user->group == 'Teacher' && Input::get('teacher_id')) {
      $user->group_id = (int) Input::get('teacher_id');
      $teacher = Teacher::find($user->group_id);
      if ($teacher && !empty($teacher->phone)) {
        $user->phone = $teacher->phone;
      }
    } elseif ($user->group == 'Student' && Input::get('student_id')) {
      $user->group_id = (int) Input::get('student_id');
      $student = Student::find($user->group_id);
      if ($student) {
        $user->regiNo = $student->regiNo;
      }
    }

    $user->save();
    return Redirect::to('/users')->with("success", "User '{$user->login}' created successfully with role '{$user->group}'.");
  }

  public function edit($id)
  {
    if (!$this->ensureAdmin()) {
      return Redirect::to('/dashboard')->with('error', 'Access denied. Only administrators can manage users and assign roles.');
    }
    $user = User::find($id);
    if (!$user) {
      return Redirect::to('/users')->with('error', 'User not found.');
    }
    $users = User::orderBy('id', 'desc')->get();
    $teachers = Teacher::select('id', 'firstName', 'lastName', 'phone', 'email')->orderBy('firstName')->get();
    $students = Student::select('id', 'regiNo', 'firstName', 'lastName')->orderBy('regiNo')->get();
    return View('app.users', compact('users', 'user', 'teachers', 'students'));
  }

  public function update()
  {
    if (!$this->ensureAdmin()) {
      return Redirect::to('/dashboard')->with('error', 'Access denied. Only administrators can manage users and assign roles.');
    }
    $userId = Input::get('id');
    $rules = [
      'id'        => 'required|exists:users,id',
      'firstname' => 'required',
      'lastname'  => 'nullable',
      'email'     => 'nullable|email',
      'group'     => 'required|in:Admin,Teacher,Student,Accountant,Director,Staff',
      'desc'      => 'nullable',
      'login'     => 'required|unique:users,login,' . $userId,
      'password'  => 'nullable|min:4'
    ];
    $validator = \Validator::make(Input::all(), $rules);
    if ($validator->fails()) {
      return Redirect::to('/useredit/' . $userId)->withInput(Input::all())->withErrors($validator);
    }

    $email = Input::get('email');
    if (!empty($email)) {
      if (User::where('email', $email)->where('id', '!=', $userId)->exists()) {
        return Redirect::to('/useredit/' . $userId)->withInput(Input::all())->withErrors(['email' => 'Another user already exists with this email address.']);
      }
    }

    $user = User::find($userId);
    $user->firstname = Input::get('firstname');
    $user->lastname  = Input::get('lastname') ?: '';
    $user->login     = Input::get('login');
    $user->desc      = Input::get('desc') ?: '';
    $user->email     = $email ?: null;
    $user->group     = Input::get('group');

    if (!empty(Input::get('password'))) {
      $user->password = Hash::make(Input::get('password'));
    }

    if ($user->group == 'Teacher') {
      if (Input::get('teacher_id')) {
        $user->group_id = (int) Input::get('teacher_id');
        $teacher = Teacher::find($user->group_id);
        if ($teacher && !empty($teacher->phone)) {
          $user->phone = $teacher->phone;
        }
      }
    } elseif ($user->group == 'Student') {
      if (Input::get('student_id')) {
        $user->group_id = (int) Input::get('student_id');
        $student = Student::find($user->group_id);
        if ($student) {
          $user->regiNo = $student->regiNo;
        }
      }
    } else {
      $user->group_id = null;
    }

    $user->save();
    return Redirect::to('/users')->with("success", "User '{$user->login}' updated successfully. Role assigned: '{$user->group}'.");
  }

  public function delete($id)
  {
    if (!$this->ensureAdmin()) {
      return Redirect::to('/dashboard')->with('error', 'Access denied. Only administrators can manage users.');
    }
    if (Auth::id() == $id) {
      return Redirect::to('/users')->with('error', 'You cannot delete your own active administrator account.');
    }
    $user = User::find($id);
    if ($user) {
      $login = $user->login;
      $user->delete();
      return Redirect::to('/users')->with("success", "User '{$login}' deleted successfully.");
    }
    return Redirect::to('/users')->with("error", "User not found.");
  }

  public function generateCode($codeLength = 4)
  {
    $min = pow(10, $codeLength);
    $max = $min * 10 - 1;
    return mt_rand($min, $max);
  }

  /**
   * Self-registration is disabled. Only administrators can sign up users.
   */
  public function showSignup()
  {
    return Redirect::to('/')->with('error', 'Self-registration is disabled. Only administrators can sign up teachers and students.');
  }

  public function processSignup(request $request)
  {
    return Redirect::to('/')->with('error', 'Self-registration is disabled. Only administrators can sign up teachers and students.');
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
   * Code-based registration has been deprecated. Redirect to Users management.
   */
  public function showCodes()
  {
    return Redirect::to('/users')->with('info', 'Code-based self-registration is disabled. Administrators can sign up teachers and students directly.');
  }

  public function generateRegistrationCode(request $request)
  {
    return Redirect::to('/users')->with('info', 'Code-based self-registration is disabled. Administrators can sign up teachers and students directly.');
  }

  public function deleteCode($id)
  {
    return Redirect::to('/users')->with('info', 'Code-based self-registration is disabled.');
  }

}
