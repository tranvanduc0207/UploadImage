<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;
use App\Models\User;
use Cache;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;



class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }
    public function create()
    {
        $href = URL::full();
        $author_code = $this->getCodeByHref($href);
        return view('image_upload', compact('author_code'));
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    
    public function upload(Request $request)
    {
        try {
            $author_code = $request->input('author_code');
            //Get image 
            $img = $request->file('images');
            $path = public_path('aaa');
            
            $originName = $img->getClientOriginalName($img);
            if (FALSE === \File::exists($path)) {
                if (TRUE !== \File::makeDirectory($path)) {
                    return false;
                }
                chmod($path, 0777);
            }
            $file_name = strtotime(date('Y-m-d H:i:s')) . $originName;
            $img->move($path, $file_name);
            $path_upload = $path . '/'. $file_name;
            //Get access token
            $user = User::find(Auth::id());
            
            if($user->refresh_token == Null){
                $res_auth = Cache::remember('res_auth', '5000', function () use ($user, $author_code) {
                    return $this->getAccessToken($user, $author_code);
                });
                $authorization = $res_auth->access_token;
                $user->access_token = $authorization;
                $user->refresh_token = $res_auth->refresh_token;
                $user->save();
            }else{
                $res_auth = Cache::remember('res_auth', '5000', function () use ($user) {
                    return $this->getRefreshToken($user);
                });
                $authorization = $res_auth->access_token;
            }
           
            // Upload image to Dropbox
            $this->uploadImage($path_upload, $authorization, $file_name);
            // Get link image just uploaded
            $result = $this->insertImageLink($path_upload, $authorization, $file_name);
            // Insert link to DB
            $images = new Image();
            $images->name = $result['url'];
            $images->save();

            return redirect('/create')->with('success', 'Upload image successful');
        } catch (\Throwable $th) {
            Log::error('error: Can not upload image now!'. $th->getMessage());
            return redirect('/create')->with('error', 'Can not upload image now');
        }
    }

    public function uploadImage($path_upload, $authorization, $file_name)
    {
        $fp = fopen($path_upload, 'rb');
        $cheaders = array(
            'Authorization: Bearer ' . $authorization,
            'Content-Type: application/octet-stream',
            'Dropbox-API-Arg: {"path":"/test/' . $file_name . '", "mode":"add","autorename": true,"mute": false,"strict_conflict": false}'
        );
        $ch = curl_init('https://content.dropboxapi.com/2/files/upload');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $cheaders);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        // curl_setopt($ch, CURLOPT_INFILESIZE, $size);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        return $response;
    }
    public function insertImageLink($path_upload, $authorization, $file_name)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$authorization,
            'Content-Type' => 'application/json',
        ])->post('https://api.dropboxapi.com/2/sharing/create_shared_link', [
            "path" => "/test/" . $file_name,
            'short_url' => false,
        ]);
        if($response->getStatusCode() != '200'){
            return false;
        }
        $res = $response->body();

        return json_decode($res, true);
       
    }
    public function getAccessToken($user, $author_code)
    {
        $params = [
            'grant_type' => 'authorization_code',
            'code' => $author_code,
            'redirect_uri' => 'http://localhost/create',
        ];
        $client = new Client();
        $response = $client->request('POST', 'https://api.dropboxapi.com/oauth2/token', [
            'query' => $params,
            'auth' => [trim($user->client_id), trim($user->client_key_secret)]
        ]);
        if ($response->getStatusCode() != '200') {
            return false;
        }
        return json_decode($response->getBody());
    }

    public function getRefreshToken($user)
    {
        $client = new Client();
        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $user->refresh_token,
        ];
        $respone = $client->request('POST', 'https://api.dropboxapi.com/oauth2/token', [
            'query' => $params,
            'auth' => [$user->client_id, trim($user->client_key_secret)]
        ]);
        if($respone->getStatusCode() != '200'){
            return false;
        }
        $res = $respone->getBody();
        return json_decode($res);
    }
    public function getCodeByHref($href) {
        if (trim($href) == '') {
            Log::error('Do not exists');
        }
        $arr = explode("http://localhost/create?code=", $href);
        if (isset($arr[1])){
           return $arr[1];
        }
        return null;
    }
}
