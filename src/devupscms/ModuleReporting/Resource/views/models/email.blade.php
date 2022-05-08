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

        .footer {
            background: #dcdbdb;
            display: inline-flex;
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
                <img width="150" class="img-responsive" alt="buyamsellam24"
                     src="{{__front}}image/logo_1.png" title="buyamsellam24">
                <hr>
            </div>
        </div>
    </div>
    <div class="body">
        <div id="yield">{yield}</div>
    </div>
    <div class="footer">
        <div class="content-left">
            <img width="150" class="img-responsive" alt="buyamsellam24"
                 src="{{__front}}image/logo_1.png" title="buyamsellam24">
        </div>
        <div class="content-right">
          <span class="content-footer-right">
            Besoin d’aide ? Consultez la FAQ. <br>
            Conditions générales
          </span>
        </div>
    </div>
</section>
</body>
</html>

