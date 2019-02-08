<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-2476758-14"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'UA-2476758-14');
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مجله جیک و پیک</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous"><link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link href="/css/app.css?v=0.1" rel="stylesheet">
    <link href="/css/common.css?v=0.1" rel="stylesheet">
</head>
<body class="subscription-body">
<div class="container subscription-main-container">

<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12" style="margin-bottom: 15px; color: #FFFFFF;">
        <div class="col-md-6 col-sm-6 col-xs-6 rtl"><span class="subscription-step-number @if($step == 'subscribed')subscription-active-step @endif">۲</span> دریافت هدیه</div>
        <div class="col-md-6 col-sm-6 col-xs-6 rtl"><span class="subscription-step-number @if(empty($step) || $step == 'otp')subscription-active-step @endif">۱</span> ثبت شماره موبایل</div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 text-center">
        <div class="subscription-gift-image-container-first-step">
            @if(empty($step) || $step == 'otp')
                <img class="img-responsive" src="{{ asset('images/gift_step_one.png') }}" style="margin-bottom: 30px;">
            @elseif($step == 'subscribed')
                <img class="img-responsive" src="{{ asset('images/gift_step_two.png') }}" style="margin-bottom: 30px;">
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12 description-container">
        <div class="panel panel-default">
            <div class="panel-heading rtl">دریافت هدیه</div>
            <div class="panel-body">
                @if (count($errors) > 0)
                    <div class="alert alert-danger rtl">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(empty($step))
                <form name="subscription" method="post">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-md-12 col-xs-12">
                            <input name="phone_number" class="form-control" type="text" value="{{ old('phone_number') }}" placeholder="شماره موبایل">
                            <sub>مثال: 09123456789</sub>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-12 col-xs-12 text-right">
                            <input name="submit" class="btn btn-block btn-success" type="submit" value="ثبت شماره موبایل">
                        </div>
                    </div>
                </form>
                @endif

                @if($step == 'otp')
                    <form name="subscription" method="post" action="/subscribe/otp">
                        {{ csrf_field() }}
                        <div class="row">
                            <div class="col-md-12 rtl">
                                یک کد چهار رقمی برای شما پیامک شد.<br>
                                لطفا پس از دریافت کد آن را در فیلد زیر وارد کنید.
                                <hr>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 col-xs-12">
                                <input name="otp" class="form-control" type="text" value="{{ old('otp') }}" placeholder="کد دریافتی">
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-12 col-xs-12 text-right">
                                <input name="submit" class="btn btn-block btn-success" type="submit" value="ثبت کد دریافتی">
                            </div>
                        </div>
                        @if (!empty($accountId))
                            <input name="account_id" type="hidden" value="{{ $accountId }}">
                        @endif
                    </form>
                @endif

                @if($step == 'subscribed')
                    <div class="row">
                        <div class="col-md-12 rtl">
                            با تشکر، شماره موبایل شما در لیست قرعه کشی ثبت شد.<br>
                            برای اطلاع از نتایج و مشاهده لیست برندگان به همین وب سایت مراجعه نمایید.<hr>
                            برای دریافت آخرین نسخه اپلیکیشن مجله جیک و پیک و استفاده از مطالب مجله به لینک زیر مراجعه نمایید.<br>
                            <a href="/download">دریافت اپلیکیشن جیک و پیک</a>
                        </div>
                    </div>
                @endif

                @if($step == 'account_not_found')
                    <div class="row">
                        <div class="col-md-12 rtl">
                            اکانت شما پیدا نشد. برای تلاش مجدد از لینک زیر اقدام کنید.<br>
                            <a href="/subscribe">شرکت در قرعه کشی</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>
