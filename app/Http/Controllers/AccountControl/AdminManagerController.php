<?php

namespace App\Http\Controllers\AccountControl;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\User;
use comp_hack\API as TempApi;

class AdminManagerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
      $api = $this->api();
      $users = $api->GetAccounts();

      if($users === false) {
        return redirect()->route('login');
      }

      return view('accountcontrol.adminmanager', compact('users'));
    }

    public function delete($username)
    {
      $api = $this->api();

      $response = $api->DeleteAccount($username);
      $user = User::where('username', $username)->first();
      if(!$user != null) {
        $user->delete();
      }

      return redirect('/accountmanager');
    }

    public function update(Request $request)
    {
      $sendArray = array();
      $reqArray = json_decode($request->getContent());
      $username;

      foreach($reqArray as $object)
      {
        if($object->value != "")
        {
          $sendArray = array_merge(array($object->name=>$object->value), $sendArray);
        }
      }

      if(!array_key_exists('username', $sendArray))
      {
        return 'No username in request';
      }
      else
      {
        $username = $sendArray['username'];
        unset($sendArray['username']);
      }

      if(array_key_exists('enabled', $sendArray))
      {
        if($sendArray['enabled'] == 'enabled')
        {
          $sendArray['enabled'] = true;
        }
        else
        {
          $sendArray['enabled'] = false;
        }
      }

      if(array_key_exists('user_level', $sendArray)) {
        $sendArray['user_level'] = (int)$sendArray['user_level'];
      }

      if(array_key_exists('cp', $sendArray)) {
        $sendArray['cp'] = (int)$sendArray['cp'];
      }

      if(array_key_exists('ticket_count', $sendArray)) {
        $sendArray['ticket_count'] = (int)$sendArray['ticket_count'];
      }

      $api = $this->api();
      $response = $api->UpdateAccount($username, $sendArray);
      if(!$response || !is_object($response) || $response->error != 'Success')
      {
        return 'Bad API Response';
      }

      $user = User::where('username', $username)->first();

      if(array_key_exists('disp_name', $sendArray))
      {
        $user->update(['name'=>$sendArray['disp_name']]);
      }
      if(array_key_exists('password', $sendArray))
      {
        $user->update(['password' => Hash::make($sendArray['password'])]);

        $user_api = new \comp_hack\API(config('comphack.api'), $username);

        if($user_api->Authenticate($sendArray['password']))
        {
          $user->update(['server_hash' => $user_api->GetPasswordHash()]);
        }
      }
      if(array_key_exists('user_level', $sendArray)) {
        $user->update(['admin' => (1000 <= $sendArray['user_level'] ? true : false)]);
      }

      return 'success';
    }
}
