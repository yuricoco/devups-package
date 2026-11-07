<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{$mail_title}}</title>
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
<body >

<div class="layout" >
<div style=" text-align:center;min-width:640px;width:100%;height:100%;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;margin:0;padding:0"
     bgcolor="#fafafa">

    <table border="0" cellpadding="0" cellspacing="0" id="m_2482255890803978757body"
           style="margin: auto; text-align:center;min-width:640px;width:100%; padding:0" bgcolor="#fafafa">
        <tbody>
        <tr>
            <td style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;height:4px;font-size:4px;line-height:4px"
                bgcolor="#f2142b"></td>
        </tr>
        <tr>
            <td style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.6;color:#5c5c5c;padding:25px 0">

                <img width="210" class="img-responsive" alt="{{PROJECT_NAME}}"
                     src="{{__env}}logo-long.png" title="{{PROJECT_NAME}}">

            </td>
        </tr>
        <tr>
            <td style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif">
                <table border="0" cellpadding="0" cellspacing="0" class="m_2482255890803978757wrapper"
                       style="width:640px;border-collapse:separate;border-spacing:0;margin:0 auto">
                    <tbody>
                    <tr>
                        <td class="m_2482255890803978757wrapper-cell"
                            style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;border-radius:3px;overflow:hidden;padding:18px 25px;border:1px solid #ededed"
                            align="left" bgcolor="#ffffff">
                            <table border="0" cellpadding="0" cellspacing="0"
                                   style="width:100%;border-collapse:separate;border-spacing:0">
                                <tbody>
                                <tr>
                                    <td>

                                        @yield("content")

                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>

        <tr>
            <td style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.6;color:#5c5c5c;padding:25px 0">
                <img width="60" class="img-responsive" alt="{{PROJECT_NAME}}"
                     src="{{__env}}logo.png" title="{{PROJECT_NAME}}">
                <div>

                    <div class=" ">
                        Copyright © {{date('Y')}}
                    </div>
                    <div class="">
                        <b class="content-footer-right">
                            {{PROJECT_NAME}}
                        </b>
                    </div>
                    You're receiving this email because of your account on <a rel="noopener noreferrer"
                                                                              href="{{__env_front}}"
                                                                              style="color:#3777b0;text-decoration:none"
                                                                              target="_blank"
                    >   {{PROJECT_NAME}} </a>.
                </div>
            </td>
        </tr>


        <tr>
            <td style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.6;color:#5c5c5c;padding:25px 0">

            </td>
        </tr>
        </tbody>
    </table>

</div>
</div>
</body>
</html>

