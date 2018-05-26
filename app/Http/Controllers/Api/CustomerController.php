<?php

namespace App\Http\Controllers\Api;

use App\Customer;
use App\Lib\GeneralFunctions;
use App\Token;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Validator;

class CustomerController extends Controller
{
    public function postGenerateOTP(Request $request)
    {
        //validate phone number
        $rules = [
            'account_id'    => 'sometimes|min:32|max:64',
            'phone_number'  => ['required', 'regex:/09(0[1-2]|1[0-9]|3[0-9]|2[0-1])-?[0-9]{3}-?[0-9]{4}/'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error_code' => '1010',  'error' => $validator->errors()]);
        }

        //check maximum request @TODO
        //create or first customer
        $account_id = (!empty($request->account_id)) ? $request->account_id : GeneralFunctions::GUID();
//        $customer = Customer::firstOrCreate(['account_id' => $account_id, 'phone_number' => $request->phone_number]);
        $customer = Customer::updateOrCreate(
                                            ['phone_number' => $request->phone_number],
                                            ['account_id' => $account_id]
                                            );
        //generate OTP
        $otp = rand(1000, 9999);
        //store OTP
        Token::create(['customer_id' => $customer->id, 'otp' => $otp]);
        //send OTP via SMS @TODO
        //return result
        $success = true;
        return compact('success', 'account_id');
    }

    public function putVerifyOTP(Request $request)
    {
        //validate otp pattern and account id
        $rules = [
            'account_id'    => 'required|min:32|max:64',
            'otp'           => 'required|numeric|digits:4',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error_code' => '1020', 'error' => $validator->errors()]);
        }

        //find customer
        $customer = Customer::where('account_id', $request->account_id)->first();
        //get latest valid otp in db (does no have token)
        $token = Token::where('customer_id', $customer->id)
                        ->where('otp', $request->otp)
                        ->whereNull('token')
                        ->orderBy('id', 'desc')
                        ->first();
        //if succeed
        if (!is_null($token)) {
            $success            = true;
            $generated_token    = Hash::make(uniqid()); //generate token
            //store token
            $token->update(['token' => $generated_token, 'is_valid' => 1]);
            //return token
            return compact('success', 'generated_token');
        } else {
            //return error
            $success    = false;
            $error_code = '1021';
            $error      = (object)['otp' => 'OTP is not correct.'];
            return compact('success', 'error_code', 'error');
        }
    }
}
