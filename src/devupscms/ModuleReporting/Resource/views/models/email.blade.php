<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email</title>
    <style>
        body {
            background: #EAF0F3;
            color: #222;
            font-family: 'Open Sans', sans-serif;
            line-height: 18px;
            margin: 0px;
        }

        .layout {
            display: inline-flex;
            padding: 46px 58px 46px 58px;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 67px;
        }

        .footer {
            /*display: inline-flex;*/
font-size: 10px;

        }

        .content-body {
            display: flex;
            padding: 34px 55px 34px 55px;
            flex-direction: column;
            align-items: center;
            border-radius: 6px;
            background: #FFF;
        }

    </style>
</head>
<body class="layout">
<section class="content">
    <div class="header" style="">
        <div class="header-content" style="text-align: center;">
            <div class="header-logo">
                <img width="210" class="img-responsive" alt="{{PROJECT_NAME}}"
                     src="{{__env}}logo-long.png" title="{{PROJECT_NAME}}">
            </div>

        </div>
    </div>
    <div class="content-body">
        <div id="yield">{yield}</div>
    </div>
    <div class="footer text-center">
        <div class=" text-center ">
            <img style="margin: auto; width: 30px" width="30"
                 src="{{__admin}}images/facebook.svg">
        </div>
        <div class=" ">
            Copyright © 2023
        </div>
        <div class="">
            <b class="content-footer-right">
                COCO BEAUTY
            </b>
        </div>
    </div>
</section>
</body>
</html>

