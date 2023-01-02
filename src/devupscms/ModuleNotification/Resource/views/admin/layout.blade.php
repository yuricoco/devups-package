@extends('layout.layout')
@section('title', 'Page Title')

<?php function style(){ ?>

<?php foreach (dclass\devups\Controller\Controller::$cssfiles as $cssfile){ ?>
<link href="<?= $cssfile ?>" rel="stylesheet">
<?php } ?>

<?php } ?>

@section('content')

    @include("default.moduleheaderwidget")
    <hr>
    @yield('layout_content')
    <hr>
    <div class='col-lg-12'>
        <div class='alert alert-info row'>
            <div class='col-lg-4'>
                a sample of code that shows how to use Notification component.
                <pre class=' '>
                    // for user (front office)
        Notification::on($entityinstance, "event", $data)
            ->send($users);

                    // for admin (back office)
        Notification::on($entityinstance, "event", $data)
            ->sendadmin($admins);

    </pre>
                Note: for pdf we use mpdf
                <pre class=' '>composer require mpdf/mpdf </pre>
                to install
            </div>
            <div class='col-lg-8'>
                <table class="table">
                    <tr>
                        <th>init()</th>
                        <th>
                            $entity : the instance of the entity we are making a notification / Object<br>
                            $event : the name of the event stored in _key attribut of notificationtype / string<br>
                            $data : an array key value to match dynamic variable inserted in the notification content /
                            Array<br>
                        </th>
                        <td>static method that wait the name of the email model and return an instance of the entity
                            model
                        </td>
                    </tr>
                    <tr>
                        <th>send()</th>
                        <th>$users : list of users</th>
                        <td>Create the notification base on the current instance, the broadcast it to the receivers
                        </td>
                    </tr>
                    <tr>
                        <th>sendadmin()</th>
                        <th>$admins : list of admins</th>
                        <td>Create the notification base on the current instance, the broadcast it to the receivers
                        </td>
                    </tr>
                    <tr>
                        <th>sendMail()</th>
                        <th>data :array</th>
                        <td>instance method that wait the data to compute the mail with. it must be an array key =>
                            value
                            else
                            empty array to say no data to compute
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

@endsection

<?php function script(){ ?>

<script src="<?= CLASSJS ?>devups.js"></script>
<script src="<?= CLASSJS ?>model.js"></script>
<script src="<?= CLASSJS ?>ddatatable.js"></script>
<?php foreach (dclass\devups\Controller\Controller::$jsfiles as $jsfile){ ?>
<script src="<?= $jsfile ?>"></script>
<?php } ?>

<?php } ?>

