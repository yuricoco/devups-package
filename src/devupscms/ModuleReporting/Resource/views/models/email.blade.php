<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email</title>
    <style>
        body {
            background-color: white;
            color: #222;
            font-family: 'Open Sans', sans-serif;
            line-height: 18px;
            margin: 0px;
        }

        .content {
            height: max-content;
        }
        #yield {
            padding: 15px 14%;
        }

        .footer {
            background: #dcdbdb;
            padding: 15px 14%;
            width: -webkit-fill-available;
        }

        .content-right {
            float: right;
            width: 100%;
        }

        .content-footer-right {
            float: right;
        }

        .body-text-content {
            padding: 60px;
            margin: 0px 15%;
            line-height: 30px;
        }
    </style>
</head>
<body>
<section class="content">
    <div class="header" style="width: 25%; margin: 30px 34%; justify-content: center;">
        <div class="header-content" style="text-align: center;">
            <div class="header-logo">
                <img width="150" class="img-responsive" alt="{{PROJECT_NAME}}"
                     src="{{__env}}logo-long.png" title="{{PROJECT_NAME}}">
                <hr>
            </div>
        </div>
    </div>
    <div class="body">
        <div id="yield">{yield}</div>
    </div>
    <div style="text-align: center" class="footer  ">
        <span class="">
            <img width="30"  alt="{{PROJECT_NAME}}"
                 src="{{__env}}logo.png" title="{{PROJECT_NAME}}">
        </span><br>
        <span style="font-size: 14px; line-height: 180%;">&copy; {{date("Y")}} All
            Rights Reserved</span>
    </div>
</section>
</body>
</html>

