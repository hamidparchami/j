<?php

namespace App\Http\Controllers\Admin;

use App\VariableValue;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;

class VariableValueController extends Controller
{
    public function index()
    {
        $variable_values = VariableValue::all();
        return view('admin.variable_value.variable_value_manage', compact('variable_values'));
    }

    public function getCreate()
    {
        return view('admin.variable_value.variable_value_form');
    }

    public function postCreate(Request $request)
    {
        $rules = [
            'variable'  => 'required|unique:variable_values|max:255',
            'value'     => 'required',
        ];

        Validator::make($request->all(), $rules)->validate();

        $request['is_active'] = ($request['is_active'] == 'on' || $request['is_active'] == '1') ? 1 : 0;
        VariableValue::create($request->all());

        return redirect('/admin/variable-value/manage')->with('message', 'متغیر با موفقیت ذخیره شد.');
    }

    public function getEdit($id)
    {
        $variable_value = VariableValue::find($id);
        return view('admin.variable_value.variable_value_form', compact('variable_value'));
    }

    public function postEdit(Request $request)
    {
        $variable_value = VariableValue::find($request->id);
        $rules = [
            'variable'  => 'required|max:255|unique:variable_values,variable,'.$variable_value->id,
            'value'     => 'required',
        ];

        Validator::make($request->all(), $rules)->validate();

        $request['is_active'] = ($request['is_active'] == 'on' || $request['is_active'] == '1') ? 1 : 0;
        $variable_value->update($request->all());

        return redirect('/admin/variable-value/manage')->with('message', 'متغیر با موفقیت ذخیره شد.');
    }

    public function getDelete($id)
    {
        VariableValue::destroy($id);
        return redirect('/admin/variable-value/manage')->with('message', 'متغیر با موفقیت حذف شد.');
    }
}
