<?php

namespace App\Http\Controllers;

use App\Helpers\EmailTemplateHelpers;
use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Storage;

use App\Models\Admins;
use App\Models\AuthenticationTokens;
use Exception;

class AdminController extends Controller
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/admins/store",
     *      operationId="storeAdmins",
     *      tags={"Admins"},
     *      summary="Store new or update admins",
     *      description="Returns admins data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - Remove the admin_id object for create new data Admin <b>or</b>
                    <br> - Fill in the admin_id object for update data Admin
                    <br> - Empty object if you don't want to change data
                    <br> - role ( 1 = superadmin, 2, admin staff )
                    <br> - soft_delete (false = active, true = deleted)",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="admin_id", type="string", example="e984fad3-bb0e-4416-bee6-8da0923b83e0"),
     *              @OA\Property(property="fullname", type="string", example="sol al-likin"),
     *              @OA\Property(property="email", type="string", example="solikin@test.co"),
     *              @OA\Property(property="password", type="string", example="solikin"),
     *              @OA\Property(property="profile_photo", type="string", example="base_64-string"),
     *              @OA\Property(property="role", type="integer", example="1"),
     *              @OA\Property(property="soft_delete", type="boolean", default="false"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful Operation",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          description="The response data",
     *                      ),
     *                      example={
     *                          "status_code": 200,
     *                          "success": true,
     *                          "data": {
     *                              "admin_id": "e984fad3-bb0e-4416-bee6-8da0923b83e0",
                                    "fullname": "sol al-likin",
                                    "email": "solikin@test.co",
                                    "password": "$2y$12$Aum3lT5OhXNG5D7b7t.f6uMIERRDXHPgfpvza6lGjBZ8hzoK91QrG",
                                    "profile_photo": "https://solikins/image.png",
                                    "role": 1,
                                    "soft_delete": 0,
                                    "created_at": "2024-01-17T09:04:50.000000Z",
                                    "updated_at": "2024-01-17T09:14:08.000000Z"
     *                          }
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="errors",
     *                          type="string",
     *                          description="The response error reason",
     *                      ),
     *                      example={
     *                          "status_code": 400,
     *                          "success": false,
     *                          "errors": "Reason of bad request operation.",
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="error",
     *                          type="string",
     *                          description="The response error code",
     *                      ),
     *                      @OA\Property(
     *                          property="detail",
     *                          type="string",
     *                          description="The response error detail",
     *                      ),
     *                      example={
     *                          "status_code": 401,
     *                          "success": false,
     *                          "error": "tokenNotFound",
     *                          "detail": "Authorization Token not found",
     *                      },
     *                  )
     *              ),
     *          }
     *      ),
     * )
     */
    public function store(Request $request)
    {
        $requestData = $request->only([
            'admin_id',
            'fullname',
            'email',
            'password',
            'profile_photo',
            'role',
            'soft_delete',
        ]);

        $admin_id = $requestData['admin_id'] ?? null;
        $fullname = $requestData['fullname'] ?? null;
        $email = $requestData['email'] ?? null;
        $password = $requestData['password'] ?? null;
        $profile_photo = $requestData['profile_photo'] ?? null;
        $role = $requestData['role'] ?? null;
        $soft_delete = $requestData['soft_delete'] ?? null;

        if ($admin_id != null) {
            $data = Admins::find($admin_id);
            if (!$data) return ResultHelpers::errors('Data admin not found.', 400, false);
        } else {
            if (!$email) return ResultHelpers::errors('Email must filled for new data.', 400, false);
            if (!$password) return ResultHelpers::errors('Password must filled for new data', 400, false);

            $usedData = Admins::where('email', $email)
                ->first();

            if ($usedData) return ResultHelpers::errors('Email is already in use.', 400, false);

            $data = new Admins();
            $data->admin_id = Str::uuid();
        }

        if ($profile_photo) {
            try {
                if ($data->profile_photo) Storage::disk('public')->delete(str_replace(env('APP_URL') . '/storage/', '', $data->profile_photo));
                $image_64 = $profile_photo; //your base64 encoded data

                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png .pdf

                $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

                // find substring fro replace here eg: data:image/png;base64,
                $image = str_replace($replace, '', $image_64);

                $image = str_replace(' ', '+', $image);
                $image = str_replace('svg+xml', 'svg', $image);

                $image_name = Carbon::now()->timestamp . '-' . Str::random(10) . '.' . $extension;
                $image_path = 'admins-profile/' . $image_name;
                $image_path = str_replace('svg+xml', 'svg', $image_path);

                Storage::disk('public')->put($image_path, base64_decode($image));

                $data->profile_photo = $image_path;
            } catch (\Throwable $th) {
                // throw $th;
            }
        }

        if ($email && $data->email != $email) {
            $usedData = Admins::where('email', $email)
                ->first();

            if ($usedData) return ResultHelpers::errors('Email is already in use.', 400, false);
            $data->email = $email;
        }
        $fullname && $data->fullname = $fullname;
        $role && $data->role = $role;
        $password && $password = Hash::make($password);
        $password && $data->password = $password;
        $soft_delete !== null && $data->soft_delete = $soft_delete;
        $data->save();

        return ResultHelpers::success($data, 200, true);
    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/admins/login",
     *      operationId="loginAdmins",
     *      tags={"Admins"},
     *      summary="Login admins",
     *      description="Returns admins data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>Email</b> can be filled by email",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="email", type="string", example="solikin@test.co"),
     *              @OA\Property(property="password", type="string", example="solikin"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful Operation",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          description="The response data",
     *                      ),
     *                      example={
     *                          "status_code": 200,
     *                          "success": true,
     *                          "data": {
     *                              "id": "e984fad3-bb0e-4416-bee6-8da0923b83e0",
                                    "fullname": "sol al-likin",
                                    "email": "solikin@test.co",
                                    "profile_photo": "https://solikins/image.png",
                                    "role": 1,
                                    "token": "aPpLkwDst3jB3jtETh727Ood9npcKwZj06UuruCLRaFnrGfQMBPAMzghHpdJvSY9Xq2BzJAOPf66kOdWCmsCtdJGM0C2Iyf3glgl"
     *                          }
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="errors",
     *                          type="string",
     *                          description="The response error reason",
     *                      ),
     *                      example={
     *                          "status_code": 400,
     *                          "success": false,
     *                          "errors": "Reason of bad request operation.",
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="error",
     *                          type="string",
     *                          description="The response error code",
     *                      ),
     *                      @OA\Property(
     *                          property="detail",
     *                          type="string",
     *                          description="The response error detail",
     *                      ),
     *                      example={
     *                          "status_code": 401,
     *                          "success": false,
     *                          "error": "tokenNotFound",
     *                          "detail": "Authorization Token not found",
     *                      },
     *                  )
     *              ),
     *          }
     *      ),
     * )
     */
    public function login(Request $request)
    {
        $requestData = $request->only([
            'email',
            'password',
        ]);

        $email = $requestData['email'] ?? null;
        $password = $requestData['password'] ?? null;

        $data = Admins::where('email', $email)
            ->select([
                'admin_id AS id',
                'fullname',
                'email',
                'password',
                'profile_photo',
                'role',
            ])
            ->first();

        if (!$data) return ResultHelpers::errors('Data admin not found.', 400, false);
        if ($data->soft_delete == 1) return ResultHelpers::errors('Data admin was deleted.', 400, false);

        $decrypt = Hash::check($password, $data->password);
        if (!$decrypt) return ResultHelpers::errors('Wrong password.', 400, false);

        $str_token = Str::random(100);
        $expired = Carbon::now()->addDays(3);

        $auth_token = new AuthenticationTokens();
        $auth_token->authentication_token_id = Str::uuid();
        $auth_token->user_auth = "admin-" . $data->admin_id;
        $auth_token->token = $str_token;
        $auth_token->expired = $expired;
        $auth_token->save();

        $data['token'] = $str_token;
        unset($data['password']);

        AuthenticationTokens::where('expired', '<=', Carbon::now()->subDays(1))
            ->forceDelete();

        return ResultHelpers::success($data, 200, true);
    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Get(
     *      path="/admins/show",
     *      operationId="showAdmins",
     *      tags={"Admins"},
     *      summary="Show admins",
     *      description="Returns admins data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - Fill <b>admin_id</b> for retrieve only 1 data.",
     *          required=false,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="admin_id", type="string", example="e984fad3-bb0e-4416-bee6-8da0923b83e0"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful Operation",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="data",
     *                          type="array",
     *                          description="The response data",
     *                          @OA\Items,
     *                      ),
     *                      example={
     *                          "status_code": 200,
     *                          "success": true,
     *                          "data": {
                                {
                                "admin_id": "fa17ec43-db7f-4e1d-8041-bc29ebf6b56b",
                                "email": "solikin@test.co",
                                "fullname": "sol al-likin",
                                "profile_photo": null,
                                "created_at": "2024-01-17T09:04:31.000000Z"
                                },
                                {
                                "admin_id": "e984fad3-bb0e-4416-bee6-8da0923b83e0",
                                "email": "solikin@test.co",
                                "fullname": "sol al-likin",
                                "profile_photo": null,
                                "created_at": "2024-01-17T09:04:50.000000Z"
                                }
     *                          }
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="errors",
     *                          type="string",
     *                          description="The response error reason",
     *                      ),
     *                      example={
     *                          "status_code": 400,
     *                          "success": false,
     *                          "errors": "Reason of bad request operation.",
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="error",
     *                          type="string",
     *                          description="The response error code",
     *                      ),
     *                      @OA\Property(
     *                          property="detail",
     *                          type="string",
     *                          description="The response error detail",
     *                      ),
     *                      example={
     *                          "status_code": 401,
     *                          "success": false,
     *                          "error": "tokenNotFound",
     *                          "detail": "Authorization Token not found",
     *                      },
     *                  )
     *              ),
     *          }
     *      ),
     * )
     */
    public function show(Request $request)
    {
        $requestData = $request->only([
            'admin_id',
        ]);

        $admin_id = $requestData['admin_id'] ?? null;

        if ($admin_id) {
            $data = Admins::where('soft_delete', 0)
                ->select(
                    'admin_id',
                    'email',
                    'fullname',
                    'profile_photo',
                    'created_at',
                )
                ->find($admin_id);
            if (!$data) return ResultHelpers::errors('Account with that ID is not found.', 400, false);
        } else {
            $data = Admins::where('soft_delete', 0)
                ->orderBy('created_at', 'DESC')
                ->select([
                    'admin_id',
                    'email',
                    'fullname',
                    'profile_photo',
                    'created_at',
                ])
                ->get();
        }


        return ResultHelpers::success($data, 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/admins/forgot-password/request",
     *      operationId="forgotPasswordRequestAdmin",
     *      tags={"Admins"},
     *      summary="Request email sent for forgot password Admin",
     *      description="Returns admin data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>Email</b> can be filled by email to resend forgot password code",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="email", type="string", example="solikin@test.co"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful Operation",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          description="The response data",
     *                      ),
     *                      example={
     *                          "status_code": 200,
     *                          "success": true,
     *                          "data": "Email has been sent"
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="errors",
     *                          type="string",
     *                          description="The response error reason",
     *                      ),
     *                      example={
     *                          "status_code": 400,
     *                          "success": false,
     *                          "errors": "Reason of bad request operation.",
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="error",
     *                          type="string",
     *                          description="The response error code",
     *                      ),
     *                      @OA\Property(
     *                          property="detail",
     *                          type="string",
     *                          description="The response error detail",
     *                      ),
     *                      example={
     *                          "status_code": 401,
     *                          "success": false,
     *                          "error": "tokenNotFound",
     *                          "detail": "Authorization Token not found",
     *                      },
     *                  )
     *              ),
     *          }
     *      ),
     * )
     */
    public function forgotPasswordRequest(Request $request)
    {
        $requestData = $request->only([
            'email',
        ]);

        $email = $requestData['email'] ?? null;
        if (!$email) return ResultHelpers::errors('Email must filled.', 400, false);

        $data = Admins::where('email', $email)
            ->first();
        if (!$data) return ResultHelpers::errors('Data Admin not found, please register first.', 400, false);

        if (Carbon::now()->diffInMinutes(Carbon::parse($data->updated_at)) >= 5 || $data->updated_at == $data->created_at) {
            $forgot_code = Str::random(25);
            $data->forgot_password = $forgot_code;

            require base_path("vendor/autoload.php");
            $mail = new PHPMailer(true);     // Passing `true` enables exceptions
            $mainWebsiteUrl =  env("MAIN_WEBSITE_URL");
            try {
                $mail->SMTPDebug = 0;
                $mail->isSMTP();
                $mail->Host = env('MAIL_HOST');
                $mail->SMTPAuth = true;
                $mail->Username = env('MAIL_USERNAME');
                $mail->Password = env('MAIL_PASSWORD');
                $mail->SMTPSecure = env('MAIL_ENCRYPTION');
                $mail->Port = env('MAIL_PORT');

                $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                $mail->addAddress($data->email);
                $mail->addCC($data->email);
                $mail->addBCC($data->email);

                $mail->isHTML(true);

                $mail->Subject = 'WorkNook Mailer';
                $emailTemplate = EmailTemplateHelpers::EmailVerifyWithCode("$mainWebsiteUrl/admin/reset-password/$forgot_code", 'Reset Password', 'To reset your password, please follow the instructions below. Enter the following code on the reset password page or click the button below if provided.', $forgot_code, 'reset your password', 'Your Code');
                $mail->Body = "$emailTemplate";

                // $mail->AltBody = plain text version of email body;

                if (!$mail->send()) return ResultHelpers::errors($mail->ErrorInfo, 400, false);
                else {
                    $data->save();
                    return ResultHelpers::success('Email has been sent.', 200, true);
                }
            } catch (\Throwable $th) {
                return ResultHelpers::errors('Message could not be sent.', 400, false);
            }
        } else {
            $timeToWaiting = 5 - (int)Carbon::now()->diffInMinutes(Carbon::parse($data->updated_at));
            return ResultHelpers::errors('Please wait ' . $timeToWaiting . ' minutes to resend forgot password email again.', 400, false);
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/admins/forgot-password/check-code",
     *      operationId="forgotPasswordCheckCodeAdmin",
     *      tags={"Admins"},
     *      summary="Check code forgot password admins",
     *      description="Returns forgot password admins data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - code is required",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="code", type="string", example="codeforgotpassword"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful Operation",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          description="The response data",
     *                      ),
     *                      example={
     *                          "status_code": 200,
     *                          "success": true,
     *                          "data": {
                                    "admin_id": "e984fad3-bb0e-4416-bee6-8da0923b83e0",
                                    "fullname": "sol al-likin",
                                    "email": "solikin@test.co",
                                    "password": "$2y$12$Aum3lT5OhXNG5D7b7t.f6uMIERRDXHPgfpvza6lGjBZ8hzoK91QrG",
                                    "profile_photo": null,
                                    "role": 1,
                                    "forgot_password": "abc",
                                    "soft_delete": 0,
                                    "created_at": "2024-01-17T09:04:50.000000Z",
                                    "updated_at": "2024-01-17T09:14:08.000000Z"
     *                          }
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="errors",
     *                          type="string",
     *                          description="The response error reason",
     *                      ),
     *                      example={
     *                          "status_code": 400,
     *                          "success": false,
     *                          "errors": "Reason of bad request operation.",
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="error",
     *                          type="string",
     *                          description="The response error code",
     *                      ),
     *                      @OA\Property(
     *                          property="detail",
     *                          type="string",
     *                          description="The response error detail",
     *                      ),
     *                      example={
     *                          "status_code": 401,
     *                          "success": false,
     *                          "error": "tokenNotFound",
     *                          "detail": "Authorization Token not found",
     *                      },
     *                  )
     *              ),
     *          }
     *      ),
     * )
     */
    public function forgotPasswordCheckCode(Request $request)
    {
        $requestData = $request->only([
            'code',
        ]);

        $code = $requestData['code'] ?? null;

        $data = Admins::where('forgot_password', $code)->first();
        if (!$data) return ResultHelpers::errors('Account with that code forgot password is not found.', 400, false);

        return ResultHelpers::success($data, 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/admins/forgot-password/submit",
     *      operationId="forgotPasswordSubmitAdmins",
     *      tags={"Admins"},
     *      summary="Submit forgot password with code admins",
     *      description="Returns admins data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>Email</b>, <b>Code</b>, and <b>Password</b> is required.",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="email", type="string", example="solikin@test.co"),
     *              @OA\Property(property="code", type="string", example="aMsaLz31dsa)31ewq@1sdf^$kjsdnf)(#)"),
     *              @OA\Property(property="new_password", type="string", example="solikin"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful Operation",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          description="The response data",
     *                      ),
     *                      example={
     *                          "status_code": 200,
     *                          "success": true,
     *                          "data": "Password has been changed."
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="errors",
     *                          type="string",
     *                          description="The response error reason",
     *                      ),
     *                      example={
     *                          "status_code": 400,
     *                          "success": false,
     *                          "errors": "Reason of bad request operation.",
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="error",
     *                          type="string",
     *                          description="The response error code",
     *                      ),
     *                      @OA\Property(
     *                          property="detail",
     *                          type="string",
     *                          description="The response error detail",
     *                      ),
     *                      example={
     *                          "status_code": 401,
     *                          "success": false,
     *                          "error": "tokenNotFound",
     *                          "detail": "Authorization Token not found",
     *                      },
     *                  )
     *              ),
     *          }
     *      ),
     * )
     */
    public function forgotPasswordSubmit(Request $request)
    {
        $requestData = $request->only([
            'email',
            'code',
            'new_password',
        ]);

        $email = $requestData['email'] ?? null;
        $code = $requestData['code'] ?? null;
        $new_password = $requestData['new_password'] ?? null;
        if (!$email) return ResultHelpers::errors('Email must filled.', 400, false);
        if (!$code) return ResultHelpers::errors('Code must filled.', 400, false);
        if (!$new_password) return ResultHelpers::errors('New Password must filled.', 400, false);

        $data = Admins::where('email', $email)
            ->first();
        if (!$data) return ResultHelpers::errors('Data account not found, please register first.', 400, false);

        if ($data->forgot_password == $code) {
            $password = Hash::make($new_password);
            $data->forgot_password = null;
            $data->password = $password;
            $data->save();
        } else return ResultHelpers::errors('Code was invalid.', 400, false);

        return ResultHelpers::success('Password has been changed.', 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/admins/forgot-password-v2",
     *      operationId="forgotPasswordV2Admins",
     *      tags={"Admins"},
     *      summary="forgot password v2",
     *      description="forgot password V2 is sending by email and got the password",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>admin_id</b> is required.",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="email", type="string", example="rifald84@gmail.com"),
     *              @OA\Property(property="current_password", type="string", example="rifaldi"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful Operation",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          description="The response data",
     *                      ),
     *                      example={
     *                          "status_code": 200,
     *                          "success": true,
     *                          "data": "Password has been changed."
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="errors",
     *                          type="string",
     *                          description="The response error reason",
     *                      ),
     *                      example={
     *                          "status_code": 400,
     *                          "success": false,
     *                          "errors": "Reason of bad request operation.",
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="error",
     *                          type="string",
     *                          description="The response error code",
     *                      ),
     *                      @OA\Property(
     *                          property="detail",
     *                          type="string",
     *                          description="The response error detail",
     *                      ),
     *                      example={
     *                          "status_code": 401,
     *                          "success": false,
     *                          "error": "tokenNotFound",
     *                          "detail": "Authorization Token not found",
     *                      },
     *                  )
     *              ),
     *          }
     *      ),
     * )
     */
    public function forgotPasswordV2(Request $request)
    {
        $requestData = $request->only([
            'email',
            'current_password',
        ]);
        $email = $requestData['email'] ?? null;
        $password = $requestData['current_password'] ?? null;

        $admin = Admins::where('email', $email)->first();

        if (!$admin) {
            return ResultHelpers::errors('admin with email not found', 400, false);
        }
        $decrypt = Hash::check($password, $admin->password);
        if (!$decrypt) return ResultHelpers::errors('Wrong password.', 400, false);

        require base_path("vendor/autoload.php");
        $mail = new PHPMailer(true);     // Passing `true` enables exceptions

        try {
            $new_password = Str::random(10);
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = env('MAIL_USERNAME');
            $mail->Password = env('MAIL_PASSWORD');
            $mail->SMTPSecure = env('MAIL_ENCRYPTION');
            $mail->Port = env('MAIL_PORT');

            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->addAddress($admin->email);
            $mail->addCC($admin->email);
            $mail->addBCC($admin->email);

            $mail->isHTML(true);

            $mail->Subject = 'Worknook Reset Password';
            $emailTemplate = EmailTemplateHelpers::EmailVerifyWithCode("", 'Reset Password', 'We have received a request to reset your account password. Your password has been reset successfully.', $new_password, 'Back to site', 'Your Password');
            $mail->Body = "$emailTemplate";

            if (!$mail->send()) return ResultHelpers::errors($mail->ErrorInfo, 400, false);
            else {
                $admin->password = Hash::make($new_password);
                $admin->save();
                return ResultHelpers::success('new password has been sent to your email.', 200, true);
            }
        } catch (\Throwable $th) {
            return ResultHelpers::errors('Message could not be sent.', 400, false);
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/admins/reset-password",
     *      operationId="resetPasswordAdmin",
     *      tags={"Admins"},
     *      summary="reset password",
     *      description="reset password",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>email</b> is required.",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="email", type="string", example="robert@test.co"),
     *              @OA\Property(property="current_password", type="string", example="robert"),
     *              @OA\Property(property="new_password", type="string", example="robert123"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful Operation",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="data",
     *                          type="object",
     *                          description="The response data",
     *                      ),
     *                      example={
                                "status_code": 200,
                                "success": true,
                                "data": {
                                    "email": "robert@test.co",
                                    "new_password": "robert"
                                }
                            }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="errors",
     *                          type="string",
     *                          description="The response error reason",
     *                      ),
     *                      example={
     *                          "status_code": 400,
     *                          "success": false,
     *                          "errors": "Reason of bad request operation.",
     *                      }
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="status_code",
     *                          type="integer",
     *                          description="The response status code"
     *                      ),
     *                      @OA\Property(
     *                          property="success",
     *                          type="boolean",
     *                          description="The response success"
     *                      ),
     *                      @OA\Property(
     *                          property="error",
     *                          type="string",
     *                          description="The response error code",
     *                      ),
     *                      @OA\Property(
     *                          property="detail",
     *                          type="string",
     *                          description="The response error detail",
     *                      ),
     *                      example={
     *                          "status_code": 401,
     *                          "success": false,
     *                          "error": "tokenNotFound",
     *                          "detail": "Authorization Token not found",
     *                      },
     *                  )
     *              ),
     *          }
     *      ),
     * )
     */

    public function resetPassword(Request $request)
    {
        $requestData = $request->only([
            'email',
            'new_password',
            'current_password'
        ]);

        $email = $requestData['email'] ?? null;
        $newPassword = $requestData['new_password'];
        $passwordOld = $requestData['current_password'] ?? null;

        $data = Admins::where('email', $email)->first();
        if (!$data) return ResultHelpers::errors('Data Admin not found', 400, false);
        if ($data->soft_delete == 1) return ResultHelpers::errors('Data admin was deleted.', 400, false);

        $decrypt = Hash::check($passwordOld, $data->password);
        if (!$decrypt) return ResultHelpers::errors('Wrong Password', 400, false);

        $data->password = Hash::make($newPassword);
        $data->save();

        $data_post = [
            "email" => $email,
            "new_password" => $newPassword
        ];

        return ResultHelpers::success($data_post, 200, true);
    }
}
